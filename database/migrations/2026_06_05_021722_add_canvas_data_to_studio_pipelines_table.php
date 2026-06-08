<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studio_pipelines', function (Blueprint $table) {
            $table->json('canvas_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('studio_pipelines', function (Blueprint $table) {
            $table->dropColumn('canvas_data');
        });
    }
};
