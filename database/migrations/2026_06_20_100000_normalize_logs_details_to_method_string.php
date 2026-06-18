<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('logs') || ! Schema::hasColumn('logs', 'details')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE logs
                ALTER COLUMN details TYPE varchar(32)
                USING (
                    CASE
                        WHEN details IS NULL THEN NULL
                        WHEN jsonb_typeof(details::jsonb) = 'object' THEN details::jsonb->>'method'
                        WHEN jsonb_typeof(details::jsonb) = 'string' THEN trim(both '"' from details::text)
                        ELSE left(details::text, 32)
                    END
                )
            SQL);
        } else {
            $rows = DB::table('logs')->select('id', 'details')->get();

            foreach ($rows as $row) {
                $normalized = $this->normalizeDetails($row->details);

                if ($normalized !== $row->details) {
                    DB::table('logs')->where('id', $row->id)->update(['details' => $normalized]);
                }
            }

            Schema::table('logs', function (Blueprint $table) {
                $table->string('details', 32)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('logs') || ! Schema::hasColumn('logs', 'details')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE logs ALTER COLUMN details TYPE json USING to_jsonb(details)');
        } else {
            Schema::table('logs', function (Blueprint $table) {
                $table->json('details')->nullable()->change();
            });
        }
    }

    private function normalizeDetails(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return isset($value['method']) ? strtoupper((string) $value['method']) : null;
        }

        if (! is_string($value)) {
            return is_scalar($value) ? strtoupper((string) $value) : null;
        }

        $decoded = json_decode($value, true);

        if (is_array($decoded) && isset($decoded['method'])) {
            return strtoupper((string) $decoded['method']);
        }

        if (is_string($decoded)) {
            return strtoupper($decoded);
        }

        return strtoupper($value);
    }
};
