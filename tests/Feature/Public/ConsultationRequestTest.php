<?php

namespace Tests\Feature\Public;

use App\Enums\AppointmentStatus;
use App\Mail\OutboxMail;
use App\Models\Appointment;
use App\Models\ConsultationType;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ConsultationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00:00', 'UTC'));
        $this->seed(DatabaseSeeder::class);
        ConsultationType::where('slug', 'introductory-call')->first()->markPublished();
        Mail::fake();
    }

    private function submit(array $overrides = [])
    {
        $this->get('/consultation')->assertOk();
        $key = array_key_last(session('form_tokens.consultation'));
        $this->travel(5)->seconds();

        return $this->post('/consultation', array_merge([
            'submission_key' => $key,
            'name' => 'Karim Nassar',
            'email' => 'karim@example.test',
            'consultation_type' => 'introductory-call',
            'timezone' => 'Asia/Beirut',
            'preferred_date' => '2026-10-20',
            'preferred_time' => '10:00',
            'summary' => 'We need advice on modernizing our internal booking tool.',
            'privacy' => '1',
        ], $overrides));
    }

    public function test_request_is_stored_in_utc_with_timezone_and_type_snapshot(): void
    {
        $this->submit(['alternate_date' => '2026-10-21', 'alternate_time' => '15:30'])
            ->assertRedirect(route('consultation'))->assertSessionHas('appointment_reference');

        $appointment = Appointment::firstOrFail();
        $this->assertSame('2026-10-20 07:00:00', $appointment->preferred_at_utc->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-21 12:30:00', $appointment->alternate_at_utc->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Beirut', $appointment->requested_timezone);
        $this->assertSame(AppointmentStatus::Requested, $appointment->status);
        $this->assertSame('Introductory call', $appointment->type_snapshot);
        $this->assertSame(30, $appointment->duration_minutes);
        $this->assertNull($appointment->confirmed_start_at_utc, 'A request is never a reservation.');

        Mail::assertSent(OutboxMail::class, fn ($m) => $m->hasTo('karim@example.test') && str_contains($m->render(), 'not reserved'));
        $this->get('/consultation')->assertSee('Your preferred time is not reserved until BRIVIA confirms it.');
    }

    public function test_dst_gap_and_overlap_times_are_rejected_with_guidance(): void
    {
        $this->submit(['timezone' => 'Europe/Berlin', 'preferred_date' => '2026-10-25', 'preferred_time' => '02:30'])
            ->assertSessionHasErrors(['preferred_time' => 'That time happens twice in the selected timezone because the clocks go back (daylight saving). Please choose another time.']);

        $this->travelTo(CarbonImmutable::parse('2027-03-01 09:00:00', 'UTC'));
        $this->submit(['timezone' => 'Europe/Berlin', 'preferred_date' => '2027-03-28', 'preferred_time' => '02:30'])
            ->assertSessionHasErrors('preferred_time');
        $this->assertStringContainsString('does not exist', session('errors')->first('preferred_time'));
        $this->assertSame(0, Appointment::count());
    }

    public function test_past_out_of_range_and_duplicate_alternate_times_are_rejected(): void
    {
        $this->submit(['preferred_date' => '2026-10-09'])->assertSessionHasErrors('preferred_date');
        $this->submit(['preferred_date' => '2027-02-01'])->assertSessionHasErrors('preferred_date');
        $this->submit(['alternate_date' => '2026-10-20', 'alternate_time' => '10:00'])->assertSessionHasErrors('alternate_time');
        $this->submit(['alternate_date' => '2026-10-21'])->assertSessionHasErrors('alternate_time');
        $this->submit(['timezone' => 'Not/AZone'])->assertSessionHasErrors('timezone');
        $this->assertSame(0, Appointment::count());
    }

    public function test_unpublished_consultation_type_is_rejected(): void
    {
        $this->submit(['consultation_type' => 'technical-consultation'])->assertSessionHasErrors('consultation_type');
        $this->assertSame(0, Appointment::count());
    }
}
