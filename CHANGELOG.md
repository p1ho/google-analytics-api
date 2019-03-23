# CHANGELOG

### Update: (2019-03-22)

  1. First Public Release (v1.0.0)

    * Restructured code to fit with latest PSR

    * Unit Tests

    * Better Documentation

### Update: (2018-11-26)

  1. Added options for 'performance'/'verbose'/'minimal' fetch modes

    * **performance**: sets ['include-empty-rows'](https://developers.google.com/analytics/devguides/reporting/core/v3/reference#includeEmptyRows) to FALSE, and simply return what was fetched. This is the default setting.

    * **verbose**: sets ['include-empty-rows'](https://developers.google.com/analytics/devguides/reporting/core/v3/reference#includeEmptyRows) to TRUE, which means data for all possible dimension combinations will be returned, even if all metrics are 0. This ensures structural consistency across different but may take up lots of memory.

    * **minimal**: sets ['include-empty-rows'](https://developers.google.com/analytics/devguides/reporting/core/v3/reference#includeEmptyRows) to FALSE, and then for each report cluster, it will trim metrics that have a value of 0. This ensures all data points in the report have a non-zero value, minimizing memory needed, but will take slightly longer to process.

  1. Added better error detection when fetching metadata for ga_validator

  1. Removed a few redundant code

### Update: (2018-09-17)

  1. Changed all array_push() statements to a more conventional "stack[] = needle;" type

### Update: (2018-08-09)

  1. Removed the assumption that the credential files (API key, etc) have to be within the repository for security reasons:

  *  now the config is loaded from the directory specified in 'ga_config.php'

  *  the secrets directories in config have to now be specified as absolute directories

### Update: (2018-08-07)

  1. Added format checks for dateRanges

### Update: (2018-07-31)

  1. Fixed a bug where dateRange was not being processed correctly

### Update: (2018-07-24)

  1. Fixed Styling for easier readability

### Update: (2018-07-23)

  1. Added a few more checks for validator

  1. Google can actually return 100000 results per call, updated accordingly

  1. Added fetch_interval to the config.ini in case we make calls too frequently

  1. Added code for handling cases where there are more results than the 100000 limit.

  1. For the dateRange argument, added some flexibility. Now it can take in 2 types of arguments:

    11. an array containing associative arrays with keys "StartDate" and "EndDate"

    11. a single associative array with keys "StartDate" and "EndDate"

### Update: (2018-07-16/2018-07-17)

  1. Added results maximum per call, Google defaults to 1000 but can theoretically be maxed at 10000.

  1. Changed naming style: shortened GoogleAnalytics to GA

### Update: (2018-07-11)

  1. Changed the GA_ReportAssembler into a utility class with static methods (which means no instantiation necessary)

### Update (2018-06-26)

  1. Added a GA_API.php that further abstracts the 4 stage process away from the end user. (Can be done now in 3 lines)

  1. Properly formatted comments for functions

  1. Moved several settings to a config.ini for easier update in the future if Google decides to change thing
