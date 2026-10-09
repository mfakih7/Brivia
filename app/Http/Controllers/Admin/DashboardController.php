<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\DeliveryStatus;
use App\Enums\EnquiryStatus;
use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ConsultationType;
use App\Models\Enquiry;
use App\Models\LegalPage;
use App\Models\NotificationDelivery;
use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Role-scoped counts. Content editors never receive client data here. */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $content = null;
        $operations = null;

        if ($user->can('manage-content')) {
            $content = collect([
                'Services' => Service::class,
                'Packages' => Package::class,
                'Projects' => Project::class,
                'Team members' => TeamMember::class,
                'Consultation types' => ConsultationType::class,
            ])->map(fn ($model) => [
                'published' => $model::query()->published()->count(),
                'draft' => $model::query()->where('status', PublicationStatus::Draft->value)->count(),
            ]);
            $content['Legal pages'] = [
                'published' => LegalPage::query()->published()->count(),
                'draft' => LegalPage::query()->where('status', PublicationStatus::Draft->value)->count(),
            ];
        }

        if ($user->can('manage-operations')) {
            $operations = [
                'new_enquiries' => Enquiry::where('status', EnquiryStatus::New->value)->count(),
                'open_enquiries' => Enquiry::whereIn('status', [EnquiryStatus::InReview->value, EnquiryStatus::Contacted->value, EnquiryStatus::Qualified->value])->count(),
                'requested_appointments' => Appointment::where('status', AppointmentStatus::Requested->value)->count(),
                'upcoming_appointments' => Appointment::where('status', AppointmentStatus::Confirmed->value)
                    ->where('confirmed_start_at_utc', '>=', now())->count(),
                'failed_deliveries' => NotificationDelivery::where('status', DeliveryStatus::Failed->value)->count(),
                'recent_enquiries' => Enquiry::latest('id')->limit(5)->get(['id', 'public_reference', 'name', 'enquiry_type', 'status', 'created_at']),
                'recent_appointments' => Appointment::latest('id')->limit(5)->get(['id', 'public_reference', 'name', 'type_snapshot', 'status', 'preferred_at_utc', 'confirmed_start_at_utc', 'created_at']),
                'staff_recipients_configured' => config('brivia.notifications.staff_recipients') !== [],
            ];
        }

        return view('admin.dashboard', [
            'content' => $content,
            'operations' => $operations,
            'staffCount' => $user->can('manage-staff') ? User::active()->count() : null,
        ]);
    }
}
