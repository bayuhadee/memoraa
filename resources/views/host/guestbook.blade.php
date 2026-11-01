@extends('layouts.panel')
@include('host._nav')
@section('title', 'Buku Tamu')

@section('panel')
    <div class="mb-6">
        <p class="eyebrow">Buku Tamu</p>
        <h1 class="mt-2 text-4xl text-wood-dark">Ucapan dari Tamu</h1>
        <p class="mt-1 text-sm text-muted">Menghapus ucapan tidak menghapus data tamu maupun fotonya.</p>
    </div>

    @if ($guests->isEmpty())
        <div class="card px-6 py-14 text-center text-muted">Belum ada tamu yang mengisi buku tamu.</div>
    @else
        <div class="grid gap-4">
            @foreach ($guests as $guest)
                <article class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <h2 class="font-serif text-xl text-wood-dark">{{ $guest->name }}</h2>
                        <p class="text-xs text-muted">{{ $guest->address }} · {{ $guest->created_at->timezone(config('galeri.timezone'))->translatedFormat('j M Y, H:i') }} · {{ $guest->photos_count }} foto</p>
                        @if ($guest->message)
                            <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ $guest->message }}</p>
                        @else
                            <p class="mt-2 text-sm italic text-muted">Ucapan telah dihapus.</p>
                        @endif
                    </div>
                    @if ($guest->message)
                        <form method="POST" action="{{ route('host.guestbook.destroy', $guest) }}" data-confirm="Hapus ucapan dari {{ $guest->name }}?" class="shrink-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm text-red-700 ring-1 ring-red-200 hover:bg-red-50">Hapus ucapan</button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $guests->links() }}</div>
    @endif
@endsection
