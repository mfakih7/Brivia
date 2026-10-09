<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            $rule = Password::min(12)->max(255)->letters()->mixedCase()->numbers()->symbols();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        ResetPassword::createUrlUsing(fn (User $user, string $token) => route('admin.password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        if ($proxies = config('brivia.security.trusted_proxies')) {
            TrustProxies::at($proxies);
        }

        $this->defineGates();
        $this->defineRateLimiters();
    }

    private function defineGates(): void
    {
        Gate::define('manage-content', fn (User $user) => $user->canManageContent());
        Gate::define('manage-operations', fn (User $user) => $user->canManageOperations());
        Gate::define('manage-staff', fn (User $user) => $user->is_active && $user->role->canManageStaff());
        Gate::define('view-audit-log', fn (User $user) => $user->is_active && $user->isOwner());
        Gate::define('delete-personal-records', fn (User $user) => $user->is_active && $user->isOwner());
        Gate::define('approve-placeholder-content', fn (User $user) => $user->is_active && $user->isOwner());
    }

    private function defineRateLimiters(): void
    {
        RateLimiter::for('admin-password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by('reset-ip:'.$request->ip()),
            Limit::perMinute(3)->by('reset-email:'.Str::lower((string) $request->input('email'))),
        ]);

        RateLimiter::for('admin-invitation-accept', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('admin-invitations', fn (Request $request) => Limit::perMinute(3)->by('invite:'.$request->user()?->id));

        // Visitor forms: 5 per minute per IP. Keeps the visitor's input and explains when to retry.
        RateLimiter::for('public-forms', fn (Request $request) => Limit::perMinute(5)->by('form:'.$request->ip())
            ->response(fn (Request $request, array $headers) => back()
                ->withInput($request->except(['submission_key', 'website']))
                ->withErrors(['form' => 'You have sent several forms in a short time. Please wait '.($headers['Retry-After'] ?? 60).' seconds and try again.'])));
    }
}
