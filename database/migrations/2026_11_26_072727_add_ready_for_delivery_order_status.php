<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('order_statuses')->insertOrIgnore([
            'code' => 'ready_for_delivery',
            'name' => 'Siap Dikirim',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $readyId = DB::table('order_statuses')->where('code', 'ready_for_delivery')->value('id');
        $completedId = DB::table('order_statuses')->where('code', 'completed')->value('id');

        if ($readyId === null || $completedId === null) {
            return;
        }

        DB::table('order_status_histories')
            ->where('from_order_status_id', $completedId)
            ->update(['from_order_status_id' => $readyId]);

        DB::table('order_status_histories')
            ->where('to_order_status_id', $completedId)
            ->update(['to_order_status_id' => $readyId]);

        DB::table('orders')
            ->where('order_status_id', $completedId)
            ->update(['order_status_id' => $readyId]);
    }

    public function down(): void
    {
        $readyId = DB::table('order_statuses')->where('code', 'ready_for_delivery')->value('id');
        $completedId = DB::table('order_statuses')->where('code', 'completed')->value('id');

        if ($readyId === null || $completedId === null) {
            return;
        }

        DB::table('orders')
            ->where('order_status_id', $readyId)
            ->update(['order_status_id' => $completedId]);

        DB::table('order_status_histories')
            ->where('from_order_status_id', $readyId)
            ->update(['from_order_status_id' => $completedId]);

        DB::table('order_status_histories')
            ->where('to_order_status_id', $readyId)
            ->update(['to_order_status_id' => $completedId]);

        DB::table('order_statuses')->where('id', $readyId)->delete();
    }
};
