<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Microsoft\Provider;

class MicrosoftSocialiteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->bootMicrosoftSocialite();
    }

    /**
     * Register Microsoft OAuth provider with Laravel Socialite
     */
    private function bootMicrosoftSocialite(): void
    {
        $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
        $socialite->extend(
            'microsoft',
            function ($app) use ($socialite) {
                $config = $app['config']['services.microsoft'];
                return $socialite->buildProvider(Provider::class, $config);
            }
        );
    }
}
