<?php

declare(strict_types=1);

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Support\Installer\EnvFileWriter;
use App\Support\Installer\InstallationStatus;
use App\Support\Installer\SeededAdminCredentials;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PDO;
use Throwable;

final class InstallerController extends Controller
{
    private const REQUIRED_EXTENSIONS = ['ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'];

    private const INSTALL_FINISH_CACHE_MINUTES = 45;

    /**
     * Cache key prefix for one-shot install completion (session-independent).
     */
    private const FINISH_CACHE_PREFIX = 'installer.finish.';

    public function index(): View
    {
        return view('install.welcome', ['checks' => $this->requirementChecks()]);
    }

    public function setup(Request $request): RedirectResponse|View
    {
        $checks = $this->requirementChecks();
        if (! collect($checks)->every(fn (array $c): bool => (bool) $c['pass'])) {
            return redirect()->route('install.index');
        }

        // Make sure .env + APP_KEY exist BEFORE rendering the form, otherwise
        // the CSRF token / session cookie won't be encryptable and the POST
        // will fail with 419 Page Expired.
        $this->ensureEnvAndKey();

        return view('install.setup', [
            'defaultAdminEmail' => SeededAdminCredentials::EMAIL,
            'defaultAdminName' => SeededAdminCredentials::NAME,
        ]);
    }

    /**
     * Guarantee .env exists and contains a usable APP_KEY before the form is
     * rendered. Also force file-based sessions/cache so the installer never
     * depends on the database (which doesn't exist yet).
     */
    private function ensureEnvAndKey(): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $example = base_path('.env.example');
            if (File::exists($example)) {
                File::copy($example, $envPath);
            } else {
                File::put($envPath, "APP_NAME=Laravel\nAPP_ENV=local\nAPP_DEBUG=true\n");
            }
        }

        // Force file-based session/cache so the installer never depends on DB.
        EnvFileWriter::merge($envPath, [
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
        ]);

        $contents = (string) File::get($envPath);
        $hasKey = preg_match('/^APP_KEY=base64:[^\s"]+/m', $contents) === 1
            || preg_match('/^APP_KEY="base64:[^"]+"/m', $contents) === 1;

        if (! $hasKey) {
            try {
                Artisan::call('config:clear');
                Artisan::call('key:generate', ['--force' => true]);
                $this->reloadAppKey();
            } catch (Throwable $e) {
                Log::warning('[installer] could not pre-generate APP_KEY', ['msg' => $e->getMessage()]);
            }
        }
    }

    /**
     * Browsers only GET this URL; installation must be submitted from the setup form (POST).
     */
    public function finishHelp(): RedirectResponse
    {
        return redirect()
            ->route('install.setup')
            ->with('installer_notice', 'This step runs when you submit the configuration form. Use "Run installation" on the previous page (do not refresh or open this URL directly).');
    }

    public function complete(Request $request): RedirectResponse|View
    {
        $finishId = $request->query('finish');
        if (is_string($finishId) && $finishId !== '') {
            $payload = Cache::store('file')->pull(self::FINISH_CACHE_PREFIX.$finishId);
            if (is_array($payload) && ! empty($payload['installed'])) {
                $admin = isset($payload['adminName'], $payload['adminEmail'], $payload['adminPassword'])
                    && is_string($payload['adminName'])
                    && is_string($payload['adminEmail'])
                    && is_string($payload['adminPassword'])
                    ? [
                        'adminName' => $payload['adminName'],
                        'adminEmail' => $payload['adminEmail'],
                        'adminPassword' => $payload['adminPassword'],
                    ]
                    : null;

                return $this->installCompleteView(false, $admin);
            }
        }

        if (InstallationStatus::completed()) {
            return $this->installCompleteView(true);
        }

        return redirect()
            ->route('install.setup')
            ->with('installer_notice', 'Submit the configuration form to finish installation. If you already installed, open the homepage.');
    }

    /**
     * @param  array{adminName: string, adminEmail: string, adminPassword: string}|null  $admin
     */
    private function installCompleteView(bool $sessionStale, ?array $admin = null): View
    {
        $admin ??= [
            'adminName' => (string) env('ADMIN_NAME', SeededAdminCredentials::NAME),
            'adminEmail' => (string) env('ADMIN_EMAIL', SeededAdminCredentials::EMAIL),
            'adminPassword' => (string) env('ADMIN_PASSWORD', SeededAdminCredentials::PASSWORD),
        ];

        return view('install.complete', [
            'sessionStale'    => $sessionStale,
            'adminName'       => $admin['adminName'],
            'adminEmail'      => $admin['adminEmail'],
            'adminPassword'   => $admin['adminPassword'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ignore_user_abort(true);
        @set_time_limit(600);
        @ini_set('max_execution_time', '600');
        @ini_set('memory_limit', '512M');

        Log::info('[installer] 1. store() entered');

        $checks = $this->requirementChecks();
        if (! collect($checks)->every(fn (array $c): bool => (bool) $c['pass'])) {
            Log::warning('[installer] requirements failed');

            return redirect()->route('install.index')->withErrors([
                '_require' => 'Fix server requirements before continuing.',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'app_name'    => ['required', 'string', 'max:191'],
            'app_url'     => ['required', 'url'],
            'admin_name'  => ['required', 'string', 'max:191'],
            'admin_email' => ['required', 'string', 'email:rfc', 'max:191'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'db_driver'   => ['required', 'in:mysql,mariadb'],
            'db_host'     => ['nullable', 'string', 'max:191'],
            'db_port'     => ['nullable', 'string', 'max:10'],
            'db_database' => ['nullable', 'string', 'max:255'],
            'db_username' => ['nullable', 'string', 'max:191'],
            'db_password' => ['nullable', 'string'],
        ]);

        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            /** @phpstan-ignore-next-line */
            $data = $v->getData();
            $driver = (string) ($data['db_driver'] ?? '');
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                foreach (['db_host', 'db_port', 'db_database', 'db_username'] as $field) {
                    if (($data[$field] ?? '') === '' || ($data[$field] ?? null) === null) {
                        $v->errors()->add($field, __('This field is required for :driver.', ['driver' => $driver]));
                    }
                }
            }
        });

        $validated = $validator->validate();

        Log::info('[installer] 2. validated');

        $driver = (string) $validated['db_driver'];

        try {
            $this->probeMysql(
                isset($validated['db_host'])     ? (string) $validated['db_host']     : '',
                isset($validated['db_port'])     ? (string) $validated['db_port']     : '',
                isset($validated['db_database']) ? (string) $validated['db_database'] : '',
                isset($validated['db_username']) ? (string) $validated['db_username'] : '',
                isset($validated['db_password']) ? (string) $validated['db_password'] : '',
            );
        } catch (\InvalidArgumentException $e) {
            Log::warning('[installer] db probe failed', ['msg' => $e->getMessage()]);

            return back()->withInput()->withErrors(['database' => $e->getMessage()]);
        }

        Log::info('[installer] 3. db probe ok');

        $envPath = base_path('.env');

        // Safety: make sure .env exists before merging.
        if (! File::exists($envPath)) {
            $example = base_path('.env.example');
            if (File::exists($example)) {
                File::copy($example, $envPath);
            } else {
                File::put($envPath, '');
            }
        }

        EnvFileWriter::merge($envPath, [
            'APP_NAME'         => $validated['app_name'],
            'APP_URL'          => rtrim((string) $validated['app_url'], '/'),
            'APP_ENV'          => 'production',
            'APP_DEBUG'        => 'false',
            'DB_CONNECTION'    => $driver,
            'SESSION_DRIVER'   => 'file',
            'CACHE_STORE'      => 'file',
            'QUEUE_CONNECTION' => 'sync',
        ]);

        EnvFileWriter::merge($envPath, [
            'DB_HOST'       => (string) $validated['db_host'],
            'DB_PORT'       => (string) (($validated['db_port'] ?? '') !== '' ? $validated['db_port'] : '3306'),
            'DB_DATABASE'   => (string) $validated['db_database'],
            'DB_USERNAME'   => (string) $validated['db_username'],
            'DB_PASSWORD'   => isset($validated['db_password']) ? trim((string) $validated['db_password']) : '',
            'ADMIN_NAME'    => (string) $validated['admin_name'],
            'ADMIN_EMAIL'   => (string) $validated['admin_email'],
            'ADMIN_PASSWORD' => (string) $validated['admin_password'],
        ]);

        $this->applyInstallerRuntimeConfig(
            $driver,
            isset($validated['db_host']) ? (string) $validated['db_host'] : '',
            isset($validated['db_port']) ? (string) $validated['db_port'] : '',
            isset($validated['db_database']) ? (string) $validated['db_database'] : '',
            isset($validated['db_username']) ? (string) $validated['db_username'] : '',
            isset($validated['db_password']) ? trim((string) $validated['db_password']) : '',
        );

        Log::info('[installer] 4. env written');

        // Only generate APP_KEY if one doesn't already exist. We pre-generated
        // it in setup() so this branch is normally skipped — rotating the key
        // here would invalidate the current session cookie and break CSRF.
        $envContents = (string) File::get($envPath);
        $hasKey = preg_match('/^APP_KEY=base64:[^\s"]+/m', $envContents) === 1
            || preg_match('/^APP_KEY="base64:[^"]+"/m', $envContents) === 1;

        try {
            Artisan::call('config:clear');
            $this->reapplyInstallerSeedAdminConfigForCurrentRequest($validated);
            if (! $hasKey) {
                Artisan::call('key:generate', ['--force' => true]);
                $this->reloadAppKey();
            }
        } catch (Throwable $e) {
            Log::error('[installer] key:generate failed', ['msg' => $e->getMessage()]);

            return back()->withInput()->withErrors(['install' => 'Could not regenerate application key: '.$e->getMessage()]);
        }

        $this->applyInstallerRuntimeConfig(
            $driver,
            isset($validated['db_host']) ? (string) $validated['db_host'] : '',
            isset($validated['db_port']) ? (string) $validated['db_port'] : '',
            isset($validated['db_database']) ? (string) $validated['db_database'] : '',
            isset($validated['db_username']) ? (string) $validated['db_username'] : '',
            isset($validated['db_password']) ? trim((string) $validated['db_password']) : '',
        );

        $this->reapplyInstallerSeedAdminConfigForCurrentRequest($validated);

        Log::info('[installer] 5. key ready', ['regenerated' => ! $hasKey]);

        $wipeExisting = filter_var($request->input('fresh_database'), FILTER_VALIDATE_BOOLEAN);
        // Default: incremental migrate only. Checking "Erase..." runs migrate:fresh (destructive).
        $migrateArgs = $wipeExisting
            ? ['migrate:fresh', '--force', '--no-interaction']
            : ['migrate', '--force', '--no-interaction'];

        $steps = [
            [
                'args' => $migrateArgs,
                'failurePrefix' => 'Migration failed. ',
                'failureSuffix' => '',
                'errorLogTag' => 'migrate',
                'successLog' => '[installer] 6. migrate done',
                'warnIfTablesAlreadyExist' => ! $wipeExisting,
                'wipeChosen' => $wipeExisting,
            ],
            [
                'args' => ['db:seed', '--force', '--no-interaction'],
                'failurePrefix' => 'Seeding failed. ',
                'failureSuffix' => ' (Migrations finished; you may run "php artisan db:seed --force" manually.)',
                'errorLogTag' => 'seed',
                'successLog' => '[installer] 7. seeders done',
                'warnIfTablesAlreadyExist' => false,
                'wipeChosen' => false,
            ],
        ];

        foreach ($steps as $step) {
            $out = $this->runArtisanCli($step['args']);
            if (! $out['ok']) {
                Log::error('[installer] '.$step['errorLogTag'].' failed', ['out' => $out['output']]);

                $message = $step['failurePrefix'].$out['output'].$step['failureSuffix'];
                if ($step['warnIfTablesAlreadyExist'] && self::looksLikeStoredSchemaConflict($out['output'])) {
                    $message .= ' Your database looks like an old install (tables exist but Laravel cannot reconcile them). Either create a NEW empty database, or repeat this step after checking '
                        . '"Erase existing tables" above (migrate:fresh — deletes ALL data in that database).';
                }

                return back()->withInput()->withErrors(['install' => $message]);
            }

            Log::info($step['successLog']);
        }

        $finishToken = Str::uuid()->toString();
        Cache::store('file')->put(
            self::FINISH_CACHE_PREFIX.$finishToken,
            [
                'installed' => true,
                'adminName' => (string) $validated['admin_name'],
                'adminEmail' => (string) $validated['admin_email'],
                'adminPassword' => (string) $validated['admin_password'],
            ],
            now()->addMinutes(self::INSTALL_FINISH_CACHE_MINUTES),
        );

        InstallationStatus::markCompleted();

        Log::info('[installer] 8. redirecting to complete', ['token' => $finishToken]);

        return redirect()->route('install.complete', ['finish' => $finishToken]);
    }

    /**
     * Re-read APP_KEY from the freshly written .env and push it into the
     * running config + encrypter so subsequent cookies in THIS request use it.
     */
    private function reloadAppKey(): void
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return;
        }

        $contents = (string) File::get($envPath);
        if (! preg_match('/^APP_KEY=(.*)$/m', $contents, $m)) {
            return;
        }

        $rawKey = trim($m[1]);
        if (strlen($rawKey) >= 2 && $rawKey[0] === '"' && substr($rawKey, -1) === '"') {
            $rawKey = substr($rawKey, 1, -1);
        }

        if ($rawKey === '') {
            return;
        }

        config(['app.key' => $rawKey]);

        try {
            app()->forgetInstance('encrypter');
        } catch (Throwable) {
            // ignore
        }
    }

    /**
     * Artisan::call('config:clear') wipes runtime values; restore admin seed input for this HTTP request only.
     *
     * @param  array<string, mixed>  $validated
     */
    private function reapplyInstallerSeedAdminConfigForCurrentRequest(array $validated): void
    {
        config([
            'installer.seed_admin.name' => (string) $validated['admin_name'],
            'installer.seed_admin.email' => (string) $validated['admin_email'],
            'installer.seed_admin.password' => (string) $validated['admin_password'],
        ]);
    }

    private function applyInstallerRuntimeConfig(
        string $driver,
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
    ): void {
        $portNumber = $port !== '' ? $port : '3306';

        config([
            'database.default' => $driver,
            "database.connections.{$driver}.host" => $host,
            "database.connections.{$driver}.port" => $portNumber,
            "database.connections.{$driver}.database" => $database,
            "database.connections.{$driver}.username" => $username,
            "database.connections.{$driver}.password" => $password,
            'session.driver' => 'file',
            'cache.default' => 'file',
        ]);

        DB::purge($driver);
        DB::reconnect($driver);
    }

    /**
     * @return list<array{label: string, pass: bool, detail: string|null}>
     */
    private function requirementChecks(): array
    {
        $checks = [];

        $checks[] = [
            'label'  => 'PHP '.PHP_VERSION.' (minimum 8.2)',
            'pass'   => version_compare(PHP_VERSION, '8.2.0', '>='),
            'detail' => null,
        ];

        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            $checks[] = [
                'label'  => 'Extension: '.$ext,
                'pass'   => extension_loaded($ext),
                'detail' => null,
            ];
        }

        foreach (['pdo_mysql'] as $pdoExt) {
            $checks[] = [
                'label'  => 'Extension: '.$pdoExt.' (required for MySQL/MariaDB)',
                'pass'   => extension_loaded($pdoExt),
                'detail' => null,
            ];
        }

        $writable = [
            storage_path(),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($writable as $path) {
            $checks[] = [
                'label'  => 'Writable: '.$path,
                'pass'   => File::isDirectory($path) && File::isWritable($path),
                'detail' => null,
            ];
        }

        $checks[] = [
            'label'  => '.env file present or writable from .env.example',
            'pass'   => File::exists(base_path('.env')) || File::isWritable(dirname(base_path('.env')))
                || File::isWritable(base_path('.env.example')),
            'detail' => null,
        ];

        return $checks;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private static function looksLikeStoredSchemaConflict(string $output): bool
    {
        $o = strtolower($output);

        return str_contains($o, 'base table or view already exists')
            || str_contains($o, '42s01')
            || str_contains($o, 'already exists');
    }

    private function probeMysql(
        string $host,
        string $port,
        string $database,
        string $username,
        string $password,
    ): void {
        if (! extension_loaded('pdo_mysql')) {
            throw new \InvalidArgumentException('PDO MySQL extension (pdo_mysql) is not enabled.');
        }

        try {
            $portNumber = ($port !== '' ? $port : '3306');
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $portNumber, $database),
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );
            $pdo->query('SELECT 1');
        } catch (Throwable $e) {
            throw new \InvalidArgumentException('Could not connect to the database server: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Run Artisan for migrate / db:seed during web install.
     *
     * Always uses in-process {@see Artisan::call}. Spawning a separate
     * `php artisan` subprocess from Apache / `php artisan serve` breaks often on
     * Windows (TCP "connection reset") and adds little benefit for a one-off install.
     *
     * @param  list<string>  $arguments Artisan arguments after "artisan"
     * @return array{ok: bool, output: string}
     */
    private function runArtisanCli(array $arguments): array
    {
        return $this->runInstallerArtisan($arguments);
    }

    /**
     * @param  list<string>  $arguments
     * @return array{ok: bool, output: string}
     */
    private function runInstallerArtisan(array $arguments): array
    {
        $primary = $arguments[0] ?? '';
        $command = match ($primary) {
            'migrate' => ['migrate', ['--force' => true]],
            'migrate:fresh' => ['migrate:fresh', ['--force' => true]],
            'db:seed' => ['db:seed', ['--force' => true]],
            default => null,
        };

        if ($command === null) {
            return ['ok' => false, 'output' => 'Unsupported installer artisan command: `'.$primary.'`.'];
        }

        try {
            Log::info('[installer] Artisan in-process', ['cmd' => $arguments]);
            Artisan::call($command[0], $command[1]);

            return ['ok' => true, 'output' => trim(Artisan::output()) ?: ($primary.' completed.')];
        } catch (Throwable $inner) {
            return ['ok' => false, 'output' => $inner->getMessage()];
        }
    }
}
