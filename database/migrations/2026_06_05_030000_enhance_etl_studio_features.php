<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add schedule_interval to studio_pipelines
        Schema::table('studio_pipelines', function (Blueprint $table) {
            $table->string('schedule_interval')->default('manual');
        });

        // 2. Add step_metrics to studio_pipeline_runs
        Schema::table('studio_pipeline_runs', function (Blueprint $table) {
            $table->json('step_metrics')->nullable();
        });

        // 3. Create studio_pipeline_versions
        Schema::create('studio_pipeline_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('studio_pipelines')->onDelete('cascade');
            $table->integer('version_number');
            $table->string('name');
            $table->foreignId('source_connection_id')->constrained('etl_connections')->onDelete('cascade');
            $table->string('source_table');
            $table->json('transformations')->nullable();
            $table->foreignId('target_connection_id')->constrained('etl_connections')->onDelete('cascade');
            $table->string('target_table');
            $table->json('column_mapping')->nullable();
            $table->json('canvas_data')->nullable();
            $table->string('schedule_interval')->default('manual');
            $table->timestamp('created_at')->useCurrent();
        });

        // 4. Create studio_reusable_templates
        Schema::create('studio_reusable_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // mapping, transform
            $table->json('config');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_reusable_templates');
        Schema::dropIfExists('studio_pipeline_versions');

        Schema::table('studio_pipeline_runs', function (Blueprint $table) {
            $table->dropColumn('step_metrics');
        });

        Schema::table('studio_pipelines', function (Blueprint $table) {
            $table->dropColumn('schedule_interval');
        });
    }
};
