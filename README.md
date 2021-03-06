# README
[![Build Status](https://travis-ci.com/p1ho/google-analytics-api.svg?branch=master)](https://travis-ci.com/p1ho/google-analytics-api)
[![Coverage Status](https://coveralls.io/repos/github/p1ho/google-analytics-api/badge.svg)](https://coveralls.io/github/p1ho/google-analytics-api)
# Table of Content
* [Introduction](#introduction)
* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
* [Report Structure](#report-structure)
* [Development](#development)
* [Contributors](#contributors)

# Introduction

Google has a [PHP API Library](https://github.com/googleapis/google-api-php-client) that allows making requests to Google APIs from a PHP execution, including [Reporting API v4](https://developers.google.com/analytics/devguides/reporting/core/v4/) which enables access to Google Analytics data. However, while the library is quite powerful, it can be unintuitive as users have to create objects from the library ([Google's Tutorial for PHP](https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/service-php)), which takes a lot of digging in the documentation.

As someone who had to use this library extensively, I wanted something that's more intuitive. Specifically, if I'm already familiar with Google Analytics and the [dimensions and metrics](https://developers.google.com/analytics/devguides/reporting/core/dimsmets) available, I should immediately be able to make requests and get my data; and thus this package was born.

## Features

* **Validation**: If you make an API request and you had a typo, Google would return a [400 Bad Request](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400). In this case you didn't get your data, but from my experience it would still eat into your [daily quota](https://developers.google.com/analytics/devguides/reporting/core/v4/limits-quotas#analytics_reporting_api_v4). This package uses cached results from [metadata API](https://developers.google.com/analytics/devguides/reporting/metadata/v3/) which helps with offline validation. (Note: some dimensions and metrics cannot be queried together, this validator as of now does not address that)

* **Optimize Requests**: Google API forces users to break apart requests:
  - [A batchGet() request can take 5 subrequests](https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#request-body).
  - [A subrequest can have 10 metrics](https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#ReportRequest.FIELDS.metrics)

  This means it can be difficult to aggregate the requests needed for a batchGet() call. If done incorrectly, this can lead to wasting space (e.g., having a lower metrics count than 10 in a subrequest). This package optimizes the packaging process of building a `batchGet()` request so no space is wasted in a single request.

* **Intuitive Reports**: Just as this package helps you aggregate the requests, it also reorganizes the fragmented reports Google returned so it's structured minimally and intuitively. See [Report Structure](#report-structure) for details.


# Requirements

* [Create a Service Account](https://developers.google.com/api-client-library/php/auth/service-accounts#creatinganaccount) and store the downloaded `json` file somewhere secure. This is needed to make calls to Google API.

* Add the created Service Account Email to your Google Analytics view. You can do this by going to your [Analytics Homeapge](https://analytics.google.com) &rarr; Admin &rarr; User Management.

* Note: If the above seemed confusing, there is a [very well written tutorial](https://github.com/spatie/laravel-analytics#how-to-obtain-the-credentials-to-communicate-with-google-analytics) on an older Google interface.

# Installation

This package is available through [Composer](https://getcomposer.org/), and can be installed by entering:

```Bash
$ composer require p1ho/google-analytics-api
```

# Usage

```PHP
<?php

require_once __DIR__ . '/vendor/autoload.php';

use P1ho\GoogleAnalyticsAPI\Client;

$googleAnalytics = new Client('path/to/your/credentials/json');

// this is the view id on your Google Analytics page that represents the website.
$viewId = '12345678';

// dates can be anything that can be parsed by strtotime()
$startDate = 'yesterday';
$endDate = 'today';

/*
For complete listing of dimensions and metrics,
see https://developers.google.com/analytics/devguides/reporting/core/dimsmets
 */

// no longer than 7
$dimensions = [
  'ga:city',
  'ga:cityId'
];
// no longer than 50
$metrics = [
  'ga:users',
  'ga:sessions'
];

// queries and get organized data
$report = $googleAnalytics->getData($viewId, $startDate, $endDate, $dimensions, $metrics);
```
If needed, you can also add a [filter expression](https://developers.google.com/analytics/devguides/reporting/core/v3/reference#filters)
```PHP
<?php

// your code from before

$filtersExp = "your-filter-expression-here";
$report = $googleAnalytics
            ->getData($viewId, $startDate, $endDate, $dimensions, $metrics, $filtersExp);

```
Note: The filter expression is the reason why I still require the `ga:` prefixes, if I had taken it out, it could make writing the filters expression confusing. I could also make it so the getData() method receives optional parameters `array $dimensionFilters` and `array $metricFilters`, but even that could introduce confusion. If this is useful to anyone, please open an issue and let me know your preference, and why.

# Report Structure
```JavaScript
{
  // how much of daily quota was spent
  "requestCost": 1,

  // The request that was sent to Google API
  "request": {
    "viewId": "12345678",
    "startDate": "yyyy-mm-dd",
    "endDate": "yyyy-mm-dd",
    "dimensions": [
      "dimension",
      "dimension"
    ],
    "metrics": [
      "metric",
      "metric"
    ],
    "filtersExp": "specified-filters-expression"
  },

  // The returned report
  "report": {

    // the summed metric values in the date range you specified
    "totals": {
      "metric": "total-value",
      "metric": "total-value"
    },

    // each row contains the metric values with a unique dimension combination
    // If all metric values for a row are 0, the row will not display
    // Any metric within a row with a value of 0 will not display
    "rows": [
      {
        "dimensions": {
          "dimension": "value",
          "dimension": "value"
        },
        "metrics": {
          "metric": "value",
          "metric": "value"
        }
      },
      {
        "dimensions": {
          "dimension": "value",
          "dimension": "value"
        },
        "metrics": {
          "metric": "value",
          "metric": "value"
        }
      }
    ]
  }
}
```
Note: Empty rows and 0 metric values are not displayed to minimize memory usage. Since users have the list of metrics used to make the requests, the 0 can be implied.
# Development

* `$ composer test` will run all the tests (use `$ composer test-win` on Windows). To enable coverage analysis, a code coverage driver is needed. I used [Xdebug](https://xdebug.org/index.php) when developing on Windows. Afterwards, run `$ composer phpcov-merge` (use `$ composer phpcov-merge-win` on Windows) to merge `build/cov/coverage.cov` with `build/logs/clover.xml` as instructed on [php-coveralls doc](https://packagist.org/packages/php-coveralls/php-coveralls).

* Run `$ composer style-fix-download` to download the latest php-cs-fixer file to project directory. Afterwards, you can run `$ composer style-fix` to auto style fix all your code.

* No caching mechanism has been developed, it is expected to be taken care of by the user of this package depending on their use case.

# Contributors
|[![](https://github.com/p1ho.png?size=50)](https://github.com/p1ho)
|---|
|[p1ho](https://github.com/p1ho)|
