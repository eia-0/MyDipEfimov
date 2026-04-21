@extends('layouts.guest')

@section('title', 'Регистрация')

@section('subtitle', 'Создайте новый аккаунт')

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf

    <!-- Имя -->
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
        <input type="text" name="name" value="{{ old('name') }}" required autofocus class="input-field">
        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Email -->
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required class="input-field">
        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Пароль -->
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
        <input type="password" name="password" required class="input-field">
        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <!-- Подтверждение пароля -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Подтвердите пароль</label>
        <input type="password" name="password_confirmation" required class="input-field">
    </div>

    <button type="submit" class="auth-btn w-full">Зарегистрироваться</button>

    <div class="text-center mt-4 text-sm text-gray-600">
        Уже есть аккаунт? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Войти</a>
    </div>
</form>
@endsection