<?php

namespace Tests\Feature\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\DeliveryStatus;
use App\Enums\NotificationKind;
use App\Enums\Role;
use App\Jobs\SendNotificationDelivery;
use App\Mail\OutboxMail;
use App\Models\Appointment;
use App\Models\NotificationDelivery;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $ops;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00:00', 'UTC'));
        Mail::fake();
        $this->ops = $this->actingAsStaff(Role::OperationsManager);
    }

    private function confirm(Appointment $a, array $overrides = [])
    {
        return $this->post("/admin/appointments/{$a->id}/confirm", array_merge([
            'lock_version' => $a->fresh()->lock_version,
            'assigned_to' => $this->ops->id,
            'start_date' => '2026-10-20',
            'start_time' => '10:00',
            'timezone' => 'Asia/Beirut',
            'duration_minutes' => 60,
            'meeting_url' => 'https://meet.example.test/abc',
        ], $overrides));
    }

    private function deliveries(Appointment $a, NotificationKind $kind)
    {
        return NotificationDelivery::where('appointment_id', $a->id)->where('kind', $kind->value)->get();
    }

    public function test_confirmation_sets_exact_utc_times_emails_and_schedules_one_reminder(): void
    {
        $a = Appointment::factory()->create(['requested_timezone' => 'Europe/London']);

        $this->confirm($a)->assertSessionHas('status');

        $a->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $a->status);
        $this->assertSame('2026-10-20 07:00', $a->confirmed_start_at_utc->format('Y-m-d H:i'));
        $this->assertSame('2026-10-20 08:00', $a->confirmed_end_at_utc->format('Y-m-d H:i'));
        $this->assertSame(1, $a->schedule_version);
        $this->assertSame(1, $a->events()->where('event_type', 'confirmed')->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.confirmed', 'resource_id' => $a->id]);

        $this->assertSame(DeliveryStatus::Sent, $this->deliveries($a, NotificationKind::AppointmentConfirmed)->first()->status);
        Mail::assertSent(OutboxMail::class, fn ($m) => $m->hasTo($a->email) && str_contains($m->render(), 'Tuesday 20 October 2026, 08:00 (Europe/London'));

        $reminder = $this->deliveries($a, NotificationKind::AppointmentReminder)->sole();
        $this->assertSame(DeliveryStatus::Pending, $reminder->status);
        $this->assertSame('2026-10-19 07:00', $reminder->scheduled_for->format('Y-m-d H:i'));
    }

    public function test_confirmation_inside_24_hours_sends_no_reminder_and_says_details_follow(): void
    {
        $a = Appointment::factory()->create();

        $this->confirm($a, ['start_date' => '2026-10-11', 'start_time' => '09:00', 'meeting_url' => ''])->assertSessionHas('status');

        $this->assertCount(0, $this->deliveries($a, NotificationKind::AppointmentReminder));
        Mail::assertSent(OutboxMail::class, fn ($m) => str_contains($m->render(), 'Meeting details will follow.'));
    }

    public function test_overlapping_confirmation_for_the_same_assignee_is_rejected_adjacent_allowed(): void
    {
        [$a, $b, $c] = Appointment::factory()->count(3)->create();
        $other = User::factory()->operationsManager()->create();

        $this->confirm($a);
        $this->confirm($b, ['start_time' => '10:30'])->assertSessionHas('error');
        $this->assertSame(AppointmentStatus::Requested, $b->fresh()->status);
        $this->assertStringContainsString($a->public_reference, session('error'));

        $this->confirm($b, ['start_time' => '11:00'])->assertSessionHas('status'); // adjacent
        $this->confirm($c, ['start_time' => '10:30', 'assigned_to' => $other->id])->assertSessionHas('status'); // different person
        $this->assertSame(3, Appointment::where('status', 'confirmed')->count());
    }

    public function test_stale_edits_are_rejected(): void
    {
        $a = Appointment::factory()->create();
        $stale = $a->fresh()->lock_version;
        $this->post("/admin/appointments/{$a->id}/assign", ['lock_version' => $stale, 'assigned_to' => $this->ops->id]);

        $this->confirm($a, ['lock_version' => $stale])->assertSessionHas('error');
        $this->assertSame(AppointmentStatus::Requested, $a->fresh()->status);
    }

    public function test_reschedule_requires_reason_and_supersedes_old_reminder(): void
    {
        $a = Appointment::factory()->create();
        $this->confirm($a);
        $oldReminder = $this->deliveries($a, NotificationKind::AppointmentReminder)->sole();

        $this->confirm($a, ['start_date' => '2026-10-22'])->assertSessionHasErrors('reason');
        $this->confirm($a, ['start_date' => '2026-10-22', 'reason' => 'Clash with another meeting'])->assertSessionHas('status');

        $this->assertSame(DeliveryStatus::Suppressed, $oldReminder->fresh()->status);
        $this->assertSame(2, $a->fresh()->schedule_version);
        $newReminder = $this->deliveries($a, NotificationKind::AppointmentReminder)->firstWhere('status', DeliveryStatus::Pending);
        $this->assertSame('2026-10-21 07:00', $newReminder->scheduled_for->format('Y-m-d H:i'));
        Mail::assertSent(OutboxMail::class, fn ($m) => $m->delivery->kind === NotificationKind::AppointmentRescheduled && str_contains($m->render(), 'Clash with another meeting'));
    }

    public function test_cancellation_suppresses_pending_reminder_and_stale_jobs_send_nothing(): void
    {
        $a = Appointment::factory()->create();
        $this->confirm($a);
        $reminder = $this->deliveries($a, NotificationKind::AppointmentReminder)->sole();

        $this->post("/admin/appointments/{$a->id}/cancel", ['lock_version' => $a->fresh()->lock_version, 'reason' => 'Client asked to postpone'])->assertSessionHas('status');
        $this->assertSame(AppointmentStatus::Cancelled, $a->fresh()->status);
        $this->assertSame(DeliveryStatus::Suppressed, $reminder->fresh()->status);

        // A stale job (e.g. already queued before cancellation) must not send obsolete details.
        $reminder->forceFill(['status' => DeliveryStatus::Pending, 'scheduled_for' => null])->save();
        Mail::fake();
        (new SendNotificationDelivery($reminder->id))->handle();
        $this->assertSame(DeliveryStatus::Suppressed, $reminder->fresh()->status);
        Mail::assertNothingSent();

        // Cancelled cannot be confirmed again.
        $this->confirm($a)->assertSessionHasErrors('status');
    }

    public function test_reminder_is_sent_by_the_scheduled_dispatcher_when_due(): void
    {
        $a = Appointment::factory()->create();
        $this->confirm($a);
        Mail::fake();

        $this->artisan('brivia:notifications:dispatch')->assertSuccessful();
        Mail::assertNothingSent();

        $this->travelTo(CarbonImmutable::parse('2026-10-19 07:01:00', 'UTC'));
        $this->artisan('brivia:notifications:dispatch')->assertSuccessful();
        Mail::assertSent(OutboxMail::class, fn ($m) => $m->delivery->kind === NotificationKind::AppointmentReminder);
        $this->assertSame(DeliveryStatus::Sent, $this->deliveries($a, NotificationKind::AppointmentReminder)->sole()->status);
    }

    public function test_dispatcher_recovers_orphaned_pending_rows(): void
    {
        $a = Appointment::factory()->create();
        $orphan = new NotificationDelivery;
        $orphan->forceFill([
            'event_key' => 'appointment:'.$a->id.':ack', 'appointment_id' => $a->id, 'kind' => NotificationKind::AppointmentAcknowledgement,
            'recipient_email' => $a->email, 'payload' => ['reference' => $a->public_reference, 'name' => $a->name, 'type' => 'Call', 'duration' => 30, 'timezone' => 'UTC', 'preferred_utc' => now()->addDays(3)->toIso8601String()],
            'status' => DeliveryStatus::Pending,
        ])->save();
        $orphan->forceFill(['created_at' => now()->subMinutes(3)])->save();

        $this->artisan('brivia:notifications:dispatch')->assertSuccessful();

        $this->assertSame(DeliveryStatus::Sent, $orphan->fresh()->status);
    }

    public function test_decline_complete_and_assignee_rules(): void
    {
        $a = Appointment::factory()->create();
        $editor = User::factory()->contentEditor()->create();

        $this->confirm($a, ['assigned_to' => $editor->id])->assertSessionHasErrors('assigned_to');
        $this->post("/admin/appointments/{$a->id}/decline", ['lock_version' => $a->fresh()->lock_version, 'reason' => ''])->assertSessionHasErrors('reason');
        $this->post("/admin/appointments/{$a->id}/decline", ['lock_version' => $a->fresh()->lock_version, 'reason' => 'Outside our services'])->assertSessionHas('status');
        $this->assertSame(AppointmentStatus::Declined, $a->fresh()->status);
        Mail::assertSent(OutboxMail::class, fn ($m) => $m->delivery->kind === NotificationKind::AppointmentDeclined);

        $b = Appointment::factory()->create();
        $this->confirm($b);
        $this->post("/admin/appointments/{$b->id}/complete", ['lock_version' => $b->fresh()->lock_version])->assertSessionHasErrors('status');
        $this->travelTo(CarbonImmutable::parse('2026-10-20 09:00:00', 'UTC'));
        $this->post("/admin/appointments/{$b->id}/complete", ['lock_version' => $b->fresh()->lock_version])->assertSessionHas('status');
        $this->assertSame(AppointmentStatus::Completed, $b->fresh()->status);
    }

    public function test_dst_ambiguous_staff_time_is_rejected(): void
    {
        $a = Appointment::factory()->create();

        $this->confirm($a, ['timezone' => 'Europe/Berlin', 'start_date' => '2026-10-25', 'start_time' => '02:30'])->assertSessionHasErrors('start_time');
        $this->assertSame(AppointmentStatus::Requested, $a->fresh()->status);
    }

    public function test_detail_and_list_render_and_escape_visitor_text(): void
    {
        $a = Appointment::factory()->create(['summary' => '<script>alert(1)</script>', 'name' => '<b>Bold</b>']);

        $this->get('/admin/appointments')->assertOk()->assertSee('&lt;b&gt;Bold&lt;/b&gt;', false);
        $this->get("/admin/appointments/{$a->id}")->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('Confirm an exact time');
    }

    public function test_content_editor_cannot_operate_appointments(): void
    {
        $a = Appointment::factory()->create();
        $this->actingAsStaff(Role::ContentEditor);

        $this->get("/admin/appointments/{$a->id}")->assertForbidden();
        $this->confirm($a)->assertForbidden();
        $this->post("/admin/appointments/{$a->id}/notes", ['body' => 'x'])->assertForbidden();
        $this->get('/admin/notifications')->assertForbidden();
    }

    public function test_only_owner_can_hard_delete_closed_records(): void
    {
        $a = Appointment::factory()->create(['status' => AppointmentStatus::Cancelled]);
        $open = Appointment::factory()->create();

        $this->delete("/admin/appointments/{$a->id}", ['confirm_reference' => $a->public_reference])->assertForbidden();

        $this->actingAsStaff(Role::Owner);
        $this->delete("/admin/appointments/{$open->id}", ['confirm_reference' => $open->public_reference])->assertSessionHas('error');
        $this->delete("/admin/appointments/{$a->id}", ['confirm_reference' => 'WRONG'])->assertSessionHasErrors('confirm_reference');
        $this->delete("/admin/appointments/{$a->id}", ['confirm_reference' => $a->public_reference])->assertRedirect(route('admin.appointments.index'));
        $this->assertModelMissing($a);
        $this->assertModelExists($open);
        $this->assertDatabaseHas('audit_logs', ['action' => 'personal_record.deleted', 'resource_id' => $a->id]);
    }
}
