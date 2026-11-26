<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('order_file_kinds')->insertOrIgnore([
            'code' => 'revision',
            'name' => 'Revisi',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $revisionId = DB::table('order_file_kinds')->where('code', 'revision')->value('id');
        $resultId = DB::table('order_file_kinds')->where('code', 'result')->value('id');

        if ($revisionId === null || $resultId === null) {
            return;
        }

        DB::table('order_files')
            ->where('order_file_kind_id', $revisionId)
            ->update(['order_file_kind_id' => $resultId]);

        DB::table('order_file_kinds')->where('id', $revisionId)->delete();
    }
};
