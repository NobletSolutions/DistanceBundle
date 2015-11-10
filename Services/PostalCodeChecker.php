<?php

namespace NS\DistanceBundle\Services;

use Doctrine\Common\Persistence\ObjectManager;
use NS\DistanceBundle\Entity\PostalCode;

class PostalCodeChecker
{
    /**
     * @var ObjectManager
     */
    private $entityMgr;

    /**
     * PostalCodeChecker constructor.
     * @param $entityMgr
     */
    public function __construct(ObjectManager $entityMgr)
    {
        $this->entityMgr = $entityMgr;
    }

    /**
     * @param $postalCode
     * @return GeographicPointInterface|null
     */
    public function getLatitudeAndLongitude($postalCode)
    {
        $postalCode = strtoupper(preg_replace('/\s+/', '', $postalCode));
        $postalObj = $this->entityMgr->getRepository('NSDistanceBundle:PostalCode')->getByCode($postalCode);

        if(!$postalObj) {
            $url = sprintf('http://maps.googleapis.com/maps/api/geocode/json?components=postal_code:%s&sensor=false',$postalCode);
            $result = file_get_contents($url);
            $response = json_decode($result, true);

            if ($response['status'] != 'OK') {
                $lat = '';
                $long = '';
            } else {
                $geometry = $response['results'][0]['geometry'];

                $postalObj = new PostalCode();
                $postalObj->setLongitude($geometry['location']['lat']);
                $postalObj->setLatitude($geometry['location']['lng']);
                $postalObj->setCity($response['results'][0]['address_components'][2]['short_name']);
                $postalObj->setPostalCode($postalCode);
                $postalObj->setProvince(isset($response['results'][0]['address_components'][4]['short_name'])? $response['results'][0]['address_components'][4]['short_name']: "AB");
                $this->entityMgr->persist($postalObj);
            }
        }

        return $postalObj;
    }
}

