@php
    $isEdit = $user->exists;

    $roleOptions = collect($roles)
        ->mapWithKeys(fn (App\Enums\UserRole $role) => [$role->value => $role->label()])
        ->all();
@endphp

<div class="row">
    <div class="col-md-6">
        <x-form.input
            label="Nama Lengkap"
            name="name"
            :value="$user->name"
            placeholder="Contoh: Diana Putri"
            required
        />
    </div>

    <div class="col-md-6">
        <x-form.input
            label="Email"
            name="email"
            type="email"
            :value="$user->email"
            placeholder="nama@email.com"
            autocomplete="email"
            required
        />
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <x-form.input
            label="Nomor WhatsApp (opsional)"
            name="phone"
            type="tel"
            :value="$user->phone"
            placeholder="08xxxxxxxxxx"
            hint="Dipakai untuk komunikasi terkait pesanan."
        />
    </div>

    <div class="col-md-6">
        <x-form.select
            label="Peran"
            name="role"
            :options="$roleOptions"
            :value="$user->role?->value"
            placeholder="Pilih peran"
            required
        />
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <x-form.input
            label="Password"
            name="password"
            type="password"
            :required="! $isEdit"
            :hint="$isEdit ? 'Kosongkan bila tidak ingin mengganti password.' : 'Minimal 8 karakter.'"
            autocomplete="new-password"
        />
    </div>

    <div class="col-md-6">
        <x-form.input
            label="Konfirmasi Password"
            name="password_confirmation"
            type="password"
            :required="! $isEdit"
            autocomplete="new-password"
        />
    </div>
</div>
