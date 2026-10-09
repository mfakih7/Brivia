<?php

namespace Tests\Feature\Admin;

use App\Enums\DeliveryStatus;
use App\Enums\EnquiryStatus;
use App\Enums\NotificationKind;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Enquiry;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnquiryOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_transitions_follow_the_allowed_graph_and_are_recorded(): void
    {
        $this->actingAsStaff(Role::OperationsManager);
        $e = Enquiry::factory()->create();
        $move = fn (string $to) => $this->post("/admin/enquiries/{$e->id}/status", ['status' => $to, 'lock_version' => $e->fresh()->lock_version]);

        $move('qualified')->assertSessionHasErrors('status'); // new -> qualified not allowed
        $move('in_review')->assertSessionHas('status');
        $move('qualified')->assertSessionHas('status');
        $move('in_review')->assertSessionHasErrors('status'); // qualified -> closed only
        $move('closed')->assertSessionHas('status');
        $move('in_review')->assertSessionHas('status'); // explicit reopen

        $this->assertSame(EnquiryStatus::InReview, $e->fresh()->status);
        $this->assertSame(4, $e->events()->count());
        $this->assertSame(4, AuditLog::where('action', 'enquiry.status_changed')->count());
        $this->assertNull(collect(AuditLog::pluck('changed_fields'))->first(fn ($f) => isset($f['message'])));
    }

    public function test_stale_status_change_is_rejected(): void
    {
        $this->actingAsStaff(Role::OperationsManager);
        $e = Enquiry::factory()->create();
        $stale = $e->fresh()->lock_version;
        $this->post("/admin/enquiries/{$e->id}/status", ['status' => 'in_review', 'lock_version' => $stale]);

        $this->post("/admin/enquiries/{$e->id}/status", ['status' => 'spam', 'lock_version' => $stale])->assertSessionHas('error');
        $this->assertSame(EnquiryStatus::InReview, $e->fresh()->status);
    }

    public function test_assignment_notes_and_detail_rendering(): void
    {
        $ops = $this->actingAsStaff(Role::OperationsManager);
        $editor = User::factory()->contentEditor()->create();
        $e = Enquiry::factory()->create(['message' => "<img src=x onerror=alert(1)>\nSecond line"]);

        $this->post("/admin/enquiries/{$e->id}/assign", ['assigned_to' => $editor->id, 'lock_version' => 0])->assertSessionHasErrors('assigned_to');
        $this->post("/admin/enquiries/{$e->id}/assign", ['assigned_to' => $ops->id, 'lock_version' => 0])->assertSessionHas('status');
        $this->post("/admin/enquiries/{$e->id}/notes", ['body' => 'Called back, interested.'])->assertSessionHas('status');

        $this->get("/admin/enquiries/{$e->id}")->assertOk()
            ->assertDontSee('<img src=x', false)->assertSee('&lt;img src=x', false)
            ->assertSee('Called back, interested.')->assertSee('mailto:'.$e->email, false);
        $this->get('/admin/enquiries?status=new&type=new_product&q=%25')->assertOk();
        $this->get('/admin/enquiries?assignee=me')->assertOk()->assertSee($e->name);
    }

    public function test_failed_delivery_is_visible_and_retryable_without_touching_the_enquiry(): void
    {
        Mail::fake();
        $this->actingAsStaff(Role::OperationsManager);
        $e = Enquiry::factory()->create();
        $d = new NotificationDelivery;
        $d->forceFill(['event_key' => "enquiry:{$e->id}:ack", 'enquiry_id' => $e->id, 'kind' => NotificationKind::EnquiryAcknowledgement,
            'recipient_email' => $e->email, 'payload' => ['reference' => $e->public_reference, 'name' => $e->name],
            'status' => DeliveryStatus::Failed, 'attempts' => 3, 'last_error' => 'TransportException: Connection refused'])->save();

        $this->get('/admin')->assertSee('Failed emails');
        $this->get('/admin/notifications?status=failed')->assertOk()->assertSee('Connection refused');
        $this->get("/admin/enquiries/{$e->id}")->assertSee('Connection refused')->assertSee('Retry');

        $this->post("/admin/notifications/{$d->id}/retry")->assertSessionHas('status');
        $this->assertSame(DeliveryStatus::Sent, $d->fresh()->status);
        $this->assertSame(EnquiryStatus::New, $e->fresh()->status);
        $this->post("/admin/notifications/{$d->id}/retry")->assertSessionHas('error');
    }

    public function test_owner_deletes_only_closed_enquiries(): void
    {
        $this->actingAsStaff(Role::Owner);
        $open = Enquiry::factory()->create();
        $closed = Enquiry::factory()->create(['status' => EnquiryStatus::Spam]);

        $this->delete("/admin/enquiries/{$open->id}", ['confirm_reference' => $open->public_reference])->assertSessionHas('error');
        $this->delete("/admin/enquiries/{$closed->id}", ['confirm_reference' => $closed->public_reference])->assertRedirect(route('admin.enquiries.index'));
        $this->assertModelMissing($closed);
        $this->assertModelExists($open);
    }
}
