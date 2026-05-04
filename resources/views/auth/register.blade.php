@extends('layouts.guest')

@section('title', 'Регистрация')

@section('subtitle', 'Создайте новый аккаунт')

@section('content')
<style>
    .auth-card-modern {
        border-radius: 7px;
        transition: all 0.2s ease;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 0.5rem;
        letter-spacing: -0.2px;
    }
    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 0.95rem;
        font-family: 'Montserrat', sans-serif;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        transition: all 0.2s;
        outline: none;
    }
    .form-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    .btn-register {
        width: 100%;
        background: #2563eb;
        color: white;
        font-weight: 600;
        font-size: 1rem;
        padding: 0.85rem;
        border: none;
        border-radius: 40px;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 0.5rem;
        font-family: 'Montserrat', sans-serif;
    }
    .btn-register:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
    }
    .link-text {
        color: #2563eb;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        transition: color 0.2s;
    }
    .link-text:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
</style>

<div class="auth-card-modern">
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label class="form-label">Имя</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus class="form-input">
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="form-input">
            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" required class="form-input">
            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Подтвердите пароль</label>
            <input type="password" name="password_confirmation" required class="form-input">
        </div>

        <button type="submit" class="btn-register">Зарегистрироваться</button>
    </form>

    <div class="text-center mt-6 text-sm text-gray-500">
        Уже есть аккаунт? <a href="{{ route('login') }}" class="link-text font-medium">Войти</a>
    </div>
</div>
@endsection