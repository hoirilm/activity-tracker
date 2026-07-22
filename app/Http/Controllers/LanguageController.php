<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function switch(Request $request, string $locale)
    {
        $supported = ['id', 'en'];

        if (!in_array($locale, $supported)) {
            abort(400);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
