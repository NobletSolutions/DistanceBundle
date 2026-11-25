<?php

namespace NS\DistanceBundle\Controller;

use NS\DistanceBundle\Exceptions\UnknownPostalCodeException;
use NS\DistanceBundle\Services\DistanceCalculator;
use Symfony\Component\HttpFoundation\JsonResponse;

class RestController
{
    private DistanceCalculator $calculator;

    public function __construct(DistanceCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function getAction(string $postalcode1, string $postalcode2): JsonResponse
    {
        if (strpos($postalcode2, ',') !== FALSE) {
            $postalcode2 = explode(',', $postalcode2);
        }

        try {
            $distance = $this->calculator->getDistanceBetweenPostalCodes($postalcode1, $postalcode2);
        }
        catch (UnknownPostalCodeException $exception) {
            $distance = array();
        }

        return new JsonResponse($distance);
    }
}

