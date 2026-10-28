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
        Schema::table('order_files', function (Blueprint $table): void {
            $table->dropForeign(['uploaded_by']);
        });

        Schema::table('order_files', function (Blueprint $table): void {
            $table->foreignId('uploaded_by')->nullable()->change();
        });

        Schema::table('order_files', function (Blueprint $table): void {
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_files', function (Blueprint $table): void {
            $table->dropForeign(['uploaded_by']);
        });

        Schema::table('order_files', function (Blueprint $table): void {
            $table->foreignId('uploaded_by')->nullable(false)->change();
        });

        Schema::table('order_files', function (Blueprint $table): void {
            $table->foreign('uploaded_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
