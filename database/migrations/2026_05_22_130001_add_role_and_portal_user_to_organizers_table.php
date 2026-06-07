<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureColumn('organizers', 'admin_role_id', function (Blueprint $table): void {
            $table->foreignId('admin_role_id')
                ->nullable()
                ->after('password')
                ->constrained('admin_roles')
                ->nullOnDelete();
        });

        $this->ensureColumn('organizers', 'user_id', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()
                ->after('admin_role_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if ($this->columnExists('organizers', 'user_id')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if ($this->columnExists('organizers', 'admin_role_id')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('admin_role_id');
            });
        }
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
