<?php

namespace Tests\Feature\Public;

use App\Enums\DeliveryStatus;
use App\Enums\NotificationKind;
use App\Jobs\SendNotificationDelivery;
use App\Mail\OutboxMail;
use App\Models\Enquiry;
use App\Models\NotificationDelivery;
use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EnquirySubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        config(['brivia.notifications.staff_recipients' => ['team@brivia.example']]);
        Mail::fake();
    }

    /** Opens the form (issuing a session-bound key) and waits past the minimum fill time. */
    private function openForm(string $query = ''): string
    {
        $this->get('/contact'.$query)->assertOk();
        $key = array_key_last(session('form_tokens.contact'));
        $this->travel(5)->seconds();

        return $key;
    }

    private function payload(string $key, array $overrides = []): array
    {
        return array_merge([
            'submission_key' => $key,
            'name' => '  Rana   Haddad ',
            'email' => 'rana@example.test',
            'phone' => '+961 70 000 000',
            'company' => 'Example Co',
            'enquiry_type' => 'new_product',
            'message' => 'We want to build a booking web application for our studio.',
            'privacy' => '1',
            'website' => '',
        ], $overrides);
    }

    public function test_valid_enquiry_is_persisted_with_context_consent_and_outbox(): void
    {
        $package = Package::where('slug', 'business-website')->first();
        $package->markPublished();
        $key = $this->openForm('?package=business-website');

        $response = $this->post('/contact', $this->payload($key, ['package' => 'business-website', 'budget' => 'Not sure yet']));

        $response->assertRedirect(route('contact'))->assertSessionHas('enquiry_reference');
        $enquiry = Enquiry::firstOrFail();
        $this->assertSame('Rana Haddad', $enquiry->name);
        $this->assertSame($package->id, $enquiry->package_id);
        $this->assertSame(['package' => 'Business Website'], $enquiry->context_snapshot);
        $this->assertSame('draft:draft-1', $enquiry->policy_version, 'Consent records the honest policy state.');
        $this->assertNotNull($enquiry->privacy_accepted_at);
        $this->assertMatchesRegularExpression('/^BRV-[A-Z2-9]{8}$/', $enquiry->public_reference);
        $this->assertStringNotContainsString((string) $enquiry->id, $response->headers->get('Location'));

        $this->assertSame(2, NotificationDelivery::count(), 'Visitor acknowledgement + one staff alert.');
        $this->assertSame(2, NotificationDelivery::where('status', DeliveryStatus::Sent->value)->count());
        Mail::assertSent(OutboxMail::class, fn ($mail) => $mail->hasTo('rana@example.test') && $mail->delivery->kind === NotificationKind::EnquiryAcknowledgement);
        Mail::assertSent(OutboxMail::class, fn ($mail) => $mail->hasTo('team@brivia.example'));

        $this->get('/contact')->assertSee('Thanks—your enquiry has been received.')->assertSee($enquiry->public_reference);
    }

    public function test_validation_errors_are_inline_and_input_is_preserved(): void
    {
        $key = $this->openForm();

        // Note: asserting on the session before the follow-up GET interferes with Laravel 13's JSON
        // session serialization in tests, so errors are verified on the re-rendered page instead.
        $this->post('/contact', $this->payload($key, ['email' => 'not-an-email', 'message' => 'short', 'privacy' => null, 'name' => 'A']))
            ->assertRedirect(route('contact'));
        $this->assertSame(0, Enquiry::count());

        $this->get('/contact')
            ->assertSee('data-error-summary', false)
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('Enter a valid email address')
            ->assertSee('Please tell us a little more')
            ->assertSee('Please confirm you have read the privacy notice.')
            ->assertSee('value="Example Co"', false)
            ->assertDontSee('value="Thanks', false);
    }

    public function test_context_must_be_published(): void
    {
        $key = $this->openForm();
        Project::factory()->create(['slug' => 'draft-proj']);

        $this->post('/contact', $this->payload($key, ['package' => 'business-website']))->assertSessionHasErrors('package');
        $this->post('/contact', $this->payload($key, ['service' => 'new-products']))->assertSessionHasErrors('service');
        $this->post('/contact', $this->payload($key, ['project' => 'draft-proj']))->assertSessionHasErrors('project');
        $this->post('/contact', $this->payload($key, ['budget' => 'One million dollars']))->assertSessionHasErrors('budget');
        $this->assertSame(0, Enquiry::count());
        $this->assertFalse(Service::where('slug', 'new-products')->first()->isPublished());
    }

    public function test_duplicate_submission_returns_the_same_acknowledgement_without_a_second_record(): void
    {
        $key = $this->openForm();

        $this->post('/contact', $this->payload($key));
        $first = session('enquiry_reference');
        $this->post('/contact', $this->payload($key))->assertRedirect(route('contact'))->assertSessionHas('enquiry_reference', $first);

        $this->assertSame(1, Enquiry::count());
        $this->assertSame(2, NotificationDelivery::count(), 'Retries never duplicate outbox rows.');

        $this->post('/contact', $this->payload($key, ['message' => 'A completely different message for the same key.']))
            ->assertSessionHasErrors('form');
        $this->assertSame(1, Enquiry::count());
    }

    public function test_keys_from_another_session_or_never_issued_are_rejected_without_leaking(): void
    {
        $existing = Enquiry::factory()->create(['submission_key' => '7b2a0c1e-5c4f-4f4b-9a54-1f2d3c4b5a69', 'public_reference' => 'BRV-SECRET99']);

        $this->openForm();
        $response = $this->post('/contact', $this->payload('7b2a0c1e-5c4f-4f4b-9a54-1f2d3c4b5a69'));
        $response->assertSessionHasErrors('form')->assertSessionMissing('enquiry_reference');
        $this->assertStringNotContainsString('BRV-SECRET99', json_encode(session()->all()));

        $this->post('/contact', $this->payload('11111111-2222-4333-8444-555555555555'))->assertSessionHasErrors('form');
        $this->assertSame(1, Enquiry::count());
        $this->assertModelExists($existing);
    }

    public function test_honeypot_and_too_fast_submissions_are_refused(): void
    {
        $key = $this->openForm();
        $this->post('/contact', $this->payload($key, ['website' => 'http://spam.example']))->assertSessionHasErrors('form');

        $this->get('/contact');
        $fresh = array_key_last(session('form_tokens.contact'));
        $this->post('/contact', $this->payload($fresh))->assertSessionHasErrors('form'); // submitted instantly

        $this->assertSame(0, Enquiry::count());
    }

    public function test_public_forms_are_rate_limited_with_preserved_input(): void
    {
        $key = $this->openForm();

        foreach (range(1, 5) as $i) {
            $this->post('/contact', $this->payload($key, ['email' => 'bad']));
        }
        $this->post('/contact', $this->payload($key))
            ->assertRedirect()
            ->assertSessionHasErrors('form')
            ->assertSessionHasInput('company', 'Example Co');
        $this->assertStringContainsString('Please wait', session('errors')->first('form'));
        $this->assertSame(0, Enquiry::count());
    }

    public function test_unavailable_mail_provider_keeps_the_enquiry_and_records_a_redacted_failure(): void
    {
        Mail::shouldReceive('to')->andThrow(new TransportException('Connection refused while sending to rana@example.test'));
        $key = $this->openForm();

        $this->post('/contact', $this->payload($key))->assertRedirect(route('contact'))->assertSessionHas('enquiry_reference');

        $this->assertSame(1, Enquiry::count());
        $ack = NotificationDelivery::where('kind', NotificationKind::EnquiryAcknowledgement->value)->first();
        $this->assertSame(DeliveryStatus::Pending, $ack->status, 'First failure leaves it pending for retry.');
        $this->assertSame(1, $ack->attempts);
        $this->assertStringContainsString('[email]', $ack->last_error);
        $this->assertStringNotContainsString('rana@example.test', $ack->last_error);

        $ack->forceFill(['attempts' => 2, 'last_attempt_at' => now()->subMinutes(10)])->save();
        (new SendNotificationDelivery($ack->id))->handle();
        $this->assertSame(DeliveryStatus::Failed, $ack->fresh()->status, 'Exhausted attempts become a visible failure.');
        $this->assertSame(1, Enquiry::count(), 'Mail retries never duplicate business records.');
    }

    public function test_csrf_is_required_for_visitor_forms(): void
    {
        $this->app->instance('env', 'local');
        $this->assertSame(419, $this->call('POST', '/contact', ['name' => 'x'])->getStatusCode());
    }
}
