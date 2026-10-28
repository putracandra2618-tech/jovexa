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
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('service_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('order_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('payment_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('order_file_kinds', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        $now = now();

        DB::table('roles')->insert([
            ['code' => 'customer', 'name' => 'Customer', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'admin_worker', 'name' => 'Admin/Worker', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('service_statuses')->insert([
            ['code' => 'draft', 'name' => 'Draf', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'published', 'name' => 'Tayang', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'archived', 'name' => 'Arsip', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('order_statuses')->insert([
            ['code' => 'pending_payment', 'name' => 'Menunggu Pembayaran', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'paid', 'name' => 'Sudah Dibayar', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'waiting_verification', 'name' => 'Menunggu Verifikasi', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'waiting_customer', 'name' => 'Menunggu Info Customer', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'in_progress', 'name' => 'Sedang Dikerjakan', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'revision', 'name' => 'Perlu Revisi', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'completed', 'name' => 'Selesai', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'delivered', 'name' => 'Sudah Dikirim', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'expired', 'name' => 'Kedaluwarsa', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'cancelled', 'name' => 'Dibatalkan', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('payment_statuses')->insert([
            ['code' => 'pending', 'name' => 'Menunggu Pembayaran', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'paid', 'name' => 'Berhasil', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'failed', 'name' => 'Gagal', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'expired', 'name' => 'Kedaluwarsa', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('order_file_kinds')->insert([
            ['code' => 'requirement', 'name' => 'Persyaratan', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'result', 'name' => 'Hasil', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_file_kinds');
        Schema::dropIfExists('payment_statuses');
        Schema::dropIfExists('order_statuses');
        Schema::dropIfExists('service_statuses');
        Schema::dropIfExists('roles');
    }
};
