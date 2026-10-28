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
        Schema::create('user_impersonations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('impersonator_id')->nullable()->index();
            $table->foreignId('impersonated_id')->nullable()->index();
            $table->string('impersonator_name');
            $table->string('impersonator_email');
            $table->string('impersonated_name');
            $table->string('impersonated_email');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('impersonator_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('impersonated_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_impersonations');
    }
};
