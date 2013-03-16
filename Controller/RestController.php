<?php

namespace NS\DistanceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
* @Route(prefix="/rest")
*/
class DistanceController extends Controller
{
    /**
     * @Route("/distance/{postalcode1}/{postalcode2}", requirements={"_method": "GET"})
     * @Rest\View
     */
    public function getAction($postalcode1,$postalcode2)
    {
        $distance = $this->getContainer()->get('ns_distance.calculator')->getDistanceBetweenPostalCodes($postalcode1,$postalcode2);

        return array('from' => $postalcode1,'to'=>$postalcode2,'distance'=>$distance);
    }
}
