<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Services\TrashService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrashController extends Controller
{
    public function __construct(private readonly TrashService $trash) {}

    /**
     * Show soft deleted user accounts.
     */
    public function users(): View
    {
        return $this->render('users', User::query()->onlyTrashed()->latest('deleted_at')->paginate(10, ['*'], 'users_page'));
    }

    /**
     * Show soft deleted services.
     */
    public function services(): View
    {
        return $this->render('services', Service::query()->onlyTrashed()->latest('deleted_at')->paginate(10, ['*'], 'services_page'));
    }

    /**
     * Restore a user and the records trashed alongside them.
     */
    public function restoreUser(User $user): RedirectResponse
    {
        $this->trash->restoreUser($user);

        return back()->with('success', "Akun {$user->name} berhasil dipulihkan.");
    }

    /**
     * Permanently delete a user and everything they own.
     */
    public function forceDeleteUser(User $user): RedirectResponse
    {
        $name = $user->name;

        $this->trash->forceDeleteUser($user);

        return back()->with('success', "Akun {$name} berhasil dihapus permanen.");
    }

    /**
     * Restore a service and its trashed orders.
     */
    public function restoreService(Service $service): RedirectResponse
    {
        $this->trash->restoreService($service);

        return back()->with('success', "Jasa \"{$service->name}\" berhasil dipulihkan.");
    }

    /**
     * Permanently delete a service and its orders.
     */
    public function forceDeleteService(Service $service): RedirectResponse
    {
        $name = $service->name;

        $this->trash->forceDeleteService($service);

        return back()->with('success', "Jasa \"{$name}\" berhasil dihapus permanen.");
    }

    /**
     * @param  LengthAwarePaginator<mixed>  $trashed
     */
    private function render(string $type, $trashed): View
    {
        return view('admin.trash.index', [
            'type' => $type,
            'trashed' => $trashed,
            'tabs' => [
                'users' => 'Pengguna',
                'services' => 'Jasa',
            ],
        ]);
    }
}
