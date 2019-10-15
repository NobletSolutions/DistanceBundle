<?php

namespace NS\DistanceBundle\Controller;

use NS\DistanceBundle\Exceptions\UnknownPostalCodeException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class RestController extends Controller
{
    /**
     * @Route("/rest/distance/{postalcode1}/{postalcode2}", name="ns_distance_rest")
     *
     * @param string $postalcode1
     * @param string $postalcode2
     *
     * @return JsonResponse
     */
    public function getAction(string $postalcode1, string $postalcode2): JsonResponse
    {
        if (strpos($postalcode2, ',') !== FALSE) {
            $postalcode2 = explode(',', $postalcode2);
        }

        try {
            $distance = $this->get('ns_distance.calculator')->getDistanceBetweenPostalCodes($postalcode1, $postalcode2);
        }
        catch (UnknownPostalCodeException $exception) {
            $distance = array();
        }

        return new JsonResponse($distance);
    }
}

