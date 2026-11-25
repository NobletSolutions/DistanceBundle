<?php

namespace NS\DistanceBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnexpectedResultException;

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

    public function getByCode($postalCode)
    {
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

