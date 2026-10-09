<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyProfileRequest;
use App\Http\Requests\Admin\HomepageRequest;
use App\Http\Requests\Admin\SiteSettingsRequest;
use App\Models\CompanyProfile;
use App\Models\HomepageContent;
use App\Models\SiteSetting;
use App\Support\AuditLogger;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** About (company profile), Homepage and public Settings singletons. */
class SingletonContentController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function editAbout(): View
    {
        return view('admin.singletons.about', ['profile' => CompanyProfile::current()]);
    }

    public function updateAbout(CompanyProfileRequest $request): RedirectResponse
    {
        $profile = CompanyProfile::current();
        $profile->fill($request->validated())->save();
        $this->audit->recordModelChanges('content.updated', 'company_profile', $profile);

        return back()->with('status', 'About content saved and live.');
    }

    public function editHomepage(): View
    {
        return view('admin.singletons.homepage', ['home' => HomepageContent::current(), 'routes' => HomepageContent::CTA_ROUTES, 'sections' => HomepageContent::SECTIONS]);
    }

    public function updateHomepage(HomepageRequest $request): RedirectResponse
    {
        $home = HomepageContent::current();
        $data = $request->validated();
        $data['section_visibility'] = collect(HomepageContent::SECTIONS)->map(fn ($label, $key) => (bool) ($data['section_visibility'][$key] ?? false))->all();
        $home->fill($data)->save();
        $this->audit->recordModelChanges('content.updated', 'homepage_content', $home);

        return back()->with('status', 'Homepage saved and live.');
    }

    public function editSettings(): View
    {
        return view('admin.singletons.settings', [
            'settings' => SiteSetting::current(),
            'timezones' => collect(DateTimeZone::listIdentifiers())->mapWithKeys(fn ($tz) => [$tz => str_replace('_', ' ', $tz)])->all(),
        ]);
    }

    public function updateSettings(SiteSettingsRequest $request): RedirectResponse
    {
        $settings = SiteSetting::current();
        $data = $request->validated();
        $data['social_links'] = array_filter($data['social_links'] ?? [], fn ($url) => filled($url));
        $settings->fill($data)->save();
        $this->audit->recordModelChanges('content.updated', 'site_settings', $settings);

        return back()->with('status', 'Settings saved and live.');
    }
}
