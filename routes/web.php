<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\OrderController as CustomerOrderController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TripayNotificationController;
use App\Http\Controllers\Worker\AvailabilityController as WorkerAvailabilityController;
use App\Http\Controllers\Worker\DashboardController as WorkerDashboardController;
use App\Http\Controllers\Worker\OrderController as WorkerOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('jasa', [ServiceController::class, 'index'])->name('services.index');
Route::get('jasa/{service}', [ServiceController::class, 'show'])->name('services.show');

Route::post('notifikasi/tripay', TripayNotificationController::class)
    ->middleware('throttle:tripay-callback')
    ->name('tripay.notification');

Route::middleware(['auth', 'role:'.UserRole::Admin->value.','.UserRole::SuperAdmin->value])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('jasa', [AdminServiceController::class, 'index'])->name('services.index');
    Route::get('jasa/create', [AdminServiceController::class, 'create'])->name('services.create');
    Route::post('jasa', [AdminServiceController::class, 'store'])->name('services.store');
    Route::get('jasa/{service}/edit', [AdminServiceController::class, 'edit'])->name('services.edit');
    Route::put('jasa/{service}', [AdminServiceController::class, 'update'])->name('services.update');
    Route::patch('jasa/{service}/arsip', [AdminServiceController::class, 'archive'])->name('services.archive');
    Route::patch('jasa/{service}/tayang', [AdminServiceController::class, 'publish'])->name('services.publish');

    Route::get('pesanan', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('pesanan/revisi', [AdminOrderController::class, 'revision'])->name('orders.revision');
    Route::get('pesanan/riwayat', [AdminOrderController::class, 'history'])->name('orders.history');
    Route::get('pesanan/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('pesanan/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('pesanan/{order}/minta-informasi', [AdminOrderController::class, 'requestInformation'])->name('orders.information');
    Route::get('pesanan/{order}/selesai', [AdminOrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('pesanan/{order}/hasil', [AdminOrderController::class, 'storeResult'])->name('orders.result.store');
    Route::post('pesanan/{order}/kirim', [AdminOrderController::class, 'sendToCustomer'])->name('orders.send');
    Route::post('pesanan/{order}/batal', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('pesanan/{order}/file/{file}', [AdminOrderController::class, 'download'])->name('orders.file');

    Route::middleware(['role:'.UserRole::SuperAdmin->value])->group(function (): void {
        Route::get('pengguna', [UserController::class, 'index'])->name('users.index');
        Route::patch('pengguna/{user}/peran', [UserController::class, 'updateRole'])->name('users.role');
        Route::post('pengguna/{user}/login-sebagai', [ImpersonationController::class, 'store'])->name('users.impersonate');
        Route::delete('pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

Route::middleware(['auth', 'role:'.UserRole::Worker->value])->prefix('worker')->name('worker.')->group(function (): void {
    Route::get('/', WorkerDashboardController::class)->name('dashboard');
    Route::patch('status-online', WorkerAvailabilityController::class)->name('availability');

    Route::get('antrean', [WorkerOrderController::class, 'queue'])->name('queue.index');
    Route::get('pesanan', [WorkerOrderController::class, 'index'])->name('orders.index');
    Route::get('pesanan/riwayat', [WorkerOrderController::class, 'history'])->name('orders.history');
    Route::get('pesanan/{order}', [WorkerOrderController::class, 'show'])->name('orders.show');
    Route::post('pesanan/{order}/klaim', [WorkerOrderController::class, 'claim'])->name('orders.claim');
    Route::patch('pesanan/{order}/status', [WorkerOrderController::class, 'updateStatus'])->name('orders.status');
    Route::post('pesanan/{order}/minta-informasi', [WorkerOrderController::class, 'requestInformation'])->name('orders.information');
    Route::get('pesanan/{order}/selesai', [WorkerOrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('pesanan/{order}/hasil', [WorkerOrderController::class, 'storeResult'])->name('orders.result.store');
    Route::post('pesanan/{order}/kirim', [WorkerOrderController::class, 'sendToCustomer'])->name('orders.send');
    Route::get('pesanan/{order}/file/{file}', [WorkerOrderController::class, 'download'])->name('orders.file');
});

Route::middleware('auth')
    ->post('kembali-super-admin', [ImpersonationController::class, 'destroy'])
    ->name('impersonation.stop');

Route::middleware(['auth', 'role:'.UserRole::Customer->value])->prefix('dashboard')->name('customer.')->group(function (): void {
    Route::get('/', CustomerDashboardController::class)->name('dashboard');

    Route::get('pesanan', [CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('pesanan/{order}/bayar', [OrderPaymentController::class, 'create'])->name('orders.pay');
    Route::post('pesanan/{order}/bayar', [OrderPaymentController::class, 'store'])
        ->middleware('throttle:checkout')
        ->name('orders.pay.store');
    Route::get('pesanan/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
    Route::get('pesanan/{order}/file/{file}', [CustomerOrderController::class, 'download'])->name('orders.file');
    Route::post('pesanan/{order}/lampiran', [CustomerOrderController::class, 'addRequirements'])
        ->middleware('throttle:uploads')
        ->name('orders.requirements');
    Route::delete('pesanan/{order}', [CustomerOrderController::class, 'destroy'])->name('orders.destroy');
});

Route::middleware(['auth', 'role:'.UserRole::Customer->value])
    ->get('jasa/{service}/pesan', [CustomerOrderController::class, 'create'])
    ->name('orders.create');

Route::middleware(['auth', 'role:'.UserRole::Customer->value])
    ->post('jasa/{service}/pesan', [CustomerOrderController::class, 'store'])
    ->middleware('throttle:orders')
    ->name('orders.store');

require __DIR__.'/auth.php';
