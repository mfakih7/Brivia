<?php

namespace App\Http\Controllers\Public;

use App\Enums\EnquiryStatus;
use App\Enums\EnquiryType;
use App\Enums\NotificationKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\EnquiryRequest;
use App\Models\Enquiry;
use App\Models\LegalPage;
use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use App\Support\FormTokens;
use App\Support\NotificationOutbox;
use App\Support\PublicReference;
use App\Support\PublicSite;
use App\Support\VisitorSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(Request $request): View
    {
        $services = Service::published()->ordered()->get(['id', 'title', 'slug']);
        $packages = Package::published()->ordered()->get(['id', 'name', 'slug']);

        // Context arrives as public slugs and is resolved against PUBLISHED records only.
        $slug = fn (string $key) => is_string($v = old($key, $request->query($key))) && $v !== '0' ? mb_substr($v, 0, 200) : null;
        $project = ($s = $slug('project')) ? Project::published()->where('slug', $s)->first(['id', 'title', 'slug']) : null;

        return view('public.contact', [
            'settings' => PublicSite::settings(),
            'services' => $services,
            'packages' => $packages,
            'selectedService' => ($s = $slug('service')) ? $services->firstWhere('slug', $s) : null,
            'selectedPackage' => ($s = $slug('package')) ? $packages->firstWhere('slug', $s) : null,
            'project' => $project,
            'types' => EnquiryType::cases(),
            'formEnabled' => true,
            'submissionKey' => FormTokens::issue('contact'),
        ]);
    }

    public function store(EnquiryRequest $request, VisitorSubmission $submission, NotificationOutbox $outbox): RedirectResponse
    {
        $data = $request->validated();

        // Never trust submitted context: resolve published records by slug.
        $context = [];
        foreach (['service' => [Service::class, 'title'], 'package' => [Package::class, 'name'], 'project' => [Project::class, 'title']] as $field => [$class, $label]) {
            if (filled($data[$field] ?? null)) {
                $record = $class::published()->where('slug', $data[$field])->first(['id', $label]);
                if (! $record) {
                    throw ValidationException::withMessages([$field => 'That option is no longer available. Please choose another or leave it empty.']);
                }
                $context[$field] = $record;
            }
        }

        $payload = collect($data)->except(['submission_key', 'privacy'])->all();

        $reference = $submission->handle($request, 'contact', Enquiry::class, $payload, function (string $hash) use ($data, $context, $outbox) {
            $enquiry = new Enquiry;
            $enquiry->forceFill([
                'public_reference' => PublicReference::generate('BRV'),
                'submission_key' => $data['submission_key'],
                'payload_hash' => $hash,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'enquiry_type' => $data['enquiry_type'],
                'service_id' => $context['service']->id ?? null,
                'package_id' => $context['package']->id ?? null,
                'project_id' => $context['project']->id ?? null,
                'context_snapshot' => array_filter([
                    'service' => $context['service']->title ?? null,
                    'package' => $context['package']->name ?? null,
                    'project' => $context['project']->title ?? null,
                ]) ?: null,
                'budget' => $data['budget'] ?? null,
                'timeline' => $data['timeline'] ?? null,
                'message' => $data['message'],
                'status' => EnquiryStatus::New,
                'policy_version' => LegalPage::currentPrivacyVersion(),
                'privacy_accepted_at' => now(),
            ])->save();

            $outbox->queue(NotificationKind::EnquiryAcknowledgement, $enquiry->email,
                ['reference' => $enquiry->public_reference, 'name' => $enquiry->name], $enquiry, "enquiry:{$enquiry->id}:ack");
            $outbox->queueStaffAlerts(NotificationKind::EnquiryStaffAlert, [
                'reference' => $enquiry->public_reference,
                'type' => $enquiry->enquiry_type->label(),
                'admin_url' => route('admin.enquiries.show', $enquiry),
            ], $enquiry, "enquiry:{$enquiry->id}");

            return $enquiry;
        });

        return redirect()->route('contact')->with('enquiry_reference', $reference);
    }
}
