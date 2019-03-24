<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use P1ho\GoogleAnalyticsAPI\Request\Package;
use P1ho\GoogleAnalyticsAPI\Request\Validator;

final class PackageTest extends TestCase
{
    public function testPackageCreation(): void
    {
        $validator = new Validator();
        $validDimensions = $validator->getValidDimensions();
        $validMetrics = $validator->getValidMetrics();

        $viewId = '12345678';
        $startDate = '2019-01-01';
        $endDate = '2019-03-01';
        $dimensions = array_slice($validDimensions, 0, 7);
        $metrics = array_slice($validMetrics, 0, 50);

        // valid inputs should not raise error
        $hasError = false;
        try {
            $package = new Package($viewId, $startDate, $endDate, $dimensions, $metrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);

        // because it's dependent on Validator, bad inputs should throw error
        $badDimensions = array_slice($validDimensions, 0, 8);

        $hasError = false;
        try {
            $package = new Package($viewId, '3019-01-01', $endDate, $badDimensions, $metrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $hasError = false;
        try {
            $package = new Package($viewId, $startDate, $endDate, $badDimensions, $metrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $badMetrics = array_slice($validMetrics, 0, 51);
        $hasError = false;
        try {
            $package = new Package($viewId, $startDate, $endDate, $dimensions, $badMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);
    }

    public function testPackageStructure(): void
    {
        $validator = new Validator();
        $validDimensions = $validator->getValidDimensions();
        $validMetrics = $validator->getValidMetrics();

        $viewId = '12345678';
        $startDate = '2019-01-01';
        $endDate = '2019-03-01';
        $dimensions = array_slice($validDimensions, 0, 7);
        $metrics = array_slice($validMetrics, 0, 50);

        $package = new Package($viewId, $startDate, $endDate, $dimensions, $metrics);

        $packageLevel_1 = $package->inspect();
        $this->assertArrayHasKey('viewId', $packageLevel_1);
        $this->assertArrayHasKey('reportsRequest', $packageLevel_1);

        $packageLevel_2 = $packageLevel_1['reportsRequest'];
        $this->assertEquals(get_class($packageLevel_2), 'Google_Service_AnalyticsReporting_GetReportsRequest');
        $this->assertEquals(count($packageLevel_2->getReportRequests()), 5);

        $packageLevel_3 = $packageLevel_2->getReportRequests()[0];
        $this->assertEquals(get_class($packageLevel_3), 'Google_Service_AnalyticsReporting_ReportRequest');
        $this->assertEquals($packageLevel_3->getViewId(), $viewId);

        $packageDateRanges = $packageLevel_3->getDateRanges();
        $this->assertEquals(count($packageDateRanges), 1);
        $packageDateRange = $packageDateRanges[0];
        $this->assertEquals(get_class($packageDateRange), 'Google_Service_AnalyticsReporting_DateRange');
        $this->assertEquals($packageDateRange->getStartDate(), $startDate);
        $this->assertEquals($packageDateRange->getEndDate(), $endDate);

        $packageDimensions = $packageLevel_3->getDimensions();
        $this->assertEquals(count($packageDimensions), 7);
        $packageDimensionSingle = $packageDimensions[0];
        $this->assertEquals(get_class($packageDimensionSingle), 'Google_Service_AnalyticsReporting_Dimension');
        $this->assertEquals($packageDimensionSingle->getName(), $dimensions[0]);

        $packageMetrics = $packageLevel_3->getMetrics();
        $this->assertEquals(count($packageMetrics), 10);
        $packageMetricSingle = $packageMetrics[0];
        $this->assertEquals(get_class($packageMetricSingle), 'Google_Service_AnalyticsReporting_Metric');
        $this->assertEquals($packageMetricSingle->getExpression(), $metrics[0]);

        // testing structure when a reportRequest doesn't have 10 metric
        $shorterMetrics = array_slice($validMetrics, 0, 25); // 25 = 10 + 10 + 5 metrics
        $package = new Package($viewId, $startDate, $endDate, $dimensions, $shorterMetrics);
        $packageLevel_1 = $package->inspect();
        $packageLevel_2 = $packageLevel_1['reportsRequest'];
        $this->assertEquals(count($packageLevel_2->getReportRequests()), 3); // 3 because 10 + 10 + 5 metrics
        $packageLevel_3 = $packageLevel_2->getReportRequests()[2];
        $this->assertEquals(count($packageLevel_3->getMetrics()), 5);
    }
}
