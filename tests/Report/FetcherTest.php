<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use P1ho\GoogleAnalyticsAPI\Report\Fetcher;

final class FetcherTest extends TestCase
{
    public function testFetcherInstantiation():void
    {
        // there is not a lot that can be tested here,
        // so will just test if class can be instantiated with proper path to credentials

        // to run this test, there needs to be a secrets.json that contains the
        // path to credentials file. This is explained in README.md
        // If the file is not found, the test won't be run
        $secretsPath = __DIR__ . '/../../secrets.json';
        if (file_exists($secretsPath)) {
            $secretsRaw = file_get_contents($secretsPath);
            $secrets = json_decode($secretsRaw);

            $hasError = false;
            try {
                $fetcher = new Fetcher($secrets->credentials);
            } catch (Exception $e) {
                $hasError = true;
            }
            $this->assertFalse($hasError);
        }

        // if credentials file do not exist throw error
        $hasError = false;
        try {
            $fetcher = new Fetcher('literally-some-random-path');
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);
    }
}
