<?php declare(strict_types=1);

namespace P1ho\GoogleAnalyticsAPI\Request;

/**
 * Accepts parameters that will be used to make a Google API call.
 * Will generate packages that can be passed to the Report\Fetcher.
 */

/*
 These numbers can be found at:
 Reports Request Limit: https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#request-body
 Metrics Limit: https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#ReportRequest.FIELDS.metrics
 Page Size Limit: https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#ReportRequest.FIELDS.page_size
 */
const REPORT_REQUESTS_LIMIT = 5;
const METRICS_LIMIT = 10;
const PAGESIZE_LIMIT = 100000;

class Package
{
    public $viewId;
    public $startDate;
    public $endDate;
    public $dimensions;
    public $metrics;
    public $filtersExp;
    public $reportsRequest;

    /**
     * __construct function
     * @param string $viewId     [Google designated view id for the site]
     * @param string $startDate  [yyyy-mm-dd]
     * @param string $endDate    [yyyy-mm-dd]
     * @param array  $dimensions [array of dimensions]
     * @param array  $metrics    [array of metrics]
     */
    public function __construct(string $viewId, string $startDate, string $endDate, array $dimensions, array $metrics, string $filtersExp = '')
    {
        $this->viewId = $viewId;
        $validator = new Validator();

        // validator will throw error if it does not pass
        if ($validator->pass($startDate, $endDate, $dimensions, $metrics)) {
            $this->startDate = date('Y-m-d', strtotime($startDate));
            $this->endDate = date('Y-m-d', strtotime($endDate));
            $this->dimensions = $dimensions;
            $this->metrics = $metrics;
            $this->filtersExp = $filtersExp;
            $this->_buildReportsRequest();
        }
    }

    /**
     * private _buildReportsRequest function.
     * Build the Google ReportsRequest based on how many metrics there are.
     */
    private function _buildReportsRequest(): void
    {
        $metricListSeparated = $this->_getMetricListSeparated();

        $reportRequestList = [];
        foreach ($metricListSeparated as $metricList) {
            $reportRequest = new \Google_Service_AnalyticsReporting_ReportRequest();
            $reportRequest->setIncludeEmptyRows(false); // subreport where all metrics have 0 values are not returned
            $reportRequest->setPageSize(PAGESIZE_LIMIT);
            $reportRequest->setViewId($this->viewId);
            $reportRequest->setDateRanges([$this->_getDateRange()]); // has to wrap in array
            $reportRequest->setMetrics($metricList);
            $reportRequest->setDimensions($this->_getDimensionList());
            $reportRequest->setFiltersExpression($this->filtersExp);
            $reportRequestList[] = $reportRequest;
        }
        $reportsRequest = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $reportsRequest->setReportRequests($reportRequestList);
        $this->reportsRequest = $reportsRequest;
    }

    /**
     * private _getDateRange function.
     * Build Google DateRange Object from StartDate and EndDate.
     * @return \Google_Service_AnalyticsReporting_DateRange
     */
    private function _getDateRange(): \Google_Service_AnalyticsReporting_DateRange
    {
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($this->startDate);
        $dateRange->setEndDate($this->endDate);
        return $dateRange;
    }

    /**
     * private _getDimensionList function.
     * @return array [Google Dimension Object from User Defined Dimensions]
     */
    private function _getDimensionList(): array
    {
        $dimensionList = [];
        foreach ($this->dimensions as $dimension) {
            $dimensionObj = new \Google_Service_AnalyticsReporting_Dimension();
            $dimensionObj->setName($dimension);
            $dimensionList[] = $dimensionObj;
        }
        return $dimensionList;
    }

    /**
     * private _getMetricListSeparated function.
     * @return array [contains arrays of Google Metric Object that can fit in a single reportRequest]
     */
    private function _getMetricListSeparated(): array
    {
        $reportRequestsRequired = ceil(count($this->metrics) / METRICS_LIMIT);
        $metricListSeparated = [];
        for ($i = 0; $i < $reportRequestsRequired; $i++) {
            $metricsOneReportRequest = [];
            for ($j = 0; $j < METRICS_LIMIT; $j++) {
                if (isset($this->metrics[$i * METRICS_LIMIT + $j])) {
                    $metric = $this->metrics[$i * METRICS_LIMIT + $j];
                    $metric_obj = new \Google_Service_AnalyticsReporting_Metric();
                    $metric_obj->setExpression($metric);
                    $metricsOneReportRequest[] = $metric_obj;
                } else {
                    break;
                }
            }
            $metricListSeparated[] = $metricsOneReportRequest;
        }
        return $metricListSeparated;
    }
}
