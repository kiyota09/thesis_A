<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            // Make interest_fabric nullable
            $table->string('interest_fabric')->nullable()->change();
            
            // Make estimated_value nullable with default 0
            $table->decimal('estimated_value', 15, 2)->nullable()->default(0)->change();
        });
    }

    public function down()
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->string('interest_fabric')->nullable(false)->change();
            $table->decimal('estimated_value', 15, 2)->nullable(false)->default(0)->change();
        });
    }
};