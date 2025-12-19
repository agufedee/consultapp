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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('appointment_id')->constrained('appointments')->nullable();
            $table->dateTime('measurement_date')->useCurrent();
            $table->float('weight')->nullable();
            $table->float('height')->nullable();
            $table->float('bmi')->nullable();
            $table->float('waist')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'measurement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
