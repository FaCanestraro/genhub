<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY = ['image', 'video', 'carousel', 'text'];
    private const WITH_PRINT = ['image', 'video', 'carousel', 'text', 'cartaz', 'tabloide'];

    /**
     * Cartaz e tabloide são formatos de impressão gerados como imagem, mas precisam de um
     * tipo próprio na geração para o prompt e o histórico distinguirem um do outro.
     */
    public function up(): void
    {
        $this->applyTypes(self::WITH_PRINT);
    }

    public function down(): void
    {
        DB::statement("UPDATE generations SET type = 'image' WHERE type IN ('cartaz', 'tabloide')");

        $this->applyTypes(self::LEGACY);
    }

    /**
     * No MySQL a coluna é um enum E tem um CHECK constraint com a mesma lista — os dois
     * precisam ser atualizados, senão o insert falha no constraint mesmo com o enum certo.
     * Fora do MySQL (os testes rodam em SQLite :memory:) não existe MODIFY COLUMN nem
     * information_schema, e o Laravel recria a tabela com o enum novo.
     */
    private function applyTypes(array $types): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('generations', function (Blueprint $table) use ($types) {
                $table->enum('type', $types)->default('image')->change();
            });

            return;
        }

        $list = collect($types)->map(fn ($t) => "'{$t}'")->join(', ');

        DB::statement("ALTER TABLE generations MODIFY COLUMN type ENUM({$list}) NOT NULL DEFAULT 'image'");

        // O CHECK só existe em bancos criados por certas versões do schema — daí a
        // checagem antes de remover, para a migration valer em qualquer ambiente.
        if ($this->hasTypeCheck()) {
            DB::statement('ALTER TABLE generations DROP CHECK generations_type_check');
        }

        DB::statement("ALTER TABLE generations ADD CONSTRAINT generations_type_check CHECK (type IN ({$list}))");
    }

    private function hasTypeCheck(): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'generations')
            ->where('CONSTRAINT_NAME', 'generations_type_check')
            ->exists();
    }
};
