<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('logs')) {
            return;
        }

        if (Schema::hasColumn('logs', 'admin_id')) {
            DB::table('logs')
                ->whereNotNull('admin_id')
                ->whereNull('user_id')
                ->update([
                    'user_id' => DB::raw('admin_id'),
                ]);

            DB::table('logs')
                ->whereNotNull('admin_id')
                ->update([
                    'admin_id' => null,
                ]);

            Schema::table('logs', function (Blueprint $table) {
                $table->dropIndex('logs_admin_id_index');
            });

            Schema::table('logs', function (Blueprint $table) {
                $table->dropColumn('admin_id');
            });
        }

        DB::table('logs')
            ->whereNotNull('user_id')
            ->where(function ($query) {
                $query->whereNull('actor_type')->orWhere('actor_type', '');
            })
            ->update(['actor_type' => 'user']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('logs') || Schema::hasColumn('logs', 'admin_id')) {
            return;
        }

        Schema::table('logs', function (Blueprint $table) {
            $table->integer('admin_id')->nullable()->index();
        });

        DB::table('logs')
            ->where('actor_type', 'admin')
            ->whereNotNull('user_id')
            ->update([
                'admin_id' => DB::raw('user_id'),
            ]);
    }
};
