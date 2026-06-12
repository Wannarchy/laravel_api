<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->text('admin_reply')->nullable()->after('message');
            $table->integer('replied_by')->nullable()->index()->after('admin_reply');
            $table->dateTime('replied_at')->nullable()->after('replied_by');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn(['admin_reply', 'replied_by', 'replied_at']);
        });
    }
};
