<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use P1ho\GoogleAnalyticsAPI\Report\FetchedData;

final class FetchedDataTest extends TestCase
{
    public function testSetterMethods(): void
    {
        $viewId = '12345678';
        $startDate = 'yesterday';
        $endDate = 'tomorrow';
        $dimensions = ['dimension-item'];
        $metrics = ['metric-item', 'metric-item'];
        $filtersExp = 'some-filter-expression';
        $callCount = 1;
        $returnedReports = [new \StdClass(), new \StdClass()];

        $data = new FetchedData();

        $hasError = false;
        try {
            $data->setViewId($viewId);
            $data->setStartDate($startDate);
            $data->setEndDate($endDate);
            $data->setDimensions($dimensions);
            $data->setMetrics($metrics);
            $data->setFiltersExp($filtersExp);
            $data->setCallCount($callCount);
            $data->setReturnedReports($returnedReports);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertFalse($hasError);

        $this->assertEquals($data->viewId, $viewId);
        $this->assertEquals($data->startDate, $startDate);
        $this->assertEquals($data->endDate, $endDate);
        $this->assertEquals($data->dimensions, $dimensions);
        $this->assertEquals($data->metrics, $metrics);
        $this->assertEquals($data->filtersExp, $filtersExp);
        $this->assertEquals($data->callCount, $callCount);
        $this->assertEquals($data->returnedReports, $returnedReports);
    }
}
