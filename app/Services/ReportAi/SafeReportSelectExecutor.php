<?php

declare(strict_types=1);

namespace App\Services\ReportAi;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

final class SafeReportSelectExecutor
{
    /**
     * @return array{
     *   columns?: list<string>,
     *   rows?: list<array<string, mixed>>,
     *   truncated?: bool,
     *   error?: string,
     *   sql?: string,
     * }
     */
    public function run(string $sql): array
    {
        $driver = (string) config('database.connections.'.config('database.default').'.driver', 'sqlite');

        $trimmedSql = trim($sql);
        if ($trimmedSql === '') {
            return ['error' => 'Empty SQL.'];
        }

        if (str_contains($trimmedSql, ';')) {
            return ['error' => 'Multiple statements are not allowed.'];
        }

        if (str_contains($trimmedSql, '--') || str_contains($trimmedSql, '/*') || str_contains($trimmedSql, '*/')) {
            return ['error' => 'SQL comments are not allowed.'];
        }

        $normalizedScan = strtolower($trimmedSql);
        $blockedFragments = [
            'sqlite_master', 'sqlite_temp', 'sqlite_sequence', 'pragma(', 'information_schema',
            'performance_schema', 'mysql.', 'into outfile', 'into dumpfile', 'load_extension',
            'attach ', 'benchmark(', 'sleep(', 'grant ', 'revoke ',
        ];
        foreach ($blockedFragments as $bad) {
            if (str_contains($normalizedScan, $bad)) {
                return ['error' => 'This query touches restricted database objects or functions.'];
            }
        }

        if (str_contains($trimmedSql, chr(0))) {
            return ['error' => 'This query touches restricted database objects or functions.'];
        }

        foreach ([
            'insert ', 'update ', 'delete ', 'drop ', 'alter ', 'create ', 'truncate ', 'replace ',
            'vacuum ', 'detach ', 'reindex ', 'merge ', 'call ',
        ] as $kw) {
            if (preg_match('/\b'.preg_quote($kw, '/').'/i', $normalizedScan) === 1) {
                return ['error' => 'Only read-only SELECT statements are permitted.'];
            }
        }

        if (preg_match('/\bfor\s+update\b/i', $trimmedSql) === 1) {
            return ['error' => 'FOR UPDATE locking is not allowed.'];
        }

        if (preg_match('/^\s*(?:with\b[\s\S]+)?select\b/is', $trimmedSql) !== 1) {
            return ['error' => 'Query must begin with SELECT (optionally preceded by WITH).'];
        }

        if (preg_match('/\bunion\b/i', $normalizedScan) === 1) {
            return ['error' => 'UNION queries are not permitted in AI reports yet.'];
        }

        if (! $this->referencesOnlyAllowedOuterTables($normalizedScan)) {
            return ['error' => 'Referencing a table outside the curated reporting schema is not permitted.'];
        }

        $maxRows = (int) Config::get('report_ai.max_rows', 500);
        $executableSql = $this->enforceSelectRowCap($trimmedSql, $maxRows);

        $pdo = DB::connection()->getPdo();

        /** @phpstan-ignore-next-line */
        $priorQueryOnly = null;
        if ($driver === 'sqlite' && $pdo instanceof PDO) {
            $priorQueryOnly = $pdo->query('PRAGMA query_only')->fetchColumn();
            $pdo->exec('PRAGMA query_only=ON');
        }

        $rowsRaw = null;
        $executeError = null;

        try {
            if ($driver === 'sqlite') {
                DB::select('explain query plan '.$executableSql);
            }

            $rowsRaw = DB::select($executableSql);
        } catch (Throwable $exception) {

            /** @phpstan-ignore-next-line */
            $executeError = 'Unable to execute the generated SQL: '.$exception->getMessage();

        } finally {
            if ($driver === 'sqlite' && $pdo instanceof PDO && $priorQueryOnly !== null) {

                /** @phpstan-ignore-next-line */
                $pdo->exec('PRAGMA query_only='.(filter_var((string) $priorQueryOnly, FILTER_VALIDATE_BOOLEAN) ? 'ON' : 'OFF'));

            }

        }

        /** @phpstan-ignore-next-line */
        if ($executeError !== null) {

            /** @phpstan-ignore-next-line */
            /** @phpstan-ignore-next-line */
            return ['error' => $executeError];

        }

        /** @phpstan-ignore-next-line */
        if (! is_array($rowsRaw)) {

            /** @phpstan-ignore-next-line */
            /** @phpstan-ignore-next-line */
            return ['error' => 'Query returned an unexpected payload.'];

        }

        /** @phpstan-ignore-next-line */
        if ($rowsRaw === []) {

            /** @phpstan-ignore-next-line */
            /** @phpstan-ignore-next-line */
            return ['sql' => $executableSql, 'columns' => [], 'rows' => [], 'truncated' => false];

        }

        /** @phpstan-ignore-next-line */
        $firstRow = $rowsRaw[0];

        /** @phpstan-ignore-next-line */

        /** @phpstan-ignore-next-line */
        $columns = array_map(static fn ($k): string => (string) $k, array_keys((array) $firstRow));

        /** @phpstan-ignore-next-line */
        $outRows = [];

        $truncated = false;

        foreach ($rowsRaw as $idx => $row) {

            if ($idx >= $maxRows) {

                $truncated = true;

                /** @phpstan-ignore-next-line */
                /** @phpstan-ignore-next-line */
                break;

            }

            /** @phpstan-ignore-next-line */
            $outRows[] = array_map(

                static fn (mixed $v): mixed => $v instanceof \Stringable ? (string) $v : $v,

                /** @phpstan-ignore-next-line */
                (array) $row

            );

        }

        /** @phpstan-ignore-next-line */
        return ['sql' => $executableSql, 'columns' => $columns, 'rows' => $outRows, 'truncated' => $truncated];
    }

    private function enforceSelectRowCap(string $sql, int $maxRows): string
    {
        if (preg_match('/\blimit\b/i', $sql) === 1) {
            $replaced = preg_replace_callback(
                '/\blimit\s+(\d+)\b/i',
                static fn (array $matches): string => 'LIMIT '.min((int) $matches[1], $maxRows + 1),
                $sql,
            );

            return $replaced ?? $sql;
        }

        return trim($sql).' LIMIT '.($maxRows + 1);
    }

    private function referencesOnlyAllowedOuterTables(string $normalizedLowerSql): bool
    {
        /** @var array<int|string, mixed>|null $whitelist */
        $whitelist = Config::get('report_ai.allowed_tables', []);

        if (! is_array($whitelist) || $whitelist === []) {
            return false;
        }

        preg_match_all('/\b(?:from|join)\s+(?!select\b)([^\s(,]+)/i', $normalizedLowerSql, $matches);

        /** @phpstan-ignore-next-line */
        $fragments = $matches[1] ?? [];
        if ($fragments === []) {
            return false;
        }

        $allowed = [];
        foreach ($whitelist as $entry) {
            $allowed[] = strtolower(is_string($entry) ? $entry : (string) $entry);
        }

        $matchedAllowedTable = false;

        foreach ($fragments as $fragment) {

            /** @phpstan-ignore-next-line */
            $normalizedFragment = strtolower(trim((string) $fragment));

            if ($normalizedFragment === '' || str_starts_with($normalizedFragment, '(')) {
                continue;
            }

            $strippedQuotes = strtolower(preg_replace('/^[\"`\[]|[\"`\[\]]$/u', '', $normalizedFragment) ?? $normalizedFragment);

            $parts = explode('.', $strippedQuotes);
            /** @phpstan-ignore-next-line */
            $base = strtolower(trim(trim($parts[0], '`"[]')));

            if ($base === '') {
                continue;
            }

            if (! in_array($base, $allowed, true)) {
                return false;
            }

            $matchedAllowedTable = true;
        }

        return $matchedAllowedTable;
    }
}
