# README

# Table of Content
* [Introduction](#introduction)
* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
* [Development](#development)
* [Contributors](#contributors)

# Introduction

Google has a [PHP API Library](https://github.com/googleapis/google-api-php-client) that allows making requests to Google APIs from a PHP execution, including [Reporting API v4](https://developers.google.com/analytics/devguides/reporting/core/v4/) which enables access to Google Analytics data. However, while the library is quite powerful, it can be unintuitive as users have to create objects from the library, which takes a lot of digging in the documentation.

As someone who had to use this library extensively, I wanted something that's more intuitive. Specifically, if I'm already familiar with Google Analytics and the [dimensions and metrics](https://developers.google.com/analytics/devguides/reporting/core/dimsmets) available, I should immediately be able to make requests and get my data; and thus this package was born.

## Features

* **Validation**: If you make an API request and you had a typo, Google would return a [400 Bad Request](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/400). In this case you didn't get your data, but from my experience it would still eat into your [daily quota](https://developers.google.com/analytics/devguides/reporting/core/v4/limits-quotas#analytics_reporting_api_v4). This package uses cached results from [metadata API](https://developers.google.com/analytics/devguides/reporting/metadata/v3/) which helps with offline validation.

* **Optimize Requests**: Google API forces users to break apart requests:
  - [A batchGet() request can take 5 subrequests](https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#request-body).
  - [A subrequest can have 10 metrics](https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#ReportRequest.FIELDS.metrics)

  This means the returned report can be difficult to parse and aggregate. This package optimizes the packaging process of building a `batchGet()` request so no space is wasted in a single request.

* **Intuitive Reports**: Just as the package helps you aggregate the requests, it also reorganizes the fragmented reports Google returned, it's structured minimally and intuitively.


# Requirements

* [Create a Service Account](https://developers.google.com/api-client-library/php/auth/service-accounts#creatinganaccount) and store the downloaded `json` file somewhere secure. This is needed to make calls to Google API.

* Add the created Service Account Email to your Google Analytics view. You can do this by going to your [analytics homeapge](https://analytics.google.com) &rarr; Admin &rarr; User Management.

Note: If the above seemed confusing, there is a [very well written tutorial](https://github.com/spatie/laravel-analytics#how-to-obtain-the-credentials-to-communicate-with-google-analytics) on an older Google interface.

# Installation

This package is available through [Composer](https://getcomposer.org/), and can be installed by entering:

```Bash
$ composer require p1ho/google-analytics-api
```

# Usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use P1ho\GoogleAnalyticsAPI\Client;

$googleAnalytics = new Client('path/to/your/credentials/json');

// this is the view id on your Google Analytics page that represents the website.
$viewId = '12345678';

// dates can be anything that can be parsed by strtotime()
$startDate = 'yesterday';
$endDate = 'today';

// see https://developers.google.com/analytics/devguides/reporting/core/dimsmets
$dimensions = ['ga:city','ga:cityId']; // no longer than 7
$metrics = ['ga:users', 'ga:sessions']; // no longer than 50

// makes the call and stores reorganized results into report.
$report = $googleAnalytics->getData($viewId, $startDate, $endDate, $dimensions, $metrics);
```
If needed, you can also add a [filter expression](https://developers.google.com/analytics/devguides/reporting/core/v3/reference#filters)
```php
<?php
// your code from before
$filtersExp = "your-filter-expression-here";
$report = $googleAnalytics->getData($viewId, $startDate, $endDate, $dimensions, $metrics, $filtersExp);
```
Report Structure (JSON representation)
```json
{
  "requestCost": 1,
  "request": {
    "viewId": "specified-view-id",
    "startDate": "specified-start-date",
    "endDate": "specified-end-date",
    "dimensions": [
      "dimension-item1",
      "dimension-item2"
    ],
    "metrics": [
      "metric-item1",
      "metric-item2"
    ],
    "filtersExp": "specified-filters-expression"
  },
  "report": {
    "totals": {
      "metric-item1": "summed-metric-value1",
      "metric-item2": "summed-metric-value2"
    },
    "rows": [
      {
        "dimensions": {
          "dimension-item1": "dimension-value1",
          "dimension-item2": "dimension-value2"
        },
        "metrics": {
          "metric-item1": "metric-value1",
          "metric-item2": "metric-value2"
        }
      },
      {
        "dimensions": {
          "dimension-item1": "dimension-value1",
          "dimension-item2": "dimension-value2"
        },
        "metrics": {
          "metric-item1": "metric-value1",
          "metric-item2": "metric-value2"
        }
      }
    ]
  }
}
```
# Development
If you'd like to fork this project, you should set up the following in the package root.
* Set up `secrets.json` in project root. It should have the following structure:
```json
{
  "credentials": "path/to/your/service/account/credential/json",
  "viewId": "view-Id-authorized-for-your-service-account"
}
```
* Download [php-cs-fixer-v2.phar](https://cs.symfony.com/download/php-cs-fixer-v2.phar) and place into project root. After this is done you can run `$ composer test` in the command line to autofix the styling.

# Contributors
|[![](https://github.com/p1ho.png?size=50)](https://github.com/p1ho)
|---|
|[p1ho](https://github.com/p1ho)|
