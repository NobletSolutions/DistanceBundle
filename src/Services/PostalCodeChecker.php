<?php

namespace NS\DistanceBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use NS\DistanceBundle\Entity\GeographicPointInterface;
use NS\DistanceBundle\Entity\PostalCode;
use NS\DistanceBundle\Exceptions\InvalidPostalCodeException;
use NS\DistanceBundle\Validator\PostalCodeValidator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PostalCodeChecker
{
    /** @var HttpClientInterface */
    private $client;

    /** @var EntityManagerInterface */
    private $entityMgr;

    /** @var string */
    private $key;

    public function __construct(EntityManagerInterface $entityMgr, HttpClientInterface $client, string $apiKey)
    {
        $this->entityMgr = $entityMgr;
        $this->key       = $apiKey;
        $this->client    = $client;
    }

    /**
     * @param $postalCode
     *
     * @return GeographicPointInterface|null
     * @throws InvalidPostalCodeException
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
            $url      = sprintf('https://maps.googleapis.com/maps/api/geocode/json?components=postal_code:%s&sensor=false&key=%s', $cleanPostalCode, $this->key);
            $result   = $this->client->request('GET', $url);
            $response = json_decode($result->getContent(), true);

            if ($response['status'] === 'OK') {
                $geometry = $response['results'][0]['geometry'];

                $postalObj = new PostalCode();
                $postalObj->setLongitude($geometry['location']['lng']);
                $postalObj->setLatitude($geometry['location']['lat']);
                $postalObj->setCity($response['results'][0]['address_components'][2]['short_name']);
                $postalObj->setPostalCode($cleanPostalCode);
                $postalObj->setProvince($response['results'][0]['address_components'][4]['short_name'] ?? 'AB');

                $this->entityMgr->persist($postalObj);
                $this->entityMgr->flush();
            }
        }

        return $postalObj;
    }
}

