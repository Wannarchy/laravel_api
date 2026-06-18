<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige les logs où l'ID utilisateur a été enregistré dans admin_id
     * (y compris si une migration précédente avait fait l'inverse).
     */
    public function up(): void
    {
        if (! Schema::hasTable('logs') || ! Schema::hasColumn('logs', 'admin_id')) {
            return;
        }

        $nonAdminIds = DB::table('utilisateurs')
            ->where('is_admin', false)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        DB::table('logs')
            ->whereNull('user_id')
            ->whereNotNull('admin_id')
            ->where('actor_type', 'user')
            ->update([
                'user_id' => DB::raw('admin_id'),
                'admin_id' => null,
            ]);

        if ($nonAdminIds !== []) {
            DB::table('logs')
                ->whereNull('user_id')
                ->whereIn('admin_id', $nonAdminIds)
                ->update([
                    'user_id' => DB::raw('admin_id'),
                    'admin_id' => null,
                    'actor_type' => 'user',
                ]);
        }
    }

    public function down(): void
    {
        // Données corrigées — pas de retour arrière automatique.
    }
};
