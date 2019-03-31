<?php declare(strict_types=1);

namespace P1ho\GoogleAnalyticsAPI\Report;

/**
 * This is just a wrapper class for the assemble function.
 * Because it doesn't have state nor instance variables, I'm making it static.
 */

class Assembler
{
    /**
     * __construct function. Set private to prevent instantiation
     */
    private function __construct()
    {
    }

    /**
     * public static run function.
     * The main assemble function that takes in an the FetchedData object and
     * reorganizes it into an associative array to be more humanly readable.
     * @param  FetchedData  $data
     * @return array
     * {
     *
     * }
     */
    public static function run(FetchedData $data): array
    {
        $report = [
            'request' => [
              'viewId' => $data->viewId,
              'startDate' => $data->startDate,
              'endDate' => $data->endDate,
              'dimensions' => $data->dimensions,
              'metrics' => $data->metrics,
              'filtersExp' => $data->filtersExp,
            ],
            'report' => self::_parseReturnedReports($data),
            'requestCost' => $data->callCount,
        ];

        return $report;
    }

    /**
     * private _parseReturnedReports function.
     * This function takes in the $data object and reorganizes its structure.
     * The new structure takes the form of:
     * {
     *    'totals': {
     *        'metricItem' => metricValue,
     *        'metricItem' => metricValue,
     *        ...
     *     },
     *     'rows': [
     *         {
     *             'dimensions' => {
     *             'dimensionItem' => dimensionValue,
     *             'dimensionItem' => dimensionValue,
     *             ...
     *             },
     *             'metrics' => {
     *             'metricItem' => metricValue,
     *             'metricItem' => metricValue,
     *             ...
     *             }
     *         }, ...
     *     ]
     * }
     * @param  FetchedData $data
     * @return array
     */
    private static function _parseReturnedReports(FetchedData $data): array
    {
        $builtReport = [];
        $batchGetReports = $data->returnedReports;
        $batchGetCount = count($batchGetReports);

        $dimensionsLookup = []; // keeps track of dimensions and its index for quicker insert.
        $dimensionsIndex = 0;
        $builtReport['totals'] = ['metrics' => []];
        $builtReport['rows'] = [];
        for ($batchGetIndex = 0; $batchGetIndex < $batchGetCount; $batchGetIndex++) {
            $singleBatchGetReport = $batchGetReports[$batchGetIndex];
            $requestCount = count($singleBatchGetReport->getReports());

            for ($reportIndex = 0; $reportIndex < $requestCount; $reportIndex++) {
                $singleReport = $singleBatchGetReport->reports[$reportIndex];
                $header = $singleReport->getColumnHeader();
                $dimensionHeaders = $header->getDimensions();
                $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
                $rows = $singleReport->getData()->getRows();
                $rowCount = count($rows);

                $totalMetricValues = $singleReport->getData()->getTotals()[0]->getValues();
                $totalMetricValuesCount = count($totalMetricValues);
                for ($i = 0; $i < $totalMetricValuesCount; $i++) {
                    $metricName = $metricHeaders[$i]->getName();
                    $builtReport['totals']['metrics'][$metricName] = $totalMetricValues[$i];
                }
                if ($rowCount !== 0) {
                    /*
                    The logic here is slightly complicated. Because Google
                    forces us to split our requests into at most 5 with
                    10 metrics each, this means we need to manaually append
                    data with the same dimensions from the 5 requests back
                    together (the rows will be different across requests).

                    This will utilize the $dimensionsLookup
                     */
                    for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
                        $row = $rows[$rowIndex];
                        $dimensions = $row->getDimensions();
                        $dimensionsCount = count($dimensions);
                        $dimensionsStr = $dimensionsCount !== 0 ? implode('_', $dimensions) : 0;
                        $dimensionsDict = [];
                        for ($i = 0; $i < $dimensionsCount; $i++) {
                            $dimensionsDict[$dimensionHeaders[$i]] = $dimensions[$i];
                        }
                        $metricValues = $row->getMetrics()[0]->getValues();
                        $metricValuesCount = count($metricValues);
                        if (isset($dimensionsLookup[$dimensionsStr])) {
                            $insertPos = $dimensionsLookup[$dimensionsStr];
                            for ($i = 0; $i < $metricValuesCount; $i++) {
                                $metricName = $metricHeaders[$i]->getName();
                                /*
                                even though the package sets includeEmptyRows to
                                false, if any one of the metrics in the subreport
                                has a non-zero value, Google will still return
                                all 10 metrics, so if the verbose mode is not
                                enabled, we will trim metrics that have 0 value.
                                 */
                                if ((int)$metricValues[$i] !== 0) {
                                    $builtReport['rows'][$insertPos]['metrics'][$metricName] = $metricValues[$i];
                                }
                            }
                        } else {
                            $builtReport['rows'][$dimensionsIndex] = [
                              'dimensions' => $dimensionsDict,
                              'metrics' => []
                            ];
                            for ($i = 0; $i < $metricValuesCount; $i++) {
                                $metricName = $metricHeaders[$i]->getName();
                                /*
                                even though the package sets includeEmptyRows to
                                false, if any one of the metrics in the subreport
                                has a non-zero value, Google will still return
                                all 10 metrics, so if the verbose mode is not
                                enabled, we will trim metrics that have 0 value.
                                 */
                                if ((int)$metricValues[$i] !== 0) {
                                    $builtReport['rows'][$dimensionsIndex]['metrics'][$metricName] = $metricValues[$i];
                                }
                            }
                            $dimensionsLookup[$dimensionsStr] = $dimensionsIndex;
                            $dimensionsIndex++;
                        }
                    }
                }
            }
        }
        return $builtReport;
    }
}
