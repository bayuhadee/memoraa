@extends('layouts.auth')

@section('title', 'Login Host')
@section('subtitle', 'Masuk untuk mengelola galeri acara Anda')

@section('form')
    <form method="POST" action="{{ route('host.login.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="event_code" class="field-label">ID Event</label>
            <input id="event_code" name="event_code" type="text" required autofocus autocapitalize="characters" autocomplete="username"
                   value="{{ old('event_code') }}" class="field uppercase" placeholder="mis. NIKAH-AB12">
            @error('event_code')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="field-label">Password Event</label>
            <input id="password" name="password" type="password" required maxlength="20" autocomplete="current-password"
                   class="field uppercase tracking-[0.4em]" placeholder="••••">
            @error('password')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn-primary w-full">Masuk</button>
        <p class="text-center text-xs text-muted">ID event & password diberikan oleh admin.</p>
    </form>
@endsection
