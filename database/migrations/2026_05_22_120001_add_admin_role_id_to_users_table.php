<?php

use App\Models\AdminRole;
use App\Models\User;
use App\Support\Admin\AdminModules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureColumn('users', 'admin_role_id', function (Blueprint $table): void {
            $table->foreignId('admin_role_id')
                ->nullable()
                ->after('is_admin')
                ->constrained('admin_roles')
                ->nullOnDelete();
        });

        if (! Schema::hasTable('admin_roles')) {
            return;
        }

        $this->ensureColumn('admin_roles', 'audience', function (Blueprint $table): void {
            $table->string('audience', 32)->default('both')->after('slug');
        });

        $roleAttributes = [
            'name' => 'Super Administrator',
            'description' => 'Full access to all admin modules',
            'permissions' => AdminModules::keys(),
            'is_super' => true,
        ];

        if ($this->columnExists('admin_roles', 'audience')) {
            $roleAttributes['audience'] = 'staff';
        }

        $superRole = AdminRole::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            $roleAttributes,
        );

        if (! $this->columnExists('users', 'admin_role_id')) {
            return;
        }

        User::query()
            ->where('is_admin', true)
            ->whereNull('admin_role_id')
            ->update(['admin_role_id' => $superRole->id]);
    }

    public function down(): void
    {
        if (! $this->columnExists('users', 'admin_role_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_role_id');
        });
    }

    private function ensureColumn(string $table, string $column, callable $definition): void
    {
        if ($this->columnExists($table, $column)) {
            return;
        }

        try {
            Schema::table($table, $definition);
        } catch (QueryException $e) {
            if ($this->isDuplicateColumnError($e) || $this->columnExists($table, $column)) {
                return;
            }

            throw $e;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (Schema::hasColumn($table, $column)) {
            return true;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 AS found FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
             LIMIT 1',
            [$database, $table, $column],
        );

        return $row !== null;
    }

    private function isDuplicateColumnError(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return $sqlState === '42S21'
            || $driverCode === 1060
            || str_contains(strtolower($e->getMessage()), 'duplicate column');
    }
};
