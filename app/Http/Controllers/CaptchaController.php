<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Support\Captcha;
use Illuminate\View\View;

class CaptchaController extends Controller
{
    /** Sajikan SVG captcha (tanpa cache, tidak pernah di-cache proxy). */
    public function show()
    {
        $svg = Captcha::svg();

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
