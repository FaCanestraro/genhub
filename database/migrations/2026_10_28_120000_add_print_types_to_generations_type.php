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
     * Cada banco guarda "enum" de um jeito e precisa do seu próprio caminho:
     *
     * - MySQL: a coluna é um ENUM de verdade E tem um CHECK com a mesma lista. Os dois
     *   precisam ser atualizados, senão o insert falha no constraint mesmo com o enum certo.
     * - PostgreSQL: não existe enum do Laravel — é varchar(255) mais um CHECK à parte.
     *   Só o CHECK muda. Mandar `$table->enum()->change()` aqui gera
     *   `ALTER COLUMN type TYPE varchar(255) check (...)`, que o Postgres recusa:
     *   CHECK não pode vir embutido num ALTER COLUMN TYPE.
     * - SQLite (os testes rodam em :memory:): não há MODIFY COLUMN nem catálogo de
     *   constraints; o Laravel recria a tabela inteira com a lista nova.
     */
    private function applyTypes(array $types): void
    {
        $list = collect($types)->map(fn ($t) => "'{$t}'")->join(', ');
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Derruba o CHECK atual seja qual for o nome que ele recebeu, em vez de
            // supor `generations_type_check` — bancos criados por versões diferentes
            // do schema nem sempre usam a mesma nomenclatura.
            foreach ($this->pgTypeChecks() as $nome) {
                DB::statement("ALTER TABLE generations DROP CONSTRAINT \"{$nome}\"");
            }

            DB::statement("ALTER TABLE generations ADD CONSTRAINT generations_type_check CHECK (type IN ({$list}))");

            return;
        }

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            Schema::table('generations', function (Blueprint $table) use ($types) {
                $table->enum('type', $types)->default('image')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE generations MODIFY COLUMN type ENUM({$list}) NOT NULL DEFAULT 'image'");

        // O CHECK só existe em bancos criados por certas versões do schema — daí a
        // checagem antes de remover, para a migration valer em qualquer ambiente.
        if ($this->hasTypeCheck()) {
            DB::statement('ALTER TABLE generations DROP CHECK generations_type_check');
        }

        DB::statement("ALTER TABLE generations ADD CONSTRAINT generations_type_check CHECK (type IN ({$list}))");
    }

    /** Nomes dos CHECK que incidem sobre generations.type, qualquer que seja a nomenclatura. */
    private function pgTypeChecks(): array
    {
        $linhas = DB::select(<<<'SQL'
            SELECT c.conname
            FROM pg_constraint c
            JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
            WHERE c.conrelid = 'generations'::regclass
              AND c.contype = 'c'
              AND a.attname = 'type'
        SQL);

        return array_column($linhas, 'conname');
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
