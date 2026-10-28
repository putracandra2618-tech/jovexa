@extends('layouts.auth')

@section('title', 'Atur Password Baru')
@section('subtitle', 'Pilih password baru untuk akunmu.')

@section('content')
    <form method="POST" action="{{ route('password.store') }}" class="needs-validation" novalidate data-loading>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="login-form-group">
            <label for="email" class="login-form-label">Alamat Email</label>
            <div class="login-input-group">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" id="email" name="email"
                       value="{{ old('email', $email) }}"
                       class="login-input @error('email') is-invalid-custom @enderror"
                       autocomplete="username" autofocus required>
            </div>
            @error('email')
                <div class="form-feedback-custom invalid-custom">
                    <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                </div>
            @enderror
        </div>

        <div class="login-form-group">
            <label for="password" class="login-form-label">Password Baru</label>
            <div class="login-input-group">
                <i class="bi bi-shield-lock input-icon"></i>
                <input type="password" id="password" name="password"
                       class="login-input login-input-password @error('password') is-invalid-custom @enderror"
                       placeholder="Minimal 8 karakter"
                       autocomplete="new-password" required>
                <button type="button" class="password-toggle-btn" data-password-toggle="password"
                        aria-label="Tampilkan password">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            @error('password')
                <div class="form-feedback-custom invalid-custom">
                    <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                </div>
            @enderror
        </div>

        <div class="login-form-group">
            <label for="password_confirmation" class="login-form-label">Konfirmasi Password</label>
            <div class="login-input-group">
                <i class="bi bi-shield-check input-icon"></i>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="login-input"
                       placeholder="Ulangi password baru"
                       autocomplete="new-password" required>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <span>Simpan Password Baru</span>
            <i class="bi bi-arrow-right"></i>
        </button>
    </form>
@endsection
