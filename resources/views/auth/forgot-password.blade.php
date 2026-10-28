@extends('layouts.auth')

@section('title', 'Lupa Password')
@section('subtitle', 'Kami akan mengirim tautan reset ke emailmu.')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="needs-validation" novalidate data-loading>
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

        <button type="submit" class="btn-login">
            <span>Kirim Tautan Reset</span>
            <i class="bi bi-arrow-right"></i>
        </button>
    </form>
@endsection

@section('footer')
        Ingat passwordnya? <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
@endsection
