<?php

namespace NS\DistanceBundle\Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnexpectedResultException;

class PostalCodeRepository extends EntityRepository
{
    public function getByCodes(array $codes)
    {
        return $this->_em->createQuery("SELECT p 
                                        FROM NS\DistanceBundle\Entity\PostalCode p INDEX BY p.postalCode 
                                        WHERE p.postalCode IN (:ids)")
                ->setParameters(array('ids' => $codes))
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

