@extends('layouts.auth')

@section('title', 'Masuk')
@section('subtitle', 'Masuk untuk melanjutkan pesananmu.')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate data-loading>
        @csrf

        <div class="login-form-group">
            <label for="email" class="login-form-label">Alamat Email</label>
            <div class="login-input-group">
                <i class="bi bi-envelope input-icon"></i>
                <input type="email" id="email" name="email"
                       value="{{ old('email') }}"
                       class="login-input @error('email') is-invalid-custom @enderror"
                       placeholder="nama@email.com"
                       autocomplete="username" autofocus required>
            </div>
            @error('email')
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
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                       autocomplete="current-password" required>
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

        <div class="login-options">
            <label class="custom-control-label">
                <input type="checkbox" class="custom-checkbox-input" name="remember" value="1">
                <span>Ingat saya</span>
            </label>
            <a href="{{ route('password.request') }}" class="forgot-password-link">Lupa password?</a>
        </div>

        <button type="submit" class="btn-login">
            <span>Masuk ke Dashboard</span>
            <i class="bi bi-arrow-right"></i>
        </button>
    </form>
@endsection

@section('footer')
    Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
@endsection
