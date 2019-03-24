<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use P1ho\GoogleAnalyticsAPI\Report\Fetcher;
use P1ho\GoogleAnalyticsAPI\Report\FetchedData;
use P1ho\GoogleAnalyticsAPI\Request\Package;

final class FetcherTest extends TestCase
{
    public function testFetcherInstantiation():void
    {

        // if credentials file do not exist throw error
        $hasError = false;
        try {
            $fetcher = new Fetcher('literally-some-random-path');
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        /*
        To run this test, there needs to be a secrets.json at package root that
        contains the following:
        {
          "credentials": "path-to-your-google-credential-file",
          "viewId": "a view id that your account has access to"
        }
         */

        $secretsPath = __DIR__ . '/../../secrets.json';
        if (!file_exists($secretsPath)) {
            throw new Exception("You haven't set up secrets.json at package root, please see README.md development section.");
        }

        $secretsRaw = file_get_contents($secretsPath);
        $secrets = json_decode($secretsRaw);
        $fetcher = new Fetcher($secrets->credentials);

        // sending real requests, so use pretested dimensions/metrics
        $compatibleDimensionsMetrics = json_decode(
            file_get_contents(__DIR__ . '/longest-compatible-dimensions-metrics.json')
        );

        $viewId = $secrets->viewId;
        $startDate = 'yesterday';
        $endDate = 'today';
        $dimensions = array_slice($compatibleDimensionsMetrics->dimensions, 0, 7);
        $metrics = array_slice($compatibleDimensionsMetrics->metrics, 0, 50);
        $package = new Package($viewId, $startDate, $endDate, $dimensions, $metrics);

        // no error should be thrown when get Data is called
        $hasError = false;
        try {
            $report = $fetcher->getData($package);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);
        $this->assertEquals(get_class($report), 'P1ho\GoogleAnalyticsAPI\Report\FetchedData');

        // can't reliably test other things because this will be vastly different
        // across sites.
    }
}
