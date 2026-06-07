<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs admin_roles when audience was never added (e.g. partial migrate on eventmanager DB).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_roles')) {
            return;
        }

        if (! $this->columnExists('admin_roles', 'audience')) {
            try {
                Schema::table('admin_roles', function (Blueprint $table) {
                    if (Schema::hasColumn('admin_roles', 'slug')) {
                        $table->string('audience', 32)->default('both')->after('slug');
                    } else {
                        $table->string('audience', 32)->default('both');
                    }
                });
            } catch (QueryException $e) {
                if (! $this->isDuplicateColumnError($e) && ! $this->columnExists('admin_roles', 'audience')) {
                    Schema::table('admin_roles', function (Blueprint $table) {
                        $table->string('audience', 32)->default('both');
                    });
                }
            }
        }

        if (! $this->columnExists('admin_roles', 'audience')) {
            return;
        }

        DB::table('admin_roles')
            ->where(function ($query) {
                $query->whereNull('audience')->orWhere('audience', '');
            })
            ->update(['audience' => 'both']);

        DB::table('admin_roles')
            ->where('slug', 'super-admin')
            ->update(['audience' => 'staff']);
    }

    public function down(): void
    {
        // Repair migration — no rollback.
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
