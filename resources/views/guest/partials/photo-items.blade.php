@foreach ($photos as $photo)
    @include('partials.photo-tile', ['photo' => $photo])
@endforeach
