<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('duplicate_candidates', function (Blueprint $table) {
            $table->foreignId('import_log_id')->nullable()->constrained('import_logs')->onDelete('cascade');
            $table->index('import_log_id');
        });
    }

    public function down(): void
    {
        Schema::table('duplicate_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('import_log_id');
        });
    }
};
