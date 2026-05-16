<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add unique constraint on (project_a_id, project_b_id)
        // so upsert ON CONFLICT works correctly
        Schema::table('duplicate_candidates', function (Blueprint $table) {
            // Drop index if it already exists (idempotent)
            $table->unique(['project_a_id', 'project_b_id'], 'duplicate_candidates_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::table('duplicate_candidates', function (Blueprint $table) {
            $table->dropUnique('duplicate_candidates_pair_unique');
        });
    }
};
