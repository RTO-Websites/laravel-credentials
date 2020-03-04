<?php
/**
 * EditCredentialsCommandTest.php
 *
 * @author RTO GmbH <kdhp-dev@rto.de>
 * @licence GPL-3.0
 */

namespace RtoWebsites\Credentials\Tests\Commands;


use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use RtoWebsites\Credentials\Commands\EditCredentialsCommand;
use RtoWebsites\Credentials\Credentials;
use RtoWebsites\Credentials\Exceptions\InvalidJSON;
use RtoWebsites\Credentials\Tests\TestCase;

class EditCredentialsCommandTest extends TestCase
{
    /**
     * @test
     */
    public function the_edit_credentials_command_creates_the_credentials_file()
    {
        $this->assertFileNotExists($file = Config::get('credentials.file'));

        /* @var Credentials $credentials */
        $credentials = $this->app->get(Credentials::class);

        /* @var EditCredentialsCommand $command */
        $command = $this->partialMock(EditCredentialsCommand::class, function (MockInterface $mock) {
            $mock->shouldReceive('runEditor', 'info');
        });
        $command->handle($credentials);

        $this->assertFileExists($file);
    }

    /**
     * @test
     * @dataProvider invalidJsonDataProvider
     */
    public function the_credentials_file_must_contain_valid_json($invalidJson)
    {
        $this->expectException(InvalidJSON::class);

        /* @var Credentials $credentials */
        $credentials = $this->app->get(Credentials::class);

        /* @var EditCredentialsCommand $command */
        $command = $this->partialMock(EditCredentialsCommand::class, function (MockInterface $mock) use ($invalidJson) {
            $mock->shouldReceive('info');
            $mock->shouldReceive('runEditor')->andReturnUsing(function ($file) use ($invalidJson) {
                file_put_contents($file, $invalidJson);
            });
        });
        $command->handle($credentials);
    }

    /**
     * @return array
     */
    public function invalidJsonDataProvider(): array
    {
        $maximumStackDepth = [];
        $this->arrayDepth($maximumStackDepth, $depth = 512);

        return [
            [json_encode($maximumStackDepth, 0, $depth + 1)], // JSON_ERROR_DEPTH
            ['{"state": "mismatch" ]'], // JSON_ERROR_STATE_MISMATCH
            // TODO: Add data to test JSON_ERROR_CTRL_CHAR
            ['syntax error'], // JSON_ERROR_SYNTAX
            ["\xB1\x31"], // JSON_ERROR_UTF8
        ];

    }

    /**
     * @param array $array
     * @param int $toGo
     */
    private function arrayDepth(array &$array, int $toGo)
    {
        for ($i = 0; $i < $toGo; ++$i) {
            $tmp = [$array];
            $array = $tmp;
        }
    }
}