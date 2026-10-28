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
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('tripay_reference')->nullable()->unique()->after('transaction_id');
            $table->string('checkout_url')->nullable()->after('tripay_reference');
            $table->timestamp('expires_at')->nullable()->after('status');
            $table->unsignedBigInteger('amount_received')->nullable()->after('amount');
            $table->unsignedBigInteger('total_fee')->nullable()->after('amount_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['tripay_reference']);
            $table->dropColumn([
                'tripay_reference',
                'checkout_url',
                'expires_at',
                'amount_received',
                'total_fee',
            ]);
        });
    }
};
