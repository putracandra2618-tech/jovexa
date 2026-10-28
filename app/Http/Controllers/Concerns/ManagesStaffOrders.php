<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\OrderStatus;
use App\Models\Lookup\OrderFileKind as OrderFileKindLookup;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait ManagesStaffOrders
{
    /**
     * Move an assigned order to another status.
     */
    protected function transitionStatus(Order $order, OrderStatus $target, ?string $note, User $actor): RedirectResponse
    {
        $this->ensureCanManageOrder($order);

        $this->workflow->transitionTo($order, $target, $actor, $note);

        return back()->with('success', "Status pesanan diubah ke {$order->fresh()->status->label()}.");
    }

    /**
     * Ask the customer for the information needed to continue the order.
     */
    protected function handleInformationRequest(Request $request, Order $order): RedirectResponse
    {
        $this->ensureCanManageOrder($order);

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'note.required' => 'Tuliskan informasi yang dibutuhkan customer.',
            'note.min' => 'Tuliskan minimal 10 karakter agar customer paham.',
        ]);

        $this->workflow->transitionTo(
            $order,
            OrderStatus::WaitingCustomer,
            $request->user(),
            $validated['note'],
        );

        return back()->with('success', 'Permintaan informasi dikirim ke customer.');
    }

    /**
     * Download a file attached to the order.
     */
    protected function downloadOrderFile(Order $order, OrderFile $file): BinaryFileResponse
    {
        $this->ensureCanManageOrder($order);

        abort_unless($file->order_id === $order->getKey(), 404);

        return $this->downloads->download($file);
    }

    /**
     * Show the form to deliver the finished work.
     */
    protected function showDeliverForm(Order $order): View
    {
        $this->ensureCanManageOrder($order);

        abort_unless($order->status === OrderStatus::Completed, 404);

        return view($this->viewPrefix.'.deliver', [
            'order' => $order->load('service'),
        ]);
    }

    /**
     * Attach a result file to an order.
     */
    protected function storeOrderResult(Request $request, Order $order): RedirectResponse
    {
        $this->ensureCanManageOrder($order);

        abort_unless($order->status === OrderStatus::Completed, 404);

        $request->validate([
            'result' => ['required', 'file', 'max:20480', 'mimes:'.implode(',', OrderFile::ALLOWED_EXTENSIONS)],
        ], [
            'result.required' => 'Pilih file hasil pekerjaan.',
            'result.max' => 'Ukuran file maksimal 20 MB.',
            'result.mimes' => 'Format file hasil tidak didukung.',
        ]);

        $file = $request->file('result');
        $path = $file->store("orders/{$order->getKey()}/results", 'local');

        $order->files()->create([
            'file_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'file_path' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'order_file_kind_id' => OrderFileKindLookup::idFor(OrderFile::KIND_RESULT),
            'uploaded_by' => $request->user()->getKey(),
        ]);

        return back()->with('success', 'Hasil pekerjaan berhasil diunggah.');
    }

    /**
     * Send the finished work to the customer.
     */
    protected function sendOrderToCustomer(Request $request, Order $order): RedirectResponse
    {
        $this->ensureCanManageOrder($order);

        abort_unless($order->status === OrderStatus::Completed, 404);

        if (! $order->results()->exists()) {
            throw ValidationException::withMessages([
                'result' => 'Unggah hasil pekerjaan sebelum mengirim ke customer.',
            ]);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->transitionTo(
            $order,
            OrderStatus::Delivered,
            $request->user(),
            $validated['note'] ?? 'Hasil pekerjaan dikirim ke customer.',
        );

        return back()->with('success', 'Hasil pekerjaan berhasil dikirim ke customer.');
    }

    /**
     * Cancel an order on behalf of the customer.
     */
    protected function cancelOrder(Request $request, Order $order): RedirectResponse
    {
        $this->ensureCanManageOrder($order);

        abort_if(
            $order->status->isFinal(),
            403,
            'Pesanan yang sudah final tidak bisa dibatalkan.'
        );

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->transitionTo(
            $order,
            OrderStatus::Cancelled,
            $request->user(),
            $validated['note'] ?? 'Pesanan dibatalkan oleh staff.',
        );

        return back()->with('success', 'Pesanan dibatalkan.');
    }

    /**
     * Reject access when the current staff member does not own the order.
     */
    abstract protected function ensureCanManageOrder(Order $order): void;
}
