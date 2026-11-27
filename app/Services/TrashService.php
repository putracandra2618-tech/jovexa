<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderStatusHistory;
use App\Models\RevisionRequest;
use App\Models\Service;
use App\Models\User;
use App\Models\UserImpersonation;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrashService
{
    /**
     * Send an order and every record it owns to the trash.
     */
    public function softDeleteOrder(Order $order, ?string $deletedAt = null): void
    {
        $timestamp = $deletedAt ?? now()->format('Y-m-d H:i:s');

        DB::transaction(function () use ($order, $timestamp): void {
            $order->files()->whereNull('deleted_at')->update(['deleted_at' => $timestamp]);
            $order->payments()->whereNull('deleted_at')->update(['deleted_at' => $timestamp]);
            $order->statusHistory()->whereNull('deleted_at')->update(['deleted_at' => $timestamp]);
            $order->revisionRequests()->whereNull('deleted_at')->update(['deleted_at' => $timestamp]);
            UserNotification::query()
                ->where('order_id', $order->getKey())
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $timestamp]);

            $order->forceFill(['deleted_at' => $timestamp])->save();
        });
    }

    /**
     * Restore an order and the records trashed together with it.
     */
    public function restoreOrder(Order $order): void
    {
        $timestamp = $order->deleted_at?->format('Y-m-d H:i:s');

        DB::transaction(function () use ($order, $timestamp): void {
            if ($timestamp !== null) {
                $order->files()->withTrashed()->where('deleted_at', $timestamp)->restore();
                $order->payments()->withTrashed()->where('deleted_at', $timestamp)->restore();
                $order->statusHistory()->withTrashed()->where('deleted_at', $timestamp)->restore();
                $order->revisionRequests()->withTrashed()->where('deleted_at', $timestamp)->restore();
                UserNotification::query()
                    ->withTrashed()
                    ->where('order_id', $order->getKey())
                    ->where('deleted_at', $timestamp)
                    ->restore();
            }

            $order->restore();
        });
    }

    /**
     * Permanently remove an order, its records, and its uploaded files.
     */
    public function forceDeleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            foreach ($order->files()->withTrashed()->get() as $file) {
                if ($file->file_path !== '') {
                    Storage::disk('local')->delete($file->file_path);
                }
            }

            $order->files()->withTrashed()->forceDelete();
            $order->payments()->withTrashed()->forceDelete();
            $order->statusHistory()->withTrashed()->forceDelete();
            $order->revisionRequests()->withTrashed()->forceDelete();
            UserNotification::query()->withTrashed()->where('order_id', $order->getKey())->forceDelete();

            $order->forceDelete();
        });
    }

    /**
     * Send an account, all of its orders, and its related records to the trash.
     */
    public function softDeleteUser(User $user): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');

        DB::transaction(function () use ($user, $timestamp): void {
            $orderIds = $user->orders()->pluck('id')
                ->merge($user->assignedOrders()->pluck('id'))
                ->unique();

            foreach (Order::query()->whereIn('id', $orderIds)->get() as $order) {
                $this->softDeleteOrder($order, $timestamp);
            }

            UserNotification::query()
                ->where('user_id', $user->getKey())
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $timestamp]);

            OrderFile::query()
                ->where('uploaded_by', $user->getKey())
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $timestamp]);

            OrderStatusHistory::query()
                ->where('changed_by', $user->getKey())
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $timestamp]);

            RevisionRequest::query()
                ->where('requested_by', $user->getKey())
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $timestamp]);

            UserImpersonation::query()
                ->where(function ($query) use ($user): void {
                    $query->where('impersonator_id', $user->getKey())
                        ->orWhere('impersonated_id', $user->getKey());
                })
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $timestamp]);

            DB::table('sessions')->where('user_id', $user->getKey())->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            $user->forceFill(['deleted_at' => $timestamp])->save();
        });
    }

    /**
     * Restore an account together with the records trashed alongside it.
     */
    public function restoreUser(User $user): void
    {
        $timestamp = $user->deleted_at?->format('Y-m-d H:i:s');

        DB::transaction(function () use ($user, $timestamp): void {
            if ($timestamp !== null) {
                $orders = Order::query()
                    ->withTrashed()
                    ->where('deleted_at', $timestamp)
                    ->where(function ($query) use ($user): void {
                        $query->where('user_id', $user->getKey())
                            ->orWhere('worker_id', $user->getKey());
                    })
                    ->get();

                foreach ($orders as $order) {
                    $this->restoreOrder($order);
                }

                UserNotification::query()
                    ->withTrashed()
                    ->where('user_id', $user->getKey())
                    ->where('deleted_at', $timestamp)
                    ->restore();

                OrderFile::query()
                    ->withTrashed()
                    ->where('uploaded_by', $user->getKey())
                    ->where('deleted_at', $timestamp)
                    ->restore();

                OrderStatusHistory::query()
                    ->withTrashed()
                    ->where('changed_by', $user->getKey())
                    ->where('deleted_at', $timestamp)
                    ->restore();

                RevisionRequest::query()
                    ->withTrashed()
                    ->where('requested_by', $user->getKey())
                    ->where('deleted_at', $timestamp)
                    ->restore();

                UserImpersonation::query()
                    ->withTrashed()
                    ->where('deleted_at', $timestamp)
                    ->where(function ($query) use ($user): void {
                        $query->where('impersonator_id', $user->getKey())
                            ->orWhere('impersonated_id', $user->getKey());
                    })
                    ->restore();
            }

            $user->restore();
        });
    }

    /**
     * Permanently remove an account and everything it owns.
     */
    public function forceDeleteUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $orders = Order::query()
                ->withTrashed()
                ->where('user_id', $user->getKey())
                ->orWhere('worker_id', $user->getKey())
                ->get();

            foreach ($orders as $order) {
                $this->forceDeleteOrder($order);
            }

            UserNotification::query()->withTrashed()->where('user_id', $user->getKey())->forceDelete();

            foreach (OrderFile::query()->withTrashed()->where('uploaded_by', $user->getKey())->get() as $file) {
                if ($file->file_path !== '') {
                    Storage::disk('local')->delete($file->file_path);
                }

                $file->forceDelete();
            }

            OrderStatusHistory::query()->withTrashed()->where('changed_by', $user->getKey())->forceDelete();
            RevisionRequest::query()->withTrashed()->where('requested_by', $user->getKey())->forceDelete();

            UserImpersonation::query()
                ->withTrashed()
                ->where(function ($query) use ($user): void {
                    $query->where('impersonator_id', $user->getKey())
                        ->orWhere('impersonated_id', $user->getKey());
                })
                ->forceDelete();

            DB::table('sessions')->where('user_id', $user->getKey())->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            $user->forceDelete();
        });
    }

    /**
     * Send a service and the orders placed for it to the trash.
     */
    public function softDeleteService(Service $service): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');

        DB::transaction(function () use ($service, $timestamp): void {
            foreach ($service->orders()->get() as $order) {
                $this->softDeleteOrder($order, $timestamp);
            }

            $service->forceFill(['deleted_at' => $timestamp])->save();
        });
    }

    /**
     * Restore a service and the orders trashed together with it.
     */
    public function restoreService(Service $service): void
    {
        $timestamp = $service->deleted_at?->format('Y-m-d H:i:s');

        DB::transaction(function () use ($service, $timestamp): void {
            if ($timestamp !== null) {
                $orders = Order::query()
                    ->withTrashed()
                    ->where('deleted_at', $timestamp)
                    ->where('service_id', $service->getKey())
                    ->get();

                foreach ($orders as $order) {
                    $this->restoreOrder($order);
                }
            }

            $service->restore();
        });
    }

    /**
     * Permanently remove a service and the orders placed for it.
     */
    public function forceDeleteService(Service $service): void
    {
        DB::transaction(function () use ($service): void {
            foreach ($service->orders()->withTrashed()->get() as $order) {
                $this->forceDeleteOrder($order);
            }

            $service->forceDelete();
        });
    }
}
