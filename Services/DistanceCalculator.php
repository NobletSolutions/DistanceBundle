<?php

namespace NS\DistanceBundle\Services;

use \Doctrine\ORM\EntityManager;

class DistanceCalculator
{
    private $entityMgr;

    /**
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->entityMgr = $em;
    }

    /**
     * This routine calculates the distance between two points (given the
     * latitude/longitude of those points). 
     * 
     * @param lat1 source latitude point
     * @param lon1 source longitude point
     * @param lat2 dest latitude point
     * @param lon2 dest longitude point
     * @param unit the output unit
     */
    public function getDistance($lat1, $lon1, $lat2, $lon2, $unit)
    {
        $theta     = $lon1 - $lon2;
        $dist      = rad2deg(acos(sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta))));
        $miles     = $dist * 60 * 1.1515;
        $upperUnit = strtoupper($unit);

        switch($upperUnit)
        {
            case 'K':
                $ret = ($miles * 1.609344);
                break;
            case 'N':
                $ret = ($miles * 0.8684);
                break;
            default:
                $ret = $miles;
                break;
        }

        return $ret;
    }

    /**
     *
     * @param string $inPostal1
     * @param string $inPostal2
     * @param string $unit
     * @return array
     */
    public function getDistanceBetweenPostalCodes($inPostal1, $inPostal2, $unit = 'K')
    {
        $codes = $this->adjustCodes($inPostal1, $inPostal2);
        $data  = $this->entityMgr->getRepository('NSDistanceBundle:PostalCode')->getByCodes($codes);

        if (count($data) < 2 && $codes[0] != $codes[1]) {
            return array();
        }

        if (is_array($postal2)) {
            $ret = array();

            foreach ($postal2 as $pcode) {
                if (isset($data[$pcode])) {
                    $ret[$pcode] = array('unit' => $unit, 'distance' => $this->getDistance($data[$postal1]->getLatitude(), $data[$postal1]->getLongitude(), $data[$pcode]->getLatitude(), $data[$pcode]->getLongitude(), $unit));
                }
            }

            return array($postal1 => $ret);
        }

        return array($postal1 => array($postal2 => array('unit' => $unit, 'distance' => $this->getDistance($data[$postal1]->getLatitude(), $data[$postal1]->getLongitude(), $data[$postal2]->getLatitude(), $data[$postal2]->getLongitude(), $unit))));
    }

    /**
     * @param string $inPostal1
     * @param string|array $inPostal2
     */
    public function adjustCodes($inPostal1, $inPostal2)
    {
        $postal1 = $this->cleanCode($inPostal1);
        if (is_array($inPostal2)) {
            foreach ($inPostal2 as &$p) {
                $p = $this->cleanCode($p);
            }

            return array_merge(array($postal1), $inPostal2);
        }

        $postal2 = $this->cleanCode($inPostal2);
        return array($postal1, $postal2);
    }

    /**
     *
     * @param string $code
     * @return string
     */
    public function cleanCode($code)
    {
        return strtoupper(preg_replace('/\s+/', '', $code));
    }
}