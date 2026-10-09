<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Service;
use App\Support\PublicContentCache;
use App\Support\PublicSite;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = PublicSite::indexable()
            ? ['User-agent: *', 'Disallow: /admin', 'Allow: /', '', 'Sitemap: '.route('sitemap')]
            : ['# Non-production environment: do not index.', 'User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** Published routes and resources only; drafts, previews and admin never appear. */
    public function sitemap(): Response
    {
        $xml = PublicContentCache::remember('sitemap', function () {
            $urls = collect([
                [route('home'), null], [route('services.index'), null], [route('packages'), null],
                [route('projects.index'), null], [route('about'), null], [route('contact'), null], [route('consultation'), null],
            ]);

            foreach (PublicSite::legalLinks() as $key => $published) {
                if ($published) {
                    $urls->push([route($key), null]);
                }
            }

            Service::published()->ordered()->get(['slug', 'updated_at'])->each(fn ($s) => $urls->push([route('services.show', $s->slug), $s->updated_at]));
            Project::published()->newestFirst()->get(['slug', 'updated_at'])->each(fn ($p) => $urls->push([route('projects.show', $p->slug), $p->updated_at]));

            return view('public.sitemap', ['urls' => $urls])->render();
        }, 600);

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
