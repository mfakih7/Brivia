<?php

namespace App\Support;

use App\Http\Controllers\Public\ProjectPageController;
use App\Http\Controllers\Public\ServicePageController;
use App\Models\LegalPage;
use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticated preview of SAVED content (drafts included) rendered with the real public
 * templates. Responses are private, no-store and noindex via the admin middleware group.
 */
class PreviewRenderer
{
    public function render(Model $model, string $type): Response
    {
        $shared = ['preview' => true];

        return match (true) {
            $model instanceof Service => response()->view('public.services.show', ServicePageController::viewData($model) + $shared),
            $model instanceof Project => response()->view('public.projects.show', ProjectPageController::viewData($model) + $shared),
            $model instanceof LegalPage => response()->view('public.legal', ['page' => $model] + $shared),
            $model instanceof Package => response()->view('public.preview-card', ['package' => $model, 'member' => null] + $shared),
            $model instanceof TeamMember => response()->view('public.preview-card', ['package' => null, 'member' => $model->loadMissing('portrait')] + $shared),
        };
    }
}
