<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('etl_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type'); // Database, File Source, Collaboration Platform
            $table->string('driver'); // pgsql, mysql, oracle, csv, excel, sharepoint
            $table->json('config')->nullable(); // host, port, database, username, password, file_path, folder_url, client_id, etc.
            $table->string('status')->default('active'); // active, inactive
            $table->json('metadata')->nullable(); // JSON containing tables/views/columns/primary keys/row counts or files
            $table->timestamps();
        });

        Schema::create('studio_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('source_connection_id')->constrained('etl_connections')->onDelete('cascade');
            $table->string('source_table'); // Table name or file name
            $table->json('transformations')->nullable(); // Ordered list of transformations: Remove Duplicate, Remove Null, Trim Text, Uppercase, Lowercase, Rename Column, Data Type Conversion, Filter Data, Custom SQL
            $table->foreignId('target_connection_id')->constrained('etl_connections')->onDelete('cascade');
            $table->string('target_table'); // Target table name
            $table->json('column_mapping')->nullable(); // JSON object mapping source columns to target columns
            $table->string('is_active')->default('active'); // active, inactive
            $table->timestamps();
        });

        Schema::create('studio_pipeline_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('studio_pipelines')->onDelete('cascade');
            $table->string('status')->default('Pending'); // Pending, Running, Success, Failed
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->integer('rows_read')->default(0);
            $table->integer('rows_written')->default(0);
            $table->integer('rows_rejected')->default(0);
            $table->text('execution_logs')->nullable(); // Step-by-step logs
            $table->text('error_log')->nullable();
            $table->json('ai_failure_analysis')->nullable(); // Gemini diagnostics
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studio_pipeline_runs');
        Schema::dropIfExists('studio_pipelines');
        Schema::dropIfExists('etl_connections');
    }
};
