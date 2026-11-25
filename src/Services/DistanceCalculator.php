<?php

namespace NS\DistanceBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use NS\DistanceBundle\Entity\Distance;
use NS\DistanceBundle\Entity\GeographicPointInterface;
use NS\DistanceBundle\Entity\PostalCode;
use NS\DistanceBundle\Exceptions\UnknownPostalCodeException;
use NS\DistanceBundle\Validator\PostalCodeValidator;

class DistanceCalculator
{
    /** @var EntityManagerInterface */
    private $entityMgr;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityMgr = $em;
    }

    /**
     * This routine calculates the distance between two points
     *
     * @param $source GeographicPointInterface
     * @param $dest   GeographicPointInterface
     *
     * @return Distance
     */
    public function getDistance(GeographicPointInterface $source, GeographicPointInterface $dest)
    {
        $sourceLatitude = deg2rad($source->getLatitude());
        $destLatitude = deg2rad($dest->getLatitude());
        $halfLongitudeDifference = deg2rad($dest->getLongitude() - $source->getLongitude()) / 2;
        $haversine = sin(($destLatitude - $sourceLatitude) / 2) ** 2
            + cos($sourceLatitude) * cos($destLatitude) * sin($halfLongitudeDifference) ** 2;
        $degrees = rad2deg(2 * asin(sqrt(min(1, max(0, $haversine)))));

        return new Distance($degrees * 60 * 1.1515);
    }

    /**
     *
     * @param string       $inPostal1
     * @param string|array $inPostal2
     *
     * @return array
     */
    public function getDistanceBetweenPostalCodes($inPostal1, $inPostal2): array
    {
        $codes = $this->adjustCodes($inPostal1, $inPostal2);

        if (count(array_unique($codes)) === 1) {
            return [$inPostal1 => [$inPostal1 => new Distance(0)]];
        }

        $points = $this->entityMgr->getRepository(PostalCode::class)->getByCodesWithPrefixFallback($codes);
        $sourceCode = array_shift($codes);

        if (!isset($points[$sourceCode])) {
            if (count($points) < 2) {
                return [];
            }

            throw new UnknownPostalCodeException(sprintf("Source postalcode '%s/%s' not found", $inPostal1, $sourceCode));
        }

        $distances = [];
        foreach (array_unique($codes) as $code) {
            if (isset($points[$code])) {
                $distances[$code] = $this->getDistance($points[$sourceCode], $points[$code]);
            }
        }

        return $distances === [] ? [] : [$sourceCode => $distances];
    }

    /**
     * @param string       $inPostal1
     * @param string|array $inPostal2
     *
     * @return array
     */
    public function adjustCodes(string $inPostal1, $inPostal2): array
    {
        $postal1 = $this->cleanCode($inPostal1);
        if (is_array($inPostal2)) {
            foreach ($inPostal2 as &$postalCode) {
                $postalCode = $this->cleanCode($postalCode);
            }

            return array_merge([$postal1], $inPostal2);
        }

        $postal2 = $this->cleanCode($inPostal2);
        return [$postal1, $postal2];
    }

    public function cleanCode(string $code): string
    {
        return PostalCodeValidator::normalize($code);
    }
}

