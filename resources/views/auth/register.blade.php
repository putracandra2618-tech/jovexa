@extends('layouts.auth')

@section('title', 'Daftar')
@section('subtitle', 'Buat akun untuk mulai memesan jasa.')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="needs-validation" novalidate data-loading>
        @csrf

        <div class="login-form-group">
            <label for="name" class="login-form-label">Nama Lengkap</label>
            <div class="login-input-group">
                <i class="bi bi-person input-icon"></i>
                <input type="text" id="name" name="name"
                       value="{{ old('name') }}"
                       class="login-input @error('name') is-invalid-custom @enderror"
                       placeholder="Nama sesuai identitas"
                       autocomplete="name" autofocus required>
            </div>
            @error('name')
                <div class="form-feedback-custom invalid-custom">
                    <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                </div>
            @enderror
        </div>

        <div class="login-form-group">
            <label for="email" class="login-form-label">Alamat Email</label>
            <div class="login-input-group">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" id="email" name="email"
                       value="{{ old('email') }}"
                       class="login-input @error('email') is-invalid-custom @enderror"
                       placeholder="nama@email.com"
                       autocomplete="username" required>
            </div>
            @error('email')
                <div class="form-feedback-custom invalid-custom">
                    <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                </div>
            @enderror
        </div>

        <div class="login-form-group">
            <label for="phone" class="login-form-label">Nomor WhatsApp <span class="text-muted">(opsional)</span></label>
            <div class="login-input-group">
                <i class="bi bi-phone input-icon"></i>
                <input type="tel" id="phone" name="phone"
                       value="{{ old('phone') }}"
                       class="login-input @error('phone') is-invalid-custom @enderror"
                       placeholder="08123456789"
                       autocomplete="tel">
            </div>
            @error('phone')
                <div class="form-feedback-custom invalid-custom">
                    <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                </div>
            @enderror
        </div>

        <div class="login-form-group">
            <label for="password" class="login-form-label">Password</label>
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

            <div class="jx-password-meter" data-password-strength="password"
                 data-password-confirm="password_confirmation">
                <div class="jx-password-bar"><span data-strength-bar data-level="0"></span></div>
                <div class="jx-password-hint">
                    <span data-strength-label></span>
                    <span data-password-match></span>
                </div>
            </div>
        </div>

        <div class="login-form-group">
            <label for="password_confirmation" class="login-form-label">Konfirmasi Password</label>
            <div class="login-input-group">
                <i class="bi bi-shield-check input-icon"></i>
                <input type="password" id="password_confirmation" name="password_confirmation"
                       class="login-input"
                       placeholder="Ulangi password"
                       autocomplete="new-password" required>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <span>Buat Akun</span>
            <i class="bi bi-arrow-right"></i>
        </button>
    </form>
@endsection

@section('footer')
    Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
@endsection
