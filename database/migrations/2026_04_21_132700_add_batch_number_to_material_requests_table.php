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
        Schema::table('material_requests', function (Blueprint $blueprint) {
            // Adds the batch_number column. 
            // We use string because the code generates "BATCH-xxxx"
            $blueprint->string('batch_number')->nullable()->after('req_number')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('material_requests', function (Blueprint $blueprint) {
            $blueprint->dropColumn('batch_number');
        });
    }
};