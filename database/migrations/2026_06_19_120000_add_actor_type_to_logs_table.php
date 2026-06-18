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

        if (! Schema::hasColumn('logs', 'user_id')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->integer('user_id')->nullable()->index();
            });
        }

        if (! Schema::hasColumn('logs', 'actor_type')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->string('actor_type', 10)->nullable()->index();
            });
        }

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

    public function down(): void
    {
        if (! Schema::hasTable('logs')) {
            return;
        }

        if (Schema::hasColumn('logs', 'actor_type')) {
            Schema::table('logs', function (Blueprint $table) {
                $table->dropColumn('actor_type');
            });
        }
    }
};
