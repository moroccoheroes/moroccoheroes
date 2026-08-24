<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Passage de embedding JSON -> colonne native pgvector.
 *
 * En JSON il faut charger toutes les lignes en PHP pour calculer la
 * similarite : ca tient pour 50 heros, ca s'ecroule a 5000. Avec pgvector
 * la recherche se fait dans Postgres avec un index HNSW.
 *
 * Migration ignoree si le driver n'est pas PostgreSQL (dev en SQLite/MySQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        DB::statement('ALTER TABLE hero_chunks ADD COLUMN IF NOT EXISTS embedding_vector vector(1536)');
        DB::statement('CREATE INDEX IF NOT EXISTS hero_chunks_embedding_idx
                       ON hero_chunks USING hnsw (embedding_vector vector_cosine_ops)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS hero_chunks_embedding_idx');
        DB::statement('ALTER TABLE hero_chunks DROP COLUMN IF EXISTS embedding_vector');
    }
};
