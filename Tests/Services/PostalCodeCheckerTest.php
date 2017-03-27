<?php

namespace NS\DistanceBundle\Tests\Services;


use Doctrine\Common\Persistence\ObjectManager;
use NS\DistanceBundle\Entity\PostalCode;
use NS\DistanceBundle\Repositories\PostalCodeRepository;
use NS\DistanceBundle\Services\PostalCodeChecker;

class PostalCodeCheckerTest extends \PHPUnit_Framework_TestCase
{
    private $entityManager;
    private $repository;

    public function testResultIsInDB()
    {
        $postCode = new PostalCode();
        $this->repository->expects($this->once())->method('getByCode')->with('T3A5J4')->willReturn($postCode);
        $this->entityManager->expects($this->once())->method('getRepository')->with('NSDistanceBundle:PostalCode')->willReturn($this->repository);
        $service = new PostalCodeChecker($this->entityManager);
        $this->assertEquals($postCode,$service->getLatitudeAndLongitude('T3A5J4'));
    }

    public function testResultIsNotInDB()
    {
        $this->repository->expects($this->once())->method('getByCode')->with('T3A5J4')->willReturn(null);
        $this->entityManager->expects($this->once())->method('getRepository')->with('NSDistanceBundle:PostalCode')->willReturn($this->repository);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $service = new PostalCodeChecker($this->entityManager);
        $postCode = $service->getLatitudeAndLongitude('T3A5J4');
        $this->assertEquals('T3A5J4', $postCode->getPostalCode());
        $this->assertEquals(-114.1077228,$postCode->getLongitude());
        $this->assertEquals(51.149276800000003,$postCode->getLatitude());
    }

    public function testRequestIsEmpty()
    {
        $this->entityManager->expects($this->never())->method('getRepository')->with('NSDistanceBundle:PostalCode')->willReturn($this->repository);
        $this->repository->expects($this->never())->method('getByCode')->willReturn(null);
        $this->entityManager->expects($this->never())->method('persist');
        $this->entityManager->expects($this->never())->method('flush');

        $service = new PostalCodeChecker($this->entityManager);
        $this->assertNull($service->getLatitudeAndLongitude(''));
        $this->assertNull($service->getLatitudeAndLongitude(null));
    }

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(PostalCodeRepository::class)->disableOriginalConstructor()->getMock();

        $this->entityManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
    }
}
