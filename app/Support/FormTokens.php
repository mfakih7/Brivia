<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Per-form random submission keys bound to the visitor's session. A key is only honoured
 * when it was issued in this session; the issue time also powers the minimum-fill-time check.
 */
class FormTokens
{
    private const MAX_ACTIVE = 10;

    public static function issue(string $form): string
    {
        $key = (string) Str::uuid();
        $tokens = session("form_tokens.{$form}", []);
        $tokens[$key] = now()->getTimestamp();
        session(["form_tokens.{$form}" => array_slice($tokens, -self::MAX_ACTIVE, null, true)]);

        return $key;
    }

    /** Unix time the key was issued in THIS session, or null. */
    public static function issuedAt(string $form, ?string $key): ?int
    {
        return is_string($key) ? (session("form_tokens.{$form}", [])[$key] ?? null) : null;
    }

    public static function rememberSubmission(string $form, string $key, string $reference): void
    {
        session(["form_submitted.{$form}.{$key}" => $reference]);
    }

    public static function submittedReference(string $form, string $key): ?string
    {
        return session("form_submitted.{$form}.{$key}");
    }
}
