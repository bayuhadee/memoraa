@extends('layouts.site')

@section('noindex', true)

@section('content')
    <main class="flex min-h-screen items-center justify-center bg-espresso px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center text-cream">
                <a href="{{ route('home') }}" class="inline-block">
                    @include('partials.logo', ['variant' => 'light', 'class' => 'mx-auto h-16 w-auto'])
                </a>
                <p class="mt-2 text-sm text-cream/60">@yield('subtitle')</p>
            </div>
            <div class="rounded-2xl bg-cream p-6 shadow-2xl sm:p-8">
                @include('partials.flash', ['hideErrorSummary' => true])
                @yield('form')
            </div>
        </div>
    </main>
@endsection
