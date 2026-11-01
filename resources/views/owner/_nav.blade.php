@section('home-url', route('owner.dashboard'))
@section('brand', 'Panel Owner')
@section('nav')
    @include('partials.nav-link', ['route' => 'owner.dashboard', 'label' => 'Dashboard'])
    @include('partials.nav-link', ['route' => 'owner.events.index', 'pattern' => 'owner.events.*', 'label' => 'Event'])
    @include('partials.nav-link', ['route' => 'owner.reports.index', 'pattern' => 'owner.reports.*', 'label' => 'Laporan'])
    <form method="POST" action="{{ route('owner.logout') }}">
        @csrf
        <button type="submit" class="rounded-lg px-3 py-2 text-sm text-red-700 hover:bg-red-50">Keluar</button>
    </form>
@endsection
