<?php

namespace NS\DistanceBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\FOSRestController;

class RestController extends FOSRestController
{
    /**
     * @Rest\View
     */
    public function getAction($postalcode1,$postalcode2,$unit)
    {
        $distance = $this->get('ns_distance.calculator')->getDistanceBetweenPostalCodes($postalcode1,$postalcode2,$unit);

        return array('from' => $postalcode1,'to'=>$postalcode2,'distance'=>$distance);
    }
}
