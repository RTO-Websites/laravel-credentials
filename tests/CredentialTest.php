<?php

namespace RtoWebsites\Credentials\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;
use RtoWebsites\Credentials\Credentials;
use RtoWebsites\Credentials\CredentialsServiceProvider;
use RtoWebsites\Credentials\EditCredentialsCommand;

class CredentialTest extends TestCase
{
    use WithFaker;

    protected function getPackageProviders($app)
    {
        return [CredentialsServiceProvider::class];
    }

    public function setUp(): void
    {
        parent::setUp();
        Config::set('credentials.file', __DIR__ . '/temp/credentials.php.enc');
    }

    public function tearDown(): void
    {
        @unlink(Config::get('credentials.file'));
    }

    /**
     * @test
     */
    public function credentials_are_stored_encrypted()
    {
        Config::set('credentials.key', $credentialsKey = Str::random(32));

        /* @var Credentials $credentials */
        $credentials = $this->app->get(Credentials::class);

        $this->assertFileNotExists($file = Config::get('credentials.file'));
        $this->assertEmpty($credentials->load($file));

        $secretData = [
            'key' => $secretPassword = $this->faker->password,
        ];

        $credentials->store($secretData, $file);

        $this->assertFileExists($file);

        $encryptedData = file_get_contents($file);

        $this->assertStringNotContainsString($secretPassword, $encryptedData);
        $this->assertEquals($secretData, $credentials->load($file));
        $this->assertEquals($secretPassword, $credentials->get('key'));

    }

    /**
     * @test
     */
    public function the_credentials_key_can_be_base_64_encoded()
    {
        $credentialsKey = base64_encode(Str::random(32));
        Config::set('credentials.key', "base64:$credentialsKey");

        $secretData = [
            'key' => $secretPassword = $this->faker->password,
        ];

        /* @var Credentials $credentials */
        $credentials = $this->app->get(Credentials::class);
        $credentials->store($secretData, $file = Config::get('credentials.file'));
        $loadedData = $credentials->load($file);

        $this->assertEquals($secretData, $loadedData);
        $this->assertEquals($secretPassword, $credentials->get('key'));
    }

    /**
     * @test
     */
    public function it_can_use_the_helper_function()
    {
        $secretData = [
            'key' => $secretPassword = $this->faker->password,
        ];

        /* @var Credentials $credentials */
        $credentials = $this->app->get(Credentials::class);
        $credentials->store($secretData, Config::get('credentials.file'));

        $this->assertSame($secretPassword, credentials('key'));
    }

    /**
     * @test
     */
    public function the_edit_credentials_command_creates_the_credentials_file()
    {
        $this->assertFileNotExists($file = Config::get('credentials.file'));

        /* @var Credentials $credentials */
        $credentials = $this->app->get(Credentials::class);

        /* @var EditCredentialsCommand $command */
        $command = $this->mock(EditCredentialsCommand::class, function (MockInterface $mock) {
            $mock->makePartial()->shouldReceive('runEditor', 'info');
        });
        $command->handle($credentials);

        $this->assertFileExists($file);
    }

}
