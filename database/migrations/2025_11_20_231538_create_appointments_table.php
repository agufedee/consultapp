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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('send_reminder')->default(false);
            $table->string('reason', 500)->nullable();
            $table->dateTime('start_date')->index();
            $table->dateTime('end_date');
            $table->foreignId('status_id')->constrained('statuses');
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
