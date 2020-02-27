<?php

namespace RtoWebsites\Credentials\Providers;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RtoWebsites\Credentials\Commands\EditCredentialsCommand;
use RtoWebsites\Credentials\Credentials;

class CredentialsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/credentials.php' => config_path('credentials.php'),
        ], 'config');

        $this->mergeConfigFrom(dirname(__DIR__) . '/../config/credentials.php', 'credentials');

        // Update configuration strings
        if (!app()->configurationIsCached()) {
            $this->fixConfig();
        }
    }

    /**
     * Fix the configuration.
     *
     * @return void
     */
    protected function fixConfig()
    {
        collect(Arr::dot(config()->all()))->filter(function ($item) {
            return is_string($item) && Str::startsWith($item, Credentials::CONFIG_PREFIX);
        })->map(function ($item, $key) {
            $item = str_replace_first(Credentials::CONFIG_PREFIX, '', $item);

            config()->set($key, credentials($item));
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Credentials::class, function () {

            // If the key starts with "base64:", we will need to decode the key before handing
            // it off to the encrypter. Keys may be base-64 encoded for presentation and we
            // want to make sure to convert them back to the raw bytes before encrypting.
            if (Str::startsWith($key = config('credentials.key'), 'base64:')) {
                $key = base64_decode(substr($key, 7));
            }

            $encrypter = new Encrypter($key, config('credentials.cipher'));

            return new Credentials($encrypter);
        });

        $this->app->bind('command.credentials.edit', EditCredentialsCommand::class);

        $this->commands([
            'command.credentials.edit',
        ]);
    }
}
