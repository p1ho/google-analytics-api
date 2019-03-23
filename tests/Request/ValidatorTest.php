<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use P1ho\GoogleAnalyticsAPI\Request\Validator;

final class ValidatorTest extends TestCase
{
    public function testDateValidation(): void
    {
        $validator = new Validator();
        $validDate = '2019-01-01';
        $validDimensions = [];
        $validMetrics = ['ga:users'];  // adding a valid metric because metrics can't be empty

        // empty date
        $hasError = false;
        try {
            $validator->pass('', $validDate, $validDimensions, $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // invalid date
        $hasError = false;
        try {
            $validator->pass('asdf;lkjasdf;lkj', $validDate, $validDimensions, $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $hasError = false;
        try {
            $validator->pass('00-01-31', $validDate, $validDimensions, $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $hasError = false;
        try {
            $validator->pass('20190131', $validDate, $validDimensions, $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $hasError = false;
        try {
            $validator->pass('2019_01_31', $validDate, $validDimensions, $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $hasError = false;
        try {
            $validator->pass('01-31-2019', $validDate, $validDimensions, $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // date in the future
        $hasError = false;
        try {
            $validator->pass('3019-01-31', $validDate, $validDimensions, $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // enddate before startdate
        $hasError = false;
        try {
            $validator->pass('2019-01-31', '2019-01-01', $validDimensions, $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // pass cases
        $this->assertTrue(
            $validator->pass('2019-01-01', '2019-01-02', $validDimensions, $validMetrics)
        );
        $this->assertTrue(
            $validator->pass('2000-01-01', '2019-01-01', $validDimensions, $validMetrics)
        );
    }

    public function testDimensionValidation(): void
    {
        $validator = new Validator();
        $validDate = '2019-01-01';
        $validDimensions = array_slice($validator->getValidDimensions(), 0, 7);
        $validMetrics = ['ga:users'];

        // invalid dimensions
        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, [''], $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, ['asdf'], $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // uppercase counts as invalid
        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, ['GA:REGION'], $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // can't have more than 7 dimensions
        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, array_merge($validDimensions, [
                'ga:sessions'
              ]), $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // duplicate dimension
        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, [
              'ga:continent',
              'ga:continent'
            ], $validMetrics);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // pass cases
        $this->assertTrue(
            $validator->pass($validDate, $validDate, [$validDimensions[0]], $validMetrics)
        );
        $this->assertTrue(
            $validator->pass($validDate, $validDate, array_slice($validDimensions, 0, 3), $validMetrics)
        );
        $this->assertTrue(
            $validator->pass($validDate, $validDate, $validDimensions, $validMetrics)
        );
    }

    public function testMetricValidation(): void
    {
        $validator = new Validator();
        $validDate = '2019-01-01';
        $validDimensions = [];
        $validMetrics = array_slice($validator->getValidMetrics(), 0, 50);

        // invalid metrics
        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, $validDimensions, ['']);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, $validDimensions, ['asdf']);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // uppercase counts as invalid
        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, $validDimensions, ['GA:USERS']);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // can't have more than 50 metrics
        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, $validDimensions, array_merge($validMetrics, ['ga:totalRefunds']));
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // duplicate metrics
        $hasError = false;
        try {
            $validator->pass($validDate, $validDate, $validDimensions, [
              'ga:totalRefunds',
              'ga:totalRefunds'
            ]);
        } catch (Exception $e) {
            $hasError = true;
        }
        $this->assertTrue($hasError);

        // pass cases
        $this->assertTrue(
            $validator->pass($validDate, $validDate, $validDimensions, [$validMetrics[0]])
        );
        $this->assertTrue(
            $validator->pass($validDate, $validDate, $validDimensions, array_slice($validMetrics, 0, 3))
        );
        $this->assertTrue(
            $validator->pass($validDate, $validDate, $validDimensions, $validMetrics)
        );
    }
}
