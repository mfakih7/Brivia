<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\HomepageContent;
use App\Models\TeamMember;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        return view('public.about', [
            'profile' => CompanyProfile::current(),
            'team' => TeamMember::published()->with('portrait')->ordered()->get(),
            'steps' => HomepageContent::current()->process_steps ?? [],
        ]);
    }
}
