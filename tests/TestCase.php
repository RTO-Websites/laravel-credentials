<?php
/**
 * TestCase.php
 *
 * @author RTO GmbH <kdhp-dev@rto.de>
 * @licence GPL-3.0
 */

namespace RtoWebsites\Credentials\Tests;


use Illuminate\Support\Facades\Config;
use RtoWebsites\Credentials\Providers\CredentialsServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * @inheritDoc
     */
    protected function getPackageProviders($app)
    {
        return [CredentialsServiceProvider::class];
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        Config::set('credentials.file', __DIR__ . '/temp/credentials.php.enc');
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        @unlink(Config::get('credentials.file'));
    }
}