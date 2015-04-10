<?php

namespace NS\DistanceBundle\Controller;

use \Symfony\Bundle\FrameworkBundle\Controller\Controller;
use \Symfony\Component\HttpFoundation\Response;


class RestController extends Controller
{
    public function getAction($postalcode1,$postalcode2,$unit)
    {
        if(strpos($postalcode2, ',') !== FALSE)
            $postalcode2 = explode (',', $postalcode2);

        $distance = $this->get('ns_distance.calculator')->getDistanceBetweenPostalCodes($postalcode1,$postalcode2,$unit);
        return new Response(json_encode($distance),200,array('Content-Type'=>'application/json'));
    }
}
