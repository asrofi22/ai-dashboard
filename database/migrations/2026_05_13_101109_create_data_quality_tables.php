<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('source_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // excel, csv, pgsql, mysql, google_sheets
            $table->json('config')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_connection_id')->constrained('source_connections')->onDelete('cascade');
            $table->string('status');
            $table->integer('total_records')->default(0);
            $table->integer('success_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->text('error_details')->nullable();
            $table->timestamps();
        });

        Schema::create('imported_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_connection_id')->constrained('source_connections')->onDelete('cascade');
            $table->foreignId('import_log_id')->constrained('import_logs')->onDelete('cascade');
            $table->string('external_id')->nullable();
            $table->text('original_name');
            $table->text('normalized_name');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Index for pg_trgm similarity search
            $table->index([DB::raw('normalized_name gin_trgm_ops')], 'idx_projects_normalized_name_trgm', 'gin');
        });

        Schema::create('duplicate_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_a_id')->constrained('imported_projects')->onDelete('cascade');
            $table->foreignId('project_b_id')->constrained('imported_projects')->onDelete('cascade');
            $table->decimal('similarity_score', 5, 4);
            $table->string('confidence_level');
            $table->string('status')->default('pending'); // pending, confirmed, rejected
            $table->string('ai_validation_status')->default('pending');
            $table->timestamps();
        });

        Schema::create('ai_validation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duplicate_candidate_id')->constrained('duplicate_candidates')->onDelete('cascade');
            $table->text('prompt')->nullable();
            $table->text('response')->nullable();
            $table->string('result')->nullable(); // SAME, POSSIBLY, DIFFERENT
            $table->text('reasoning')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->timestamps();
        });

        Schema::create('duplicate_review_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duplicate_candidate_id')->constrained('duplicate_candidates')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duplicate_review_history');
        Schema::dropIfExists('ai_validation_logs');
        Schema::dropIfExists('duplicate_candidates');
        Schema::dropIfExists('imported_projects');
        Schema::dropIfExists('import_logs');
        Schema::dropIfExists('source_connections');
    }
};
