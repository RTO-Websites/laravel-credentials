<?php

use RtoWebsites\Credentials\Credentials;

if (! function_exists('credentials')) {
    /**
     * Get a an encrypted value.
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    function credentials(string $key, $default = null)
    {
        $filename = config('credentials.file');

        try {
            /* @var Credentials $credentials */
            $credentials = app(Credentials::class);
            $credentials->load($filename);

            return $credentials->get($key, $default);
        } catch (ReflectionException $e) {
            return Credentials::CONFIG_PREFIX.$key;
        }
    }
}
