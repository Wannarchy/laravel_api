<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_message_replies', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('contact_message_id')->index();
            $table->integer('admin_id')->nullable()->index();
            $table->text('body');
            $table->boolean('mail_sent')->default(false);
            $table->dateTime('created_at')->nullable()->useCurrent();

            $table->index(['contact_message_id', 'created_at'], 'idx_contact_replies_message');
        });

        $existing = DB::table('contact_messages')
            ->whereNotNull('admin_reply')
            ->where('admin_reply', '!=', '')
            ->get(['id', 'admin_reply', 'replied_by', 'replied_at']);

        foreach ($existing as $row) {
            DB::table('contact_message_replies')->insert([
                'contact_message_id' => $row->id,
                'admin_id' => $row->replied_by,
                'body' => $row->admin_reply,
                'mail_sent' => false,
                'created_at' => $row->replied_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_message_replies');
    }
};
