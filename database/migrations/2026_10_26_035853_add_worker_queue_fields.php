<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_online')->default(false)->after('role_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('worker_id')->nullable()->after('user_id')->index();
            $table->timestamp('worker_claimed_at')->nullable()->after('worker_id');
            $table->foreign('worker_id')->references('id')->on('users')->nullOnDelete();
        });

        DB::table('order_statuses')->insertOrIgnore([
            'code' => 'queued',
            'name' => 'Menunggu Worker',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $queuedId = DB::table('order_statuses')->where('code', 'queued')->value('id');
        $waitingVerificationId = DB::table('order_statuses')->where('code', 'waiting_verification')->value('id');

        if ($queuedId !== null && $waitingVerificationId !== null) {
            DB::table('orders')
                ->where('order_status_id', $queuedId)
                ->update(['order_status_id' => $waitingVerificationId]);

            DB::table('order_status_histories')
                ->where('from_order_status_id', $queuedId)
                ->update(['from_order_status_id' => $waitingVerificationId]);

            DB::table('order_status_histories')
                ->where('to_order_status_id', $queuedId)
                ->update(['to_order_status_id' => $waitingVerificationId]);
        }

        if ($queuedId !== null) {
            DB::table('order_statuses')->where('id', $queuedId)->delete();
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['worker_id']);
            $table->dropIndex(['worker_id']);
            $table->dropColumn(['worker_id', 'worker_claimed_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_online');
        });
    }
};
