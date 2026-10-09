<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_responses_carry_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
        $this->assertNotNull($response->headers->get('X-Request-Id'));
    }

    public function test_built_asset_policy_has_no_inline_allowances_and_hides_php_version(): void
    {
        // Simulate production assets: no Vite dev server ("hot" file) present.
        Vite::useHotFile(storage_path('framework/testing-no-hot-file'));

        $response = $this->get('/admin/login');
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('5173', $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]+'(;|$)/", $csp);
        $this->assertFalse($response->headers->has('X-Powered-By'));
        $this->assertStringContainsString('nonce="', $response->getContent());
    }

    public function test_untrusted_host_headers_are_rejected_outside_local(): void
    {
        config(['app.url' => 'https://brivia.example']);
        $this->app->instance('env', 'production');

        try {
            $this->get('https://evil.example/')->assertStatus(400);
            $this->get('https://brivia.example/robots.txt')->assertOk();
        } finally {
            Request::setTrustedHosts([]); // static on Symfony's Request; don't leak into other tests
        }
    }

    public function test_server_errors_render_the_branded_page_without_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/__test-boom', fn () => throw new \RuntimeException('secret-internal-detail'));

        $this->get('/__test-boom')
            ->assertStatus(500)
            ->assertSee('Something went wrong on our side')
            ->assertDontSee('secret-internal-detail')
            ->assertDontSee('RuntimeException');
    }

    public function test_csrf_is_enforced_on_admin_forms(): void
    {
        // The testing kernel normally bypasses CSRF; re-enable it for this assertion.
        $this->app->instance('env', 'local');
        $response = $this->call('POST', '/admin/login', ['email' => 'a@example.test', 'password' => 'x']);

        $this->assertSame(419, $response->getStatusCode());
    }
}
