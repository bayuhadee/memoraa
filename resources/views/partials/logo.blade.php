{{--
    Logo Memoraa.
    variant: color (latar terang) | light (latar gelap)
--}}
@php($variant = $variant ?? 'color')
<img src="{{ asset('images/brand/'.($variant === 'light' ? 'logo-light.png' : 'logo.png')) }}"
     alt="{{ config('app.name') }} — Digital Guestbook"
     width="575" height="160"
     class="{{ $class ?? 'h-10 w-auto' }}">
