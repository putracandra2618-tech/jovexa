<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Show the user management list.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('roleRecord')
            ->withCount(['orders', 'assignedOrders'])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->when($request->filled('role'), function ($query) use ($request): void {
                $role = UserRole::tryFrom($request->string('role')->toString());

                if ($role !== null) {
                    $query->whereRole($role);
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'search' => $search,
        ]);
    }

    /**
     * Update a user's role.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $auth = $request->user();

        if ($auth && $user->is($auth)) {
            return back()->with('error', 'Anda tidak boleh mengubah peran akun sendiri.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Akun Super Admin tidak dapat diubah dari halaman ini.');
        }

        if ($user->isWorker() && Order::query()->activeForWorker($user)->exists()) {
            return back()->with('error', 'Worker masih memiliki pesanan aktif. Selesaikan atau batalkan pesanan terlebih dahulu.');
        }

        $newRole = $request->role();

        $user->forceFill(['role_id' => Role::ensureIdFor($newRole->value, $newRole->label())])->save();
        $user->unsetRelation('roleRecord');

        return back()->with('success', "Peran {$user->name} berhasil diperbarui.");
    }

    /**
     * Delete a user account.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $auth = $request->user();

        if ($auth && $user->is($auth)) {
            return back()->with('error', 'Anda tidak boleh menghapus akun sendiri.');
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Akun Super Admin tidak dapat dihapus dari halaman ini.');
        }

        if ($user->isWorker() && Order::query()->activeForWorker($user)->exists()) {
            return back()->with('error', 'Worker masih memiliki pesanan aktif. Selesaikan atau batalkan pesanan terlebih dahulu.');
        }

        $user->delete();

        return back()->with('success', "Akun {$user->name} berhasil dihapus.");
    }
}
