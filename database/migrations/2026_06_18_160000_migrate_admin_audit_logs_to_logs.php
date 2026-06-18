<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_audit_logs') && ! Schema::hasTable('logs')) {
            Schema::rename('admin_audit_logs', 'logs');
        }

        if (Schema::hasTable('logs') && ! Schema::hasColumn('logs', 'user_id')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->integer('user_id')->nullable()->index()->after('admin_id');
            });
        }

        if (Schema::hasTable('account_deletion_logs') && Schema::hasTable('logs')) {
            $rows = DB::table('account_deletion_logs')->orderBy('id')->get();

            foreach ($rows as $row) {
                DB::table('logs')->insert([
                    'admin_id' => null,
                    'user_id' => $row->user_id,
                    'action' => $row->action ?? 'account.self_deleted',
                    'target_type' => 'User',
                    'target_id' => $row->user_id,
                    'ip' => null,
                    'details' => json_encode(['method' => 'DELETE']),
                    'created_at' => $row->created_at,
                ]);
            }

            Schema::dropIfExists('account_deletion_logs');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('logs') && ! Schema::hasTable('admin_audit_logs')) {
            Schema::rename('logs', 'admin_audit_logs');
        }

        if (Schema::hasTable('admin_audit_logs') && Schema::hasColumn('admin_audit_logs', 'user_id')) {
            Schema::table('admin_audit_logs', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};
