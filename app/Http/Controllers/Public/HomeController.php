<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HomepageContent;
use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Project::published()->with(['category', 'cover.media'])->where('is_featured', true)->ordered()->limit(2)->get();

        if ($featured->count() < 2) {
            $featured = $featured->concat(
                Project::published()->with(['category', 'cover.media'])->whereKeyNot($featured->modelKeys())->newestFirst()->limit(2 - $featured->count())->get()
            );
        }

        return view('public.home', [
            'home' => HomepageContent::current(),
            'services' => Service::published()->ordered()->limit(8)->get(),
            'projects' => $featured,
            'packages' => Package::published()->orderByDesc('is_featured')->ordered()->limit(3)->get(),
        ]);
    }
}
