<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use P1ho\GoogleAnalyticsAPI\Client;

final class ClientTest extends Testcase
{
    public function testInstantiation(): void
    {
        $hasError = false;
        try {
            $googleAnalytics = new Client(__DIR__.'/Report/TestCredentials.json');
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);
    }

}
