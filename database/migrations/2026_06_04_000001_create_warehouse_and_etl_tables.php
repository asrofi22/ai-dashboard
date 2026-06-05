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
        Schema::create('warehouse_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('row_count')->default(0);
            $table->integer('col_count')->default(0);
            $table->string('source_system');
            $table->integer('quality_score')->default(100);
            $table->text('description')->nullable();
            $table->json('dashboards_used')->nullable();
            $table->json('key_columns')->nullable();
            $table->string('business_owner')->nullable();
            $table->timestamp('last_refresh')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('warehouse_tables')->onDelete('cascade');
            $table->string('name');
            $table->string('data_type');
            $table->string('is_nullable')->default('YES');
            $table->integer('distinct_count')->default(0);
            $table->decimal('missing_percentage', 5, 2)->default(0.00);
            $table->string('min_value')->nullable();
            $table->string('max_value')->nullable();
            $table->string('mean_value')->nullable();
            $table->timestamps();
        });

        Schema::create('etl_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('source_layer');
            $table->string('target_layer');
            $table->string('frequency')->default('Daily');
            $table->string('is_active')->default('active');
            $table->timestamps();
        });

        Schema::create('etl_job_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('etl_pipelines')->onDelete('cascade');
            $table->string('status'); // Success, Running, Failed, Warning
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->integer('rows_processed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('ai_failure_analysis')->nullable();
            $table->timestamps();
        });

        Schema::create('query_histories', function (Blueprint $table) {
            $table->id();
            $table->text('natural_query');
            $table->text('generated_sql');
            $table->string('execution_status')->default('success'); // success, failed
            $table->text('execution_error')->nullable();
            $table->string('chart_type')->nullable();
            $table->timestamps();
        });

        Schema::create('dq_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->string('finding_type');
            $table->text('finding_summary');
            $table->text('business_impact');
            $table->text('recommended_action');
            $table->string('priority_level')->default('Medium'); // High, Medium, Low
            $table->integer('quality_score_impact')->default(0);
            $table->string('is_resolved')->default('pending'); // pending, resolved
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dq_recommendations');
        Schema::dropIfExists('query_histories');
        Schema::dropIfExists('etl_job_runs');
        Schema::dropIfExists('etl_pipelines');
        Schema::dropIfExists('warehouse_columns');
        Schema::dropIfExists('warehouse_tables');
    }
};
