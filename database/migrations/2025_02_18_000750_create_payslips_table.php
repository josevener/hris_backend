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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->onDelete('cascade');
            $table->foreignId('payroll_items_id')->constrained('payroll_items')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('salary_id')->constrained()->onDelete('cascade');
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('deductions', 12, 2)->nullable();
            $table->decimal('net_salary', 12, 2);
            $table->date('issued_date');
            $table->enum('payment_method', ['Bank Transfer', 'Cash', 'Check'])->default('Bank Transfer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
