<?php

namespace NS\DistanceBundle\Controller;

use NS\DistanceBundle\Exceptions\UnknownPostalCodeException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RestController extends Controller
{
    public function getAction($postalcode1, $postalcode2)
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

        return new Response(json_encode($distance), 200, array('Content-Type' => 'application/json'));
    }
}

