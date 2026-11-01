{{--
    Layout dashboard host & owner.
    Section: title, brand (teks kecil di bawah logo), nav (daftar link), content.
--}}
@extends('layouts.site')

@section('body-class', 'min-h-screen bg-cream')
@section('noindex', true)

@section('content')
    <div x-data="toggle" class="min-h-screen">
        <header class="no-print sticky top-0 z-40 border-b border-wood/10 bg-cream/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="@yield('home-url', '/')" class="shrink-0">
                        @include('partials.logo', ['class' => 'h-9 w-auto sm:h-10'])
                    </a>
                    <p class="hidden truncate border-l border-wood/15 pl-3 text-xs text-muted sm:block">@yield('brand')</p>
                </div>

                <nav class="hidden items-center gap-1 md:flex" aria-label="Menu utama">
                    @yield('nav')
                </nav>

                <button type="button" class="rounded-lg p-2 text-wood-dark md:hidden" @click="toggle" aria-label="Buka menu">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                </button>
            </div>
            <nav x-show="open" x-cloak class="flex flex-col gap-1 border-t border-wood/10 px-4 py-3 md:hidden" aria-label="Menu mobile">
                @yield('nav')
            </nav>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            @include('partials.flash')
            @yield('panel')
        </main>
    </div>
@endsection
