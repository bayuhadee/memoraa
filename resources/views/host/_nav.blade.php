@section('home-url', route('host.dashboard'))
@section('brand', 'Host · '.$event->bride_groom_names)
@section('nav')
    @include('partials.nav-link', ['route' => 'host.dashboard', 'label' => 'Ringkasan'])
    @include('partials.nav-link', ['route' => 'host.photos.index', 'label' => 'Foto'])
    @include('partials.nav-link', ['route' => 'host.guestbook.index', 'label' => 'Buku Tamu'])
    @include('partials.nav-link', ['route' => 'host.zip.index', 'label' => 'Download ZIP'])
    <form method="POST" action="{{ route('host.logout') }}">
        @csrf
        <button type="submit" class="rounded-lg px-3 py-2 text-sm text-red-700 hover:bg-red-50">Keluar</button>
    </form>
@endsection
