<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_login_otps') && ! Schema::hasTable('login_otps')) {
            Schema::rename('admin_login_otps', 'login_otps');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('login_otps') && ! Schema::hasTable('admin_login_otps')) {
            Schema::rename('login_otps', 'admin_login_otps');
        }
    }
};
