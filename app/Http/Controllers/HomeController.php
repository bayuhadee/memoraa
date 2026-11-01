<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function __invoke()
    {
        $wa = config('galeri.whatsapp');

        return view('home', [
            'waUrl' => 'https://wa.me/'.$wa['number'].'?text='.rawurlencode($wa['message']),
            'waDisplay' => $wa['display'],
        ]);
    }
}
