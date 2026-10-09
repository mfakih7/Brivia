<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use App\Support\AuditLogger;
use App\Support\NotificationOutbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Delivery outcomes for transactional email; failures are visible and retryable. */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), array_column(DeliveryStatus::cases(), 'value'), true) ? $request->query('status') : null;

        return view('admin.notifications.index', [
            'deliveries' => NotificationDelivery::with(['enquiry:id,public_reference', 'appointment:id,public_reference'])
                ->when($status, fn ($q, $s) => $q->where('status', $s), fn ($q) => $q->whereIn('status', [DeliveryStatus::Failed->value, DeliveryStatus::Pending->value]))
                ->latest('id')->paginate(30)->withQueryString(),
            'counts' => NotificationDelivery::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function retry(NotificationDelivery $delivery, NotificationOutbox $outbox, AuditLogger $audit): RedirectResponse
    {
        if (! $outbox->retry($delivery)) {
            return back()->with('error', 'Only failed deliveries can be retried.');
        }

        $audit->record('notification.retried', 'notification_delivery', $delivery->id, ['kind' => $delivery->kind->value]);

        return back()->with('status', 'Delivery queued again. Check back shortly for the outcome.');
    }
}
