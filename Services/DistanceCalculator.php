<?php

namespace NS\DistanceBundle\Services;

use \Doctrine\ORM\EntityManager;

class DistanceCalculator
{
    private $em;
    
    public function __construct(EntityManager $em) 
    {
        $this->em = $em;
    }

    /**
     * This routine calculates the distance between two points (given the
     * latitude/longitude of those points). It is being used to calculate
     * the distance between two zip codes or postal codes using our
     * Zipcodeworld(tm) and Postalcodeworld(tm) products.
     * 
     * @param lat1 source latitude point
     * @param lon1 source longitude point
     * @param lat2 dest latitude point
     * @param lon2 dest longitude point
     * @param unit the output unit
     */
    public function getDistance($lat1, $lon1, $lat2, $lon2, $unit) 
    { 
        $theta = $lon1 - $lon2; 
        $dist = rad2deg(acos( sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)) )); 
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);
 
        if (\strcasecmp($unit, "K") == 0)
            return ($miles * 1.609344);
        else if (\strcasecmp($unit, "N") == 0)
            return ($miles * 0.8684);
        else
            return $miles;
    }
    
    public function getDistanceBetweenPostalCodes($postal1, $postal2, $unit = 'K')
    {
        $postal1 = strtoupper(preg_replace('/\s+/', '', $postal1));
        $postal2 = strtoupper(preg_replace('/\s+/', '', $postal2));
        $data = $this->em->getRepository('NSDistanceBundle:PostalCode')->getByCodes(array($postal1,$postal2));
        
        if(count($data) != 2)
            throw new \Exception("Unable to find postal code");
        
        return $this->getDistance($data[$postal1]->getLatitude(),$data[$postal1]->getLongitude(),$data[$postal2]->getLatitude(),$data[$postal2]->getLongitude(),$unit);
    }
}
