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
        Schema::create('payroll_configs', function (Blueprint $table) {
            $table->id();
            $table->string('start_year_month'); // e.g., "2025-03"
            $table->integer('first_start_day');
            $table->integer('first_end_day');
            $table->integer('second_start_day');
            $table->integer('second_end_day');
            $table->integer('pay_date_offset');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_configs');
    }
};
