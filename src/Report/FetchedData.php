<?php declare(strict_types=1);

namespace P1ho\GoogleAnalyticsAPI\Report;

/**
 * An object that contains returned data from Google Analytics BatchGet()
 */

class FetchedData
{
    public $viewId;
    public $startDate;
    public $endDate;
    public $dimensions;
    public $metrics;
    public $filtersExp;
    public $callCount;
    public $returnedReports;

    public function setViewId(string $viewId): void
    {
        $this->viewId = $viewId;
    }

    public function setStartDate(string $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function setEndDate(string $endDate): void
    {
        $this->endDate = $endDate;
    }
    public function setDimensions(array $dimensions): void
    {
        $this->dimensions = $dimensions;
    }
    public function setMetrics(array $metrics): void
    {
        $this->metrics = $metrics;
    }
    public function setFiltersExp(string $filtersExp): void
    {
        $this->filtersExp = $filtersExp;
    }

    public function setCallCount(int $callCount): void
    {
        $this->callCount = $callCount;
    }

    public function setReturnedReports(array $returnedReports): void
    {
        $this->returnedReports = $returnedReports;
    }
}
