@extends('layouts.auth')

@section('title', 'Login Owner')
@section('subtitle', 'Panel admin')

@section('form')
    <form method="POST" action="{{ route('owner.login.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username" value="{{ old('email') }}" class="field">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="field-label">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="field">
            @error('password')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-muted">
            <input type="checkbox" name="remember" value="1" class="accent-wood-dark"> Ingat saya
        </label>
        <button type="submit" class="btn-primary w-full">Masuk</button>
    </form>
@endsection
