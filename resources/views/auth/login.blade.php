@extends('layouts.guest')

@section('title', 'Вход')

@section('subtitle', 'Войдите в свой аккаунт')

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf

    <!-- Email -->
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus class="input-field">
        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Пароль -->
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
        <input type="password" name="password" required class="input-field">
        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Запомнить меня -->
    <div class="flex items-center justify-between mb-6">
        <label class="flex items-center">
            <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600">
            <span class="ml-2 text-sm text-gray-600">Запомнить меня</span>
        </label>
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">Забыли пароль?</a>
        @endif
    </div>

    <button type="submit" class="auth-btn w-full">Войти</button>

    <div class="text-center mt-4 text-sm text-gray-600">
        Нет аккаунта? <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Зарегистрироваться</a>
    </div>
</form>
@endsection