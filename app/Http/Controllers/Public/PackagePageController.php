<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\View\View;

class PackagePageController extends Controller
{
    public function __invoke(): View
    {
        return view('public.packages', [
            'packages' => Package::published()->ordered()->get(),
        ]);
    }
}
