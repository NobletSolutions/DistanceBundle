<?php

namespace NS\DistanceBundle\Entity;

/**
 * Description of Distance
 *
 * @author gnat
 */
class Distance
{
    const KM            = 'K';
    const NAUTICAL_MILE = 'NM';
    const MILE          = 'M';

    private $distance;

    /**
     *
     * @param double $distanceInMiles
     */
    public function __construct($distanceInMiles = null)
    {
        $this->distance = array();
        if(!is_null($distanceInMiles) && is_numeric($distanceInMiles)) {
            $this->setDistance ($distanceInMiles);
        }
    }

    /**
     *
     * @param string $unit
     * @return double
     * @throws \InvalidArgumentException
     */
    public function getDistance($unit = self::KM)
    {
        if(!isset($this->distance[$unit])){
            throw new \InvalidArgumentException("Unit of measurement $unit is invalid");
        }
   
        return $this->distance[$unit];
    }

    /**
     *
     * @param double $distanceInMiles
     */
    public function setDistance($distanceInMiles)
    {
        $this->distance[self::KM] = $distanceInMiles*1.609344;
        $this->distance[self::NAUTICAL_MILE] = $distanceInMiles*0.8684;
        $this->distance[self::MILE] = $distanceInMiles;
    }
}
