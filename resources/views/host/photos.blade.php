@extends('layouts.panel')
@include('host._nav')
@section('title', 'Foto Tamu')

@section('panel')
    <div class="mb-6">
        <p class="eyebrow">Galeri</p>
        <h1 class="mt-2 text-4xl text-wood-dark">Foto dari Tamu</h1>
    </div>

    @include('partials.admin-photo-grid', [
        'photos' => $photos,
        'bulkAction' => route('host.photos.bulk-destroy'),
        'singleRoute' => fn ($photo) => route('host.photos.destroy', $photo),
    ])
@endsection
