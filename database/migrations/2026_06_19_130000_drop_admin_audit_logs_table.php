<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_audit_logs')) {
            return;
        }

        if (! Schema::hasTable('logs')) {
            Schema::rename('admin_audit_logs', 'logs');
        } else {
            $rows = DB::table('admin_audit_logs')->orderBy('id')->get();

            foreach ($rows as $row) {
                $exists = DB::table('logs')
                    ->where('admin_id', $row->admin_id)
                    ->where('action', $row->action)
                    ->where('created_at', $row->created_at)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('logs')->insert([
                    'actor_type' => 'admin',
                    'admin_id' => $row->admin_id,
                    'user_id' => null,
                    'action' => $row->action,
                    'target_type' => $row->target_type,
                    'target_id' => $row->target_id,
                    'ip' => $row->ip,
                    'details' => $row->details,
                    'created_at' => $row->created_at,
                ]);
            }
        }

        if (Schema::hasTable('logs') && ! Schema::hasColumn('logs', 'user_id')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->integer('user_id')->nullable()->index();
            });
        }

        if (Schema::hasTable('logs') && ! Schema::hasColumn('logs', 'actor_type')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->string('actor_type', 10)->nullable()->index();
            });
        }

        if (Schema::hasTable('logs')) {
            DB::table('logs')
                ->whereNotNull('admin_id')
                ->where(function ($query) {
                    $query->whereNull('actor_type')->orWhere('actor_type', '');
                })
                ->update(['actor_type' => 'admin']);

            DB::table('logs')
                ->whereNotNull('user_id')
                ->where(function ($query) {
                    $query->whereNull('actor_type')->orWhere('actor_type', '');
                })
                ->update(['actor_type' => 'user']);
        }

        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('account_deletion_logs');
    }

    public function down(): void
    {
        // Table volontairement non recréée.
    }
};
