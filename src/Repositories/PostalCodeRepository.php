<?php

namespace NS\DistanceBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnexpectedResultException;
use NS\DistanceBundle\Validator\PostalCodeValidator;

class PostalCodeRepository extends EntityRepository
{
    public function getByCodes(array $codes)
    {
        return $this->createQueryBuilder('p', 'p.postalCode')
            ->where('p.postalCode IN (:ids)')
            ->setParameter('ids', $codes)
            ->getQuery()
            ->getResult();
    }

    public function getByCodesWithPrefixFallback(array $codes): array
    {
        $candidates = [];
        foreach (array_unique($codes) as $code) {
            $candidates[$code] = [$code];
            $formatted = PostalCodeValidator::formatCanadianCode($code);
            if ($formatted !== null) {
                $candidates[$code][] = $formatted;
                $candidates[$code][] = substr($code, 0, 3);
            }
        }

        $points = $this->getByCodes(array_unique(array_merge(...array_values($candidates))));
        $matches = [];
        foreach ($candidates as $code => $alternatives) {
            foreach ($alternatives as $alternative) {
                if (isset($points[$alternative])) {
                    $matches[$code] = $points[$alternative];
                    break;
                }
            }
        }

        return $matches;
    }

    public function getByCode($postalCode)
    {
        $formatted = is_string($postalCode) ? PostalCodeValidator::formatCanadianCode($postalCode) : null;
        if ($formatted !== null) {
            $points = $this->getByCodes([$postalCode, $formatted]);

            return $points[$postalCode] ?? $points[$formatted] ?? null;
        }

        try {
            return $this->createQueryBuilder('p')
                ->where('p.postalCode = :pcode')
                ->setParameter('pcode',$postalCode)
                ->getQuery()
                ->getSingleResult();
        } catch (UnexpectedResultException $exception) {
            return null;
        }
    }
}

