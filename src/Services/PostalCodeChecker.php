<?php

namespace NS\DistanceBundle\Services;

use Doctrine\Common\Persistence\ObjectManager;
use NS\DistanceBundle\Entity\GeographicPointInterface;
use NS\DistanceBundle\Entity\PostalCode;
use NS\DistanceBundle\Exceptions\InvalidPostalCodeException;
use NS\DistanceBundle\Validator\PostalCodeValidator;

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
     *
     * @throws InvalidPostalCodeException
     * @return GeographicPointInterface|null
     */
    public function getLatitudeAndLongitude($postalCode)
    {
        if (empty($postalCode)) {
            return null;
        }

        $cleanPostalCode = strtoupper(preg_replace('/\s+/', '', $postalCode));
        if (!PostalCodeValidator::validate($cleanPostalCode)) {
            throw new InvalidPostalCodeException();
        }

        $postalObj = $this->entityMgr->getRepository('NSDistanceBundle:PostalCode')->getByCode($cleanPostalCode);

        if (!$postalObj) {
            $url = sprintf('http://maps.googleapis.com/maps/api/geocode/json?components=postal_code:%s&sensor=false', $cleanPostalCode);
            $result = file_get_contents($url);
            $response = json_decode($result, true);

            if ($response['status'] == 'OK') {
                $geometry = $response['results'][0]['geometry'];

                $postalObj = new PostalCode();
                $postalObj->setLongitude($geometry['location']['lng']);
                $postalObj->setLatitude($geometry['location']['lat']);
                $postalObj->setCity($response['results'][0]['address_components'][2]['short_name']);
                $postalObj->setPostalCode($cleanPostalCode);
                $postalObj->setProvince(isset($response['results'][0]['address_components'][4]['short_name']) ? $response['results'][0]['address_components'][4]['short_name'] : "AB");

                $this->entityMgr->persist($postalObj);
                $this->entityMgr->flush($postalObj);
            }
        }

        return $postalObj;
    }
}

