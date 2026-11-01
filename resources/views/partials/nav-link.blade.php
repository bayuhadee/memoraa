@php($active = request()->routeIs($pattern ?? $route))
<a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
   class="rounded-lg px-3 py-2 text-sm transition {{ $active ? 'bg-wood-dark text-cream' : 'text-wood-dark hover:bg-sand' }}">{{ $label }}</a>
