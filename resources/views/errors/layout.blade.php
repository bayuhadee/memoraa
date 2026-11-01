@extends('layouts.site')
@section('noindex', true)
@section('content')
    <main class="flex min-h-screen items-center justify-center px-6 text-center">
        <div class="max-w-md">
            <a href="{{ route('home') }}" class="mb-10 inline-block">@include('partials.logo', ['class' => 'mx-auto h-12 w-auto'])</a>
            <p class="font-serif text-7xl text-gold">@yield('code')</p>
            <h1 class="mt-4 text-3xl text-wood-dark">@yield('heading')</h1>
            <p class="mt-3 text-sm text-muted">@yield('message')</p>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="btn-primary mt-8">Kembali</a>
        </div>
    </main>
@endsection
