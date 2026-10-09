<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Enquiry;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Shared submission guard for visitor forms: session-bound idempotency key, honeypot,
 * minimum fill time and duplicate handling. Same key + same payload => same acknowledgement;
 * same key + different payload => conflict. Keys from another session are never honoured.
 */
class VisitorSubmission
{
    /**
     * @param  class-string<Enquiry|Appointment>  $model
     * @param  callable(): (Enquiry|Appointment)  $persist  runs inside a transaction
     * @return string public reference
     */
    public function handle(Request $request, string $form, string $model, array $payload, callable $persist): string
    {
        $key = $request->input('submission_key');
        $hash = hash('sha256', json_encode($payload));

        if ($existing = $model::where('submission_key', $key)->first(['public_reference', 'payload_hash'])) {
            return $this->duplicate($form, $key, $existing, $hash);
        }

        $issuedAt = FormTokens::issuedAt($form, $key);
        if ($issuedAt === null) {
            $this->fail('Your form session expired. Please check your details and submit again.');
        }
        if (filled($request->input('website')) || now()->getTimestamp() - $issuedAt < config('brivia.forms.min_seconds', 3)) {
            $this->fail('We could not accept this submission yet. Please review your details and submit again.');
        }

        try {
            $record = DB::transaction(fn () => $persist($hash));
        } catch (UniqueConstraintViolationException) {
            // The same key raced in from a double submit: answer like a retry.
            $existing = $model::where('submission_key', $key)->firstOrFail(['public_reference', 'payload_hash']);

            return $this->duplicate($form, $key, $existing, $hash);
        }

        FormTokens::rememberSubmission($form, $key, $record->public_reference);

        return $record->public_reference;
    }

    private function duplicate(string $form, string $key, Enquiry|Appointment $existing, string $hash): string
    {
        $mine = FormTokens::submittedReference($form, $key) === $existing->public_reference;

        if ($mine && hash_equals($existing->payload_hash, $hash)) {
            return $existing->public_reference;
        }

        $this->fail($mine
            ? 'This form was already submitted with different details. Your earlier submission was kept; please use the new form if you want to send another.'
            : 'Your form session expired. Please check your details and submit again.');
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['form' => $message]);
    }
}
