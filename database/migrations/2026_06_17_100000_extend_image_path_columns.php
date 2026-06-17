<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE products ALTER COLUMN image_path TYPE VARCHAR(512)');
        DB::statement('ALTER TABLE categories ALTER COLUMN image_path TYPE VARCHAR(512)');
        DB::statement('ALTER TABLE homepage_slides ALTER COLUMN image_path TYPE VARCHAR(512)');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE products ALTER COLUMN image_path TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE categories ALTER COLUMN image_path TYPE VARCHAR(255)');
        DB::statement('ALTER TABLE homepage_slides ALTER COLUMN image_path TYPE VARCHAR(255)');
    }
};
