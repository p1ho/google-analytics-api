<?php declare(strict_types=1);

namespace P1ho\GoogleAnalyticsAPI\Request;

/**
 * Validate parameters that will eventually be passed to a Google API call.
 * This reduces the number of errors and prevent wasting API call quotas
 * (because an API call with an error may still count towards daily quota)
 *
 * Validator fetches latest complete listing of all dimensions and metrics from
 * Google Metadata API and caches it for the day
 * (requires api-key on instantiation).
 *
 * The fetched listing will saved as json in "cache" folder at root.
 */

const METADATA_PATH = "https://www.googleapis.com/analytics/v3/metadata/ga/columns"; // v4 not supported yet
const CACHE_PATH = __DIR__ . '/../../cache/';
const CACHE = CACHE_PATH . 'DimensionsMetrics.json';
const CACHE_TTL = 86400000;
const MAX_DIMENSIONS = 7;
const MAX_METRICS = 50;

class Validator
{
    private $dimensions;
    private $metrics;

    /**
     * Construct the Validator
     */
    public function __construct()
    {
        $currentTime = time();
        if (file_exists(CACHE) && time() - filemtime(CACHE) >= CACHE_TTL) {
            $data = json_decode(file_get_contents(CACHE), true);
        } else {
            $data = $this->_fetchMetadata();
        }
        $this->dimensions = $data['dimensions'];
        $this->metrics = $data['metrics'];
    }

    /**
     * public pass function.
     * @param string $startDate (yyyy-mm-dd)
     * @param string $endDate (yyyy-mm-dd)
     * @param array $dimensions (array of strings)
     * @param array $metrics (array of strings)
     * @return bool (True if all validated)
     */
    public function pass(string $startDate, string $endDate, array $dimensions, array $metrics): bool
    {
        return $this->_datesValidated($startDate, $endDate) &&
           $this->_dimensionsValidated($dimensions) &&
           $this->_metricsValidated($metrics);
    }

    /**
     * public getValidDimensions function.
     * @return array
     */
    public function getValidDimensions(): array
    {
        return $this->dimensions;
    }

    /**
     * public getValidMetrics function.
     * @return array
     */
    public function getValidMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * private _fetchMetaData function.
     * Get, cache, and return Google Metadata API.
     * @return array (associative array with keys 'dimensions' and 'metrics')
     */
    private function _fetchMetadata(): array
    {
        if (!file_exists(CACHE)) {
            mkdir(CACHE_PATH);
        }

        $resp = file_get_contents(METADATA_PATH);
        $resp_json = json_decode($resp, true);

        $data = [
          'dimensions' => [],
          'metrics' => []
        ];

        foreach ($resp_json["items"] as $item) {
            if ($item["attributes"]["status"] !== "DEPRECATED") {
                if ($item["attributes"]["type"] === "DIMENSION") {
                    $data["dimensions"][] = $item["id"];
                } elseif ($item["attributes"]["type"] === "METRIC") {
                    $data["metrics"][] = $item["id"];
                }
            }
        }

        $cache = fopen(CACHE, 'w');
        fwrite($cache, json_encode($data));
        fclose($cache);

        return $data;
    }

    /**
     * private _datesValidated function.
     * check format and whether dates are in right order
     * @param string $startDate [date that can be parsed by strtotime()]
     * @param string $endDate [date that can be parsed by strtotime()]
     * @return bool
     */
    private function _datesValidated(string $startDate, string $endDate): bool
    {
        if ($startDate === '' || $endDate === '') {
            throw new \Exception($this->_datesErrorMsg('empty dates', $startDate, $endDate));
        } else {
            $startDateUnix = strtotime($startDate);
            $endDateUnix = strtotime($endDate);
            $todayUnix = strtotime('now');
            if ($startDateUnix !== false && $endDateUnix !== false) {
                if (+$startDateUnix > +$endDateUnix) {
                    throw new \Exception($this->_datesErrorMsg('end-date cannot precede start-date', $startDate, $endDate));
                } elseif ($startDateUnix > $todayUnix || $endDateUnix > $todayUnix) {
                    throw new \Exception($this->_datesErrorMsg('date cannot be in the future', $startDate, $endDate));
                } else {
                    return true;
                }
            } else {
                throw new \Exception($this->_datesErrorMsg('could not parse dates', $startDate, $endDate));
            }
        }
    }

    /**
     * private _datesErrorMsg function.
     * Helper function to generate Exception message.
     * @param  string $msg       [error message]
     * @param  string $startDate
     * @param  string $endDate
     * @return string
     */
    private function _datesErrorMsg(string $msg, string $startDate, string $endDate): string
    {
        return "Invalid Dates: $msg (entered '$startDate' - '$endDate').";
    }

    /**
     * private _dimensionsValidated function.
     * @param  array  $dimensions
     * @return bool
     */
    private function _dimensionsValidated(array $dimensions): bool
    {
        $excluded = $this->_getFirstExcluded($dimensions, $this->dimensions);
        if ($excluded !== null) {
            throw new \Exception("Invalid Dimension: $excluded is not an acceptable dimension.");
        }
        $duplicate = $this->_getFirstDuplicate($dimensions);
        if ($duplicate !== null) {
            throw new \Exception("Duplicate Dimension: $duplicate is entered repeatedly.");
        }
        $count = count($dimensions);
        if ($count > MAX_DIMENSIONS) {
            throw new \Exception("Too Many Dimensions: Only ".MAX_DIMENSIONS." dimensions allowed (You have $count)");
        }
        return true;
    }

    /**
     * private _metricsValidated function.
     * @param  array  $metrics
     * @return bool
     */
    private function _metricsValidated(array $metrics): bool
    {
        if (count($metrics) === 0) {
            throw new \Exception("Invalid Format: You did not enter any metrics.");
        }
        $excluded = $this->_getFirstExcluded($metrics, $this->metrics);
        if ($excluded !== null) {
            throw new \Exception("Invalid Metric: $excluded is not an acceptable metric.");
        }
        $duplicate = $this->_getFirstDuplicate($metrics);
        if ($duplicate !== null) {
            throw new \Exception("Duplicate Metric: $duplicate is entered repeatedly.");
        }
        $count = count($metrics);
        if ($count > MAX_METRICS) {
            throw new \Exception("Too Many Metrics: Only ".MAX_METRICS." metrics allowed (You have $count)");
        }
        return true;
    }

    /**
     * private _getFirstExcluded function.
     * get items in subset that are excluded in fullset
     * @param array $subset
     * @param array $fullset
     * @return string|null
     */
    private function _getFirstExcluded(array $subset, array $fullset): ?string
    {
        $lookup = array_flip($fullset);
        foreach ($subset as $item) {
            if (!isset($lookup[$item])) {
                return $item;
            }
        }
        return null;
    }

    /**
     * private _getFirstDuplicate function.
     * @param  array  $array
     * @return string|null
     */
    private function _getFirstDuplicate(array $array): ?string
    {
        $lookup = [];
        foreach ($array as $item) {
            if (in_array($item, $lookup)) {
                return $item;
            }
            $lookup[] = $item;
        }
        return null;
    }
}
