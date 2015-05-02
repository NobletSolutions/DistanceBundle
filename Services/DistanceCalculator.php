<?php

namespace NS\DistanceBundle\Services;

use \Doctrine\Common\Persistence\ObjectManager;
use \Doctrine\ORM\EntityManager;
use \NS\DistanceBundle\Entity\Distance;
use \NS\DistanceBundle\Entity\GeographicPointInterface;
use \NS\DistanceBundle\Exceptions\UnknownPostalCodeException;

/**
 * @author gnat
 */
class DistanceCalculator
{
    private $entityMgr;

    /**
     *
     * @param EntityManager $em
     */
    public function __construct(ObjectManager $em)
    {
        $this->entityMgr = $em;
    }

    /**
     * This routine calculates the distance between two points
     * 
     * @param $source GeographicPointInterface
     * @param $dest GeographicPointInterface
     */
    public function getDistance(GeographicPointInterface $source, GeographicPointInterface $dest)
    {
        $theta = $source->getLongitude() - $dest->getLongitude();
        $dist  = rad2deg(acos(sin(deg2rad($source->getLatitude())) * sin(deg2rad($dest->getLatitude())) + cos(deg2rad($source->getLatitude())) * cos(deg2rad($dest->getLatitude())) * cos(deg2rad($theta))));

        return new Distance($dist * 60 * 1.1515);
    }

    /**
     *
     * @param string $inPostal1
     * @param string|array $inPostal2
     * @return array
     */
    public function getDistanceBetweenPostalCodes($inPostal1, $inPostal2)
    {
        $codes = $this->adjustCodes($inPostal1, $inPostal2);
        $data  = $this->entityMgr->getRepository('NSDistanceBundle:PostalCode')->getByCodes($codes);

        if (count($data) < 2) {
            return array();
        }

        if (!isset($data[$codes[0]])) {
            throw new UnknownPostalCodeException(sprintf("Source postalcode '%s/%s' not found",$inPostal1,$codes[0]));
        }

        $postal1 = $data[$codes[0]];

        if (is_array($inPostal2)) {
            $ret = array();

            foreach ($data as $pcode) {
                if($pcode != $postal1) {
                    $ret[$pcode->getPostalCode()] = $this->getDistance($postal1,$pcode);
                }
            }

            return array($postal1->getPostalCode() => $ret);
        }

        $postal2 = $data[$codes[1]];

        return array($postal1->getPostalCode() => array($postal2->getPostalCode() => $this->getDistance($postal1,$postal2)));
    }

    /**
     * @param string $inPostal1
     * @param string|array $inPostal2
     */
    public function adjustCodes($inPostal1, $inPostal2)
    {
        $postal1 = $this->cleanCode($inPostal1);
        if (is_array($inPostal2)) {
            foreach ($inPostal2 as &$postalCode) {
                $postalCode = $this->cleanCode($postalCode);
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