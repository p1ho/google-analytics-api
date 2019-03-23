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
const CACHE_PATH = __DIR__ . '/../../../cache/';
const CACHE = CACHE_PATH . 'DimensionsMetrics.json';
const CACHE_TTL = 86400000;
const MAX_DIMENSIONS = 7;
const MAX_METRICS = 50;

class Validator {

  private $dimensions;
  private $metrics;
  private $apiKey;

  /**
   * Construct the Validator
   */
  public function __construct() {
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
   * @return boolean (True if all validated)
   */
  public function pass(string $startDate, string $endDate, array $dimensions, array $metrics) {
    return $this->_datesValidated($startDate, $endDate) &&
           $this->_dimensionsValidated($dimensions) &&
           $this->_metricsValidated($metrics);
  }

  /**
   * private _fetchMetaData function.
   * Get, cache, and return Google Metadata API.
   * @return array (associative array with keys 'dimensions' and 'metrics')
   */
  private function _fetchMetadata() {
    if (!file_exists(CACHE)) { mkdir(CACHE_PATH); }

    $resp = file_get_contents(METADATA_PATH);
    $resp_json = json_decode($resp, true);

    $data = [
      'dimensions' => [],
      'metrics' => []
    ];

    foreach($resp_json["items"] as $item) {
      if ($item["attributes"]["status"] !== "DEPRECATED") {
        if ($item["attributes"]["type"] === "DIMENSION") {
          $data["dimensions"][] = $item["id"];
        } else if ($item["attributes"]["type"] === "METRIC") {
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
   * @param string $startDate (yyyy-mm-dd)
   * @param string $endDate (yyyy-mm-dd)
   * @return boolean
   */
  private function _datesValidated(string $startDate, string $endDate) {
    if ($this->_dateValidated($startDate) && $this->_dateValidated($endDate)) {
      // Check if EndDate is not before StartDate
      if ((int)str_replace("-","", $startDate) > (int)str_replace("-","",$endDate)) {
        throw new Exception("Invalid Dates: end-date cannot precede start-date (entered $startDate - $endDate).");
      }
      return true;
    } else {
      return false;
    }
  }

  /**
   * private _dateValidated function.
   * check if date format is 'yyyy-mm-dd'.
   * @param string $date
   * @return boolean
   */
  private function _dateValidated(string $date) {
    if ($date[4] !== '-' || $date[7] !== '-' || !ctype_digit(str_replace("-","", $date))) {
      throw new Exception("Invalid Format: date must be yyyy-mm-dd (entered $date).");
    }
    return true;
  }

  /**
   * private _dimensionsValidated function.
   * @param  array  $dimensions
   * @return boolean
   */
  private function _dimensionsValidated(array $dimensions) {
    $excluded = $this->_getFirstExcluded($dimensions, $this->$dimensions);
    if ($excluded !== null) {
      throw new Exception("Invalid Dimension: $excluded is not an acceptable dimension.");
    }
    $duplicate = $this->_getFirstDuplicate($dimensions);
    if ($duplicate !== null) {
      throw new Exception("Duplicate Dimension: $duplicate is entered repeatedly.");
    }
    return true;
  }

  /**
   * private _metricsValidated function.
   * @param  array  $metrics
   * @return boolean
   */
  private function _metricsValidated(array $metrics) {
    if (count($metrics) === 0) {
      throw new Exception("Invalid Format: You did not enter any metrics.");
    }
    $excluded = $this->_getFirstExcluded($metrics, $this->metrics);
    if ($excluded !== null) {
      throw new Exception("Invalid Metric: $excluded is not an acceptable metric.");
    }
    $duplicate = $this->_getFirstDuplicate($metrics);
    if ($duplicate !== null) {
      throw new Exception("Duplicate Metric: $duplicate is entered repeatedly.");
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
  private function _getFirstExcluded(array $subset, array $fullset) {
    $lookup = array_flip($fullset);
    foreach($subset as $item) {
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
  private function _getFirstDuplicate(array $array) {
    $lookup = [];
    foreach($array as $item) {
      if (isset($lookup[$item])) {
        return $item;
      }
      $lookup[] = $item;
    }
    return null;
  }

}