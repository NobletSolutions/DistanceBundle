<?php

namespace NS\DistanceBundle\Entity;

class Distance
{
    const KM            = 'K';
    const NAUTICAL_MILE = 'NM';
    const MILE          = 'M';

    public $distance;

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
     * @param string $unit
     * @return string
     */
    public function getDistanceString($unit = self::KM)
    {
        $str = null;
        switch($unit)
        {
            case self::NAUTICAL_MILE:
                $str = 'nm';
                break;
            case self::MILE:
                $str = 'm';
                break;
            case self::KM:
            default:
                $str = 'KM';
                break;
        }

        return sprintf("%01.2f %s",$this->getDistance($unit),$str);
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
