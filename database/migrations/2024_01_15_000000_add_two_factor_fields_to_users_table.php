<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns already exist before adding them
        $columns = DB::select("SHOW COLUMNS FROM users");
        $columnNames = array_column($columns, 'Field');
        
        // Add columns only if they don't exist
        if (!in_array('two_factor_secret', $columnNames)) {
            try {
                DB::statement("ALTER TABLE `users` ADD COLUMN `two_factor_secret` TEXT NULL AFTER `password`");
            } catch (\Exception $e) {
                // Column might have been added by another process, ignore error
                \Log::warning('Could not add two_factor_secret column: ' . $e->getMessage());
            }
        }
        
        // Refresh column list
        $columns = DB::select("SHOW COLUMNS FROM users");
        $columnNames = array_column($columns, 'Field');
        
        if (!in_array('two_factor_enabled', $columnNames)) {
            try {
                $afterColumn = in_array('two_factor_secret', $columnNames) ? 'two_factor_secret' : 'password';
                DB::statement("ALTER TABLE `users` ADD COLUMN `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `{$afterColumn}`");
            } catch (\Exception $e) {
                \Log::warning('Could not add two_factor_enabled column: ' . $e->getMessage());
            }
        }
        
        // Refresh column list again
        $columns = DB::select("SHOW COLUMNS FROM users");
        $columnNames = array_column($columns, 'Field');
        
        if (!in_array('two_factor_backup_codes', $columnNames)) {
            try {
                $afterColumn = in_array('two_factor_enabled', $columnNames) ? 'two_factor_enabled' : 
                              (in_array('two_factor_secret', $columnNames) ? 'two_factor_secret' : 'password');
                DB::statement("ALTER TABLE `users` ADD COLUMN `two_factor_backup_codes` TEXT NULL AFTER `{$afterColumn}`");
            } catch (\Exception $e) {
                \Log::warning('Could not add two_factor_backup_codes column: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_enabled',
                'two_factor_backup_codes'
            ]);
        });
    }
};
