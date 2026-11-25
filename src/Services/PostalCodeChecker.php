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

        $cleanPostalCode = PostalCodeValidator::normalize($postalCode);
        if (!PostalCodeValidator::validate($cleanPostalCode)) {
            throw new InvalidPostalCodeException();
        }

        $postalObj = $this->entityMgr->getRepository(PostalCode::class)->getByCode($cleanPostalCode);
        if ($postalObj !== null) {
            return $postalObj;
        }

        foreach ($this->entityMgr->getUnitOfWork()->getScheduledEntityInsertions() as $pending) {
            if ($pending instanceof PostalCode && $pending->getPostalCode() === $cleanPostalCode) {
                return $pending;
            }
        }

        $url = sprintf('https://maps.googleapis.com/maps/api/geocode/json?components=postal_code:%s&sensor=false&key=%s', $cleanPostalCode, $this->key);
        $result = $this->client->request('GET', $url);
        $response = json_decode($result->getContent(), true);
        $match = $response['results'][0] ?? [];
        $location = $match['geometry']['location'] ?? [];
        if (($response['status'] ?? null) !== 'OK'
            || !is_numeric($location['lat'] ?? null)
            || !is_numeric($location['lng'] ?? null)) {
            return null;
        }

        $components = [];
        foreach ($match['address_components'] ?? [] as $component) {
            $name = $component['short_name'] ?? $component['long_name'] ?? '';
            if ($name === '') {
                continue;
            }

            foreach ($component['types'] ?? [] as $type) {
                $components[$type] = $components[$type] ?? $name;
            }
        }

        $city = $components['locality'] ?? $components['postal_town'] ?? null;
        $province = $components['administrative_area_level_1'] ?? null;
        $postalObj = (new PostalCode())
            ->setPostalCode($cleanPostalCode)
            ->setLongitude($location['lng'])
            ->setLatitude($location['lat'])
            ->setCity($city)
            ->setProvince($province);

        // The postal code table requires a city and a province.
        if ($city !== null && $province !== null) {
            $this->entityMgr->persist($postalObj);
        }

        return $postalObj;
    }
}

