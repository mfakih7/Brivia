<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HomepageContent;
use App\Models\Service;
use Illuminate\View\View;

class ServicePageController extends Controller
{
    public function index(): View
    {
        return view('public.services.index', [
            'services' => Service::published()->ordered()->get(),
            'steps' => HomepageContent::current()->process_steps ?? [],
        ]);
    }

    public function show(string $slug): View
    {
        $service = Service::published()->where('slug', $slug)->first();
        abort_unless($service, 404);

        return view('public.services.show', self::viewData($service));
    }

    /** Shared with authenticated preview; related records are always published-only. */
    public static function viewData(Service $service): array
    {
        return [
            'service' => $service,
            'projects' => $service->projects()->published()->with(['category', 'cover.media'])->newestFirst()->limit(3)->get(),
            'packages' => $service->packages()->published()->ordered()->get(),
        ];
    }
}
