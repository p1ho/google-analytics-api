<?php declare(strict_types=1);

namespace P1ho\GoogleAnalyticsAPI\Report;

use P1ho\GoogleAnalyticsAPI\Request\Package;

/**
 * This class is a wrapper around Google_Service_AnalyticsReporting.
 * It will handle requests that need more than one batchGet() calls
 */

/*
Setting fetch interval to 0 for now, but if issues come up, will increase this value.
 */
const FETCH_INTERVAL = 0;

class Fetcher
{
    private $client;
    private $analyticsService;

    /**
     * [__construct description]
     * @param string $pathToCredentials [description]
     */
    public function __construct(string $pathToCredentials)
    {
        if (!file_exists($pathToCredentials)) {
            throw new \Exception("Invalid Argument: credentials file not found ('$pathToCredentials' entered)");
        }

        putenv("GOOGLE_APPLICATION_CREDENTIALS=$pathToCredentials");
        $this->client = new \Google_Client();
        $this->client->useApplicationDefaultCredentials();
        $this->client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        $this->analyticsService = new \Google_Service_AnalyticsReporting($this->client);
    }

    /**
     * public getData function.
     * use the instantiated analytics service to get reports.
     * If the result count is so large that it requires multiple calls, this
     * will handle that. However, it's unlikely because it would be a lot of
     * memory usage.
     * @param  Package $package [request package]
     * @return object [has attributes 'callCount' and 'returnedReports']
     */
    public function getData(Package $package): \stdClass
    {
        $reportsRequest = $package->reportsRequest;
        $data = (object) [
          'callCount' => 1,
          'returnedReports' => []
        ];
        $returnedReport = $this->analyticsService->reports->batchGet($reportsRequest);
        $data->returnedReports[] = $returnedReport;

        // Handle cases where results may exceed pagesize_limit.
        // (This is highly unlikely, as there's probably going to be a memory_size
        // error thrown before the array can get this large)
        $nextPageToken = $this->_getNextPageToken($returnedReport);
        while ($nextPageToken !== null) {
            // set new page token for each report_request
            foreach ($reportsRequest->getReportRequests() as $reportRequest) {
                $reportRequest->setPageToken($nextPageToken);
            }
            if (FETCH_INTERVAL !== 0) {
                sleep(FETCH_INTERVAL);
            }
            $returnedReport = $this->analyticsService->reports->batchGet($reportRequest);
            $data->callCount += 1;
            $data->returnedReports[] = $returnedReport;
            $nextPageToken = $this->_getNextPageToken($returnedReport);
        }

        return $data;
    }


    /**
     * private _getNextPageToken function.
     * Helper to get token when there are unfetched data due to pagesize_limit
     * (Loops through all requests in the report)
     * @param  array [$returnedReport]
     * @return string|null [depending on whether nextPageToken was found]
     */
    private function _getNextPageToken(\Google_Service_AnalyticsReporting_GetReportsResponse $returnedReport): ?string
    {
        foreach ($returnedReport as $report) {
            $nextPageToken = $report->getNextPageToken();
            if ($nextPageToken !== null) {
                return $nextPageToken;
            }
        }
        return null;
    }
}
