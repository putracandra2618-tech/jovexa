<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Order;
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
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User,
            'roles' => $this->assignableRoles(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::query()->create($request->userAttributes());

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$user->name} berhasil ditambahkan.");
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): View|RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Akun Super Admin tidak dapat diubah dari halaman ini.');
        }

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles(),
        ]);
    }

    /**
     * Update a user's profile and role.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Akun Super Admin tidak dapat diubah dari halaman ini.');
        }

        $newRole = $request->role();

        if ($user->role !== $newRole && $user->isWorker() && Order::query()->activeForWorker($user)->exists()) {
            return back()->with('error', 'Worker masih memiliki pesanan aktif. Selesaikan atau batalkan pesanan terlebih dahulu.');
        }

        $user->forceFill($request->userAttributes())->save();
        $user->unsetRelation('roleRecord');

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Akun {$user->name} berhasil diperbarui.");
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

    /**
     * Show the form for editing the user's profile.
     */
    public function editProfile(): View
    {
        return view('admin.profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the user's profile password.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = auth()->user();

        if ($request->password) {
            if (!Hash::check($request->old_password, $user->password)) {
                return back()->with('error', 'Password lama salah.');
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return redirect()
                ->route('profile.edit')
                ->with('success', 'Password berhasil diubah.');
        }

        return back()->with('info', 'Password tidak diubah.');
    }

    /**
     * Roles that can be assigned from the management screens.
     *
     * @return array<int, UserRole>
     */
    private function assignableRoles(): array
    {
        return [
            UserRole::Customer,
            UserRole::Worker,
            UserRole::Admin,
        ];
    }
}
