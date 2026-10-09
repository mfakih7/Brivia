<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/** Baseline security headers and a strict Content Security Policy for all web responses. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        $headers = $response->headers;
        $headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By'); // set by PHP itself (expose_php); also disable expose_php on the server
        }
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (! $headers->has('Content-Security-Policy') && ($policy = $this->policy()) !== null) {
            $headers->set('Content-Security-Policy', $policy);
        }

        if (config('brivia.security.hsts') && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(): ?string
    {
        $nonce = Vite::cspNonce();
        $script = ["'self'", "'nonce-{$nonce}'"];
        $style = ["'self'"];
        $connect = ["'self'"];
        $font = ["'self'"];
        $img = ["'self'", 'data:'];

        // Vite dev server (local only): HMR injects inline styles and connects over websockets.
        if (Vite::isRunningHot()) {
            $hot = rtrim(file_get_contents(public_path('hot')));

            // CSP host-sources cannot express IPv6 literals (e.g. http://[::1]:5173). Local dev only:
            // skip the policy rather than block the dev server. vite.config.js pins 127.0.0.1 to avoid this.
            if (str_contains($hot, '[') && app()->isLocal()) {
                return null;
            }

            $ws = preg_replace('/^http/', 'ws', $hot);
            array_push($script, $hot);
            array_push($style, $hot, "'unsafe-inline'");
            array_push($connect, $hot, $ws);
            $font[] = $hot;
            $img[] = $hot;
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            'img-src '.implode(' ', $img),
            'font-src '.implode(' ', $font),
            'connect-src '.implode(' ', $connect),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
