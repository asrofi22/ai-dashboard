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
        Schema::table('etl_pipelines', function (Blueprint $table) {
            $table->text('definition_prompt')->nullable();
            $table->text('generated_script')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etl_pipelines', function (Blueprint $table) {
            $table->dropColumn(['definition_prompt', 'generated_script']);
        });
    }
};
