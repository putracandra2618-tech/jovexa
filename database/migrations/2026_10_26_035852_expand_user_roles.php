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

        DB::table('roles')->insertOrIgnore([
            ['code' => 'super_admin', 'name' => 'Super Admin', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'admin', 'name' => 'Admin', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'worker', 'name' => 'Worker', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $adminId = DB::table('roles')->where('code', 'admin')->value('id');
        $legacyId = DB::table('roles')->where('code', 'admin_worker')->value('id');

        if ($adminId !== null && $legacyId !== null) {
            DB::table('users')
                ->where('role_id', $legacyId)
                ->update(['role_id' => $adminId]);
        }

        if ($legacyId !== null) {
            DB::table('roles')->where('id', $legacyId)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $now = now();

        DB::table('roles')->insertOrIgnore([
            ['code' => 'admin_worker', 'name' => 'Admin/Worker', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $legacyId = DB::table('roles')->where('code', 'admin_worker')->value('id');

        foreach (['super_admin', 'admin', 'worker'] as $code) {
            $roleId = DB::table('roles')->where('code', $code)->value('id');

            if ($legacyId !== null && $roleId !== null) {
                DB::table('users')
                    ->where('role_id', $roleId)
                    ->update(['role_id' => $legacyId]);
            }

            if ($roleId !== null) {
                DB::table('roles')->where('id', $roleId)->delete();
            }
        }
    }
};
