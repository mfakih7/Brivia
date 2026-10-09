@php($K = \App\Enums\NotificationKind::class)
<x-mail::message>
@switch($kind)
@case($K::EnquiryAcknowledgement)
# Thank you, {{ $p['name'] }}

Your enquiry has been received. Our team will review it and get back to you.

Your reference is **{{ $p['reference'] }}**. Please don't reply with passwords or other confidential access details.
@break

@case($K::EnquiryStaffAlert)
# New enquiry {{ $p['reference'] }}

**Type:** {{ $p['type'] }}

The message and contact details are available in the admin panel only.

<x-mail::button :url="$p['admin_url']">Open enquiry</x-mail::button>
@break

@case($K::AppointmentAcknowledgement)
# Thank you, {{ $p['name'] }}

We received your request for a **{{ $p['type'] }}** ({{ $p['duration'] }} minutes).

**Preferred time:** {{ $preferred }}

Your preferred time is **not reserved** until BRIVIA confirms it. We will email you to confirm the exact time or suggest an alternative.

Reference: **{{ $p['reference'] }}**
@break

@case($K::AppointmentStaffAlert)
# New consultation request {{ $p['reference'] }}

**Type:** {{ $p['type'] }}
**Preferred:** {{ $preferred }}

<x-mail::button :url="$p['admin_url']">Review request</x-mail::button>
@break

@case($K::AppointmentConfirmed)
@case($K::AppointmentRescheduled)
@case($K::AppointmentReminder)
# {{ $kind === $K::AppointmentConfirmed ? 'Your consultation is confirmed' : ($kind === $K::AppointmentRescheduled ? 'Your consultation has a new time' : 'Your consultation is coming up') }}

Hello {{ $p['name'] }},

**{{ $p['type'] }}** — {{ $p['duration'] }} minutes

**When:** {{ $when }}

@if (! empty($p['meeting_url']))
**Meeting link:** {{ $p['meeting_url'] }}
@else
Meeting details will follow.
@endif

@if ($kind === $K::AppointmentRescheduled && ! empty($p['reason']))
**Note from BRIVIA:** {{ $p['reason'] }}
@endif

Reference: **{{ $p['reference'] }}**. If this time no longer works, reply to this email.
@break

@case($K::AppointmentDeclined)
@case($K::AppointmentCancelled)
# {{ $kind === $K::AppointmentDeclined ? 'We could not schedule your consultation' : 'Your consultation has been cancelled' }}

Hello {{ $p['name'] }},

@if ($kind === $K::AppointmentDeclined)
We are unable to confirm your consultation request ({{ $p['reference'] }}).
@else
Your consultation ({{ $p['reference'] }}) has been cancelled.
@endif

@if (! empty($p['reason']))
**Reason:** {{ $p['reason'] }}
@endif

You are welcome to send a new request or contact us.
@break
@endswitch

{{ config('app.name') }}
</x-mail::message>
