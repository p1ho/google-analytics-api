<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use P1ho\GoogleAnalyticsAPI\Request\Package;
use P1ho\GoogleAnalyticsAPI\Report\Fetcher;
use P1ho\GoogleAnalyticsAPI\Report\Assembler;

final class AssemblerTest extends TestCase
{
    public function testReportGeneration():void
    {

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
        $package = new Package($viewId, $startDate, $endDate, $dimensions, $metrics);
        $report = $fetcher->getData($package);

        $hasError = false;
        try {
            $reportAssembled = Assembler::run($report);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);
        $this->assertEquals(gettype($reportAssembled), 'array');

        // test basic structure (totals/rows)
        $this->assertEquals($reportAssembled["request"]["viewId"], $viewId);
        $this->assertEquals($reportAssembled["request"]["startDate"], date('Y-m-d', strtotime($startDate)));
        $this->assertEquals($reportAssembled["request"]["endDate"], date('Y-m-d', strtotime($endDate)));
        $this->assertEquals($reportAssembled["request"]["dimensions"], $dimensions);
        $this->assertEquals($reportAssembled["request"]["metrics"], $metrics);
        $this->assertEquals(array_keys($reportAssembled["report"]), ["totals", "rows"]);
        $this->assertEquals(array_keys($reportAssembled["report"]["totals"]), ["metrics"]);
        $this->assertEquals(array_keys($reportAssembled["report"]["totals"]["metrics"]), $metrics);
        // the following test only runs when there's actually data
        if (isset($reportAssembled["report"]["rows"][0])) {
            $rowData = $reportAssembled["report"]["rows"][0];
            $this->assertEquals(array_keys($rowData), ["dimensions", "metrics"]);
            $this->assertEquals(array_keys($rowData["dimensions"]), $dimensions);
            // test that no metric values are 0
            foreach ($rowData["metrics"] as $metricDatum) {
                $this->assertNotEquals($metricDatum, 0);
            }
        }


        // try to fetch report without dimensions specified (should only be 1 row)
        $package = new Package($viewId, $startDate, $endDate, [], $metrics);
        $report = $fetcher->getData($package);
        $hasError = false;
        try {
            $reportAssembled = Assembler::run($report);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);
        $this->assertEquals(gettype($reportAssembled), 'array');

        // try to fetch report with metrics that only support total values
        $metrics = [
            "ga:transactionShipping",
            "ga:transactionTax",
            "ga:totalValue",
            "ga:itemQuantity",
            "ga:uniquePurchases",
            "ga:revenuePerItem",
            "ga:itemRevenue",
            "ga:itemsPerPurchase",
            "ga:buyToDetailRate",
            "ga:cartToDetailRate",
            "ga:productAddsToCart",
            "ga:productCheckouts",
            "ga:productDetailViews",
            "ga:productListCTR",
            "ga:productListClicks",
            "ga:productListViews",
            "ga:productRefundAmount",
            "ga:productRefunds",
            "ga:productRemovesFromCart",
            "ga:productRevenuePerPurchase",
            "ga:quantityAddedToCart",
            "ga:quantityCheckedOut",
            "ga:quantityRefunded",
            "ga:quantityRemovedFromCart",
            "ga:refundAmount",
            "ga:revenuePerUser",
            "ga:totalRefunds",
            "ga:transactionsPerUser"
        ];
        $package = new Package($viewId, $startDate, $endDate, $dimensions, $metrics);
        $report = $fetcher->getData($package);
        $hasError = false;
        try {
            $reportAssembled = Assembler::run($report);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);
        $this->assertEquals(gettype($reportAssembled), 'array');

    }
}
