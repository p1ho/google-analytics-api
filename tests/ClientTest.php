<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use P1ho\GoogleAnalyticsAPI\Client;

final class ClientTest extends Testcase
{
    public function testInstantiation(): void
    {
        /*
        To run this test, there needs to be a secrets.json at package root that
        contains the following:
        {
          "credentials": "path-to-your-google-credential-file",
          "viewId": "a view id that your account has access to"
        }
         */

        $secretsPath = __DIR__ . '/../secrets.json';
        if (!file_exists($secretsPath)) {
            throw new Exception("You haven't set up secrets.json at package root, please see README.md development section.");
        }

        $secretsRaw = file_get_contents($secretsPath);
        $secrets = json_decode($secretsRaw);

        $hasError = false;
        try {
            $googleAnalytics = new Client($secrets->credentials);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);
    }

    public function testGetData(): void
    {
        /*
        To run this test, there needs to be a secrets.json at package root that
        contains the following:
        {
          "credentials": "path-to-your-google-credential-file",
          "viewId": "a view id that your account has access to"
        }
         */

        $secretsPath = __DIR__ . '/../secrets.json';
        if (!file_exists($secretsPath)) {
            throw new Exception("You haven't set up secrets.json at package root, please see README.md development section.");
        }
        $secretsRaw = file_get_contents($secretsPath);
        $secrets = json_decode($secretsRaw);
        $googleAnalytics = new Client($secrets->credentials);

        $viewId = $secrets->viewId;
        $startDate = 'yesterday';
        $endDate = 'today';
        $dimensions = [
            "ga:date",
            "ga:city",
            "ga:cityId",
            "ga:fullReferrer",
            "ga:networkLocation",
            "ga:deviceCategory",
            "ga:pagePath",
        ];
        $metrics = [
            "ga:users",
            "ga:newUsers",
            "ga:percentNewSessions",
            "ga:sessionsPerUser",
            "ga:sessions",
            "ga:bounces",
            "ga:bounceRate",
            "ga:sessionDuration",
            "ga:avgSessionDuration",
            "ga:uniqueDimensionCombinations",
            "ga:hits",
            "ga:organicSearches",
            "ga:goalStartsAll",
            "ga:goalCompletionsAll",
            "ga:goalValueAll",
            "ga:goalValuePerSession",
            "ga:goalConversionRateAll",
            "ga:goalAbandonsAll",
            "ga:goalAbandonRateAll",
            "ga:pageValue",
            "ga:entrances",
            "ga:entranceRate",
            "ga:pageviews",
            "ga:pageviewsPerSession",
            "ga:uniquePageviews",
            "ga:timeOnPage"
        ];

        $hasError = false;
        try {
            $report = $googleAnalytics->getData($viewId, $startDate, $endDate, $dimensions, $metrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);
        $this->assertEquals(gettype($report), 'array');
    }
}
