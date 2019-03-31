<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use P1ho\GoogleAnalyticsAPI\Report\Fetcher;

final class FetcherTest extends TestCase
{
    public function testFetcherInstantiation(): void
    {

        // if credentials file do not exist throw error
        $hasError = false;
        try {
            $fetcher = new Fetcher('literally-some-random-path');
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // if credentials file exist, it should instantiate without error
        $hasError = false;
        try {
            $fetcher = new Fetcher(__DIR__ . '/TestCredentials.json');
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);

        /*
        not testing the batchGet call because it will be inconsistent across devices
        and sites, and it's not really testing the System Under Testing.
         */
    }
}
