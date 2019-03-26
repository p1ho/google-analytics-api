<?php

namespace P1ho\GoogleAnalyticsAPI;

class Client
{
    private $fetcher;

    public function __construct(string $pathToCredentials)
    {
        $this->fetcher = new Report\Fetcher($pathToCredentials);
    }

    public function getData(string $viewId, string $startDate, string $endDate, array $dimensions, array $metrics, string $filtersExp = ''): array
    {
        $package = new Request\Package($viewId, $startDate, $endDate, $dimensions, $metrics, $filtersExp);
        $fetchedData = $this->fetcher->getData($package);
        return Report\Assembler::run($fetchedData);
    }
}
