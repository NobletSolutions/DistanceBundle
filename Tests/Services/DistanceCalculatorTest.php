<?php

namespace NS\DistanceBundle\Tests\Services;

use \NS\DistanceBundle\Entity\PostalCode;
use \NS\DistanceBundle\Services\DistanceCalculator;

/**
 * Description of DistanceCalculatorTest
 *
 * @author gnat
 */
class DistanceCalculatorTest extends \PHPUnit_Framework_TestCase
{

    public function testMultipleGetDistanceByCode()
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('getByCodes'))
            ->getMock();

        $repo->expects($this->once())
            ->method('getByCodes')
            ->with(array('T3A5J4', 'T2L0W2', 'T3A0A1'))
            ->willReturn($this->getPostalCodesObjects(true));

        $mockEntityMgr = $this->getEntityManager();
        $mockEntityMgr->expects($this->once())
            ->method('getRepository')
            ->with('NSDistanceBundle:PostalCode')
            ->willReturn($repo);

        $calculator = new DistanceCalculator($mockEntityMgr);
        $distance   = $calculator->getDistanceBetweenPostalCodes('T3A5J4', array(
            'T2L0W2', 'T3A0A1'));

        $this->assertNotEmpty($distance);
        $this->assertCount(1, $distance);
        $this->assertArrayHasKey('T3A5J4', $distance);
        $this->assertCount(2, $distance['T3A5J4'], print_r($distance['T3A5J4'],true));
        $this->assertArrayHasKey('T2L0W2', $distance['T3A5J4']);
        $this->assertArrayHasKey('T3A0A1', $distance['T3A5J4']);
        $this->assertInstanceOf('NS\DistanceBundle\Entity\Distance', $distance['T3A5J4']['T2L0W2']);
    }

    public function testSimpleGetDistanceByCode()
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('getByCodes'))
            ->getMock();

        $repo->expects($this->once())
            ->method('getByCodes')
            ->with(array('T3A5J4', 'T2L0W2'))
            ->willReturn($this->getPostalCodesObjects());

        $mockEntityMgr = $this->getEntityManager();

        $mockEntityMgr->expects($this->once())
            ->method('getRepository')
            ->with('NSDistanceBundle:PostalCode')
            ->willReturn($repo);

        $calculator = new DistanceCalculator($mockEntityMgr);
        $distance   = $calculator->getDistanceBetweenPostalCodes('T3A5J4', 'T2L0W2');
        $this->assertNotEmpty($distance);
        $this->assertCount(1, $distance);
        $this->assertArrayHasKey('T3A5J4', $distance);
        $this->assertArrayHasKey('T2L0W2', $distance['T3A5J4']);
        $this->assertInstanceOf('NS\DistanceBundle\Entity\Distance', $distance['T3A5J4']['T2L0W2']);
    }

    public function testGetDistanceByCodeIsEmpty()
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('getByCodes'))
            ->getMock();

        $repo->expects($this->once())
            ->method('getByCodes')
            ->with(array('T3A5J4', 'T2L0W2'))
            ->willReturn(array());

        $mockEntityMgr = $this->getEntityManager();

        $mockEntityMgr->expects($this->once())
            ->method('getRepository')
            ->with('NSDistanceBundle:PostalCode')
            ->willReturn($repo);

        $calculator = new DistanceCalculator($mockEntityMgr);
        $distance   = $calculator->getDistanceBetweenPostalCodes('T3A5J4', 'T2L0W2');
        $this->assertEmpty($distance);
    }

    public function testSimpleGetDistanceByCodeSameCodeIsZero()
    {
        $mockEntityMgr = $this->getEntityManager();

        $mockEntityMgr->expects($this->never())
            ->method('getRepository')
            ->with('NSDistanceBundle:PostalCode');

        $calculator = new DistanceCalculator($mockEntityMgr);
        $distance   = $calculator->getDistanceBetweenPostalCodes('T3A5J4', 'T3A5J4');
        $this->assertCount(1, $distance);
        $this->assertArrayHasKey('T3A5J4', $distance);
        $this->assertArrayHasKey('T3A5J4', $distance['T3A5J4']);
        $this->assertInstanceOf('NS\DistanceBundle\Entity\Distance', $distance['T3A5J4']['T3A5J4']);
    }

    public function testIncludingSourceAndDestCodesResultsInZeroDistance()
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('getByCodes'))
            ->getMock();

        $repo->expects($this->once())
            ->method('getByCodes')
            ->with(array('T3A5J4', 'T2L0W2', 'T3A5J4'))
            ->willReturn($this->getPostalCodesObjects());

        $mockEntityMgr = $this->getEntityManager();

        $mockEntityMgr->expects($this->once())
            ->method('getRepository')
            ->with('NSDistanceBundle:PostalCode')
            ->willReturn($repo);

        $calculator = new DistanceCalculator($mockEntityMgr);
        $distance   = $calculator->getDistanceBetweenPostalCodes('T3A5J4', array('T2L0W2','T3A5J4'));
        $this->assertNotEmpty($distance);
        $this->assertCount(1, $distance);
        $this->assertArrayHasKey('T3A5J4', $distance);
        $this->assertCount(2, $distance['T3A5J4'],print_r($distance,true));
        $this->assertArrayHasKey('T2L0W2', $distance['T3A5J4']);
        $this->assertArrayHasKey('T3A5J4', $distance['T3A5J4']);
        $this->assertInstanceOf('NS\DistanceBundle\Entity\Distance', $distance['T3A5J4']['T2L0W2']);
    }

    public function testAdjustCodesHasDuplicates()
    {
        $mockEntityMgr = $this->getEntityManager();
        $calculator = new DistanceCalculator($mockEntityMgr);
        $this->assertEquals(array('T3A5J4','T2L0W2','T3A5J4'),$calculator->adjustCodes('T3A5J4', array('T2L0W2','T3A5J4')));
    }

    public function testGetDistance()
    {
        $mockEntityMgr = $this->getEntityManager();
        $ret = $this->getPostalCodesObjects();

        $calculator = new DistanceCalculator($mockEntityMgr);
        $distance   = $calculator->getDistance(current($ret), end($ret));

        $this->assertInstanceOf('NS\DistanceBundle\Entity\Distance', $distance);
        $this->assertEquals(9.5858131440110874, $distance->getDistance());
    }

    public function testAdjustCodesArray()
    {
        $mockEntityMgr = $this->getEntityManager();
        $calculator = new DistanceCalculator($mockEntityMgr);
        $codes      = $calculator->adjustCodes('T2L 0w2', array('t3a5j4', 'H0h0h0'));
        $this->assertEquals($codes[0], 'T2L0W2');
        $this->assertEquals($codes[1], 'T3A5J4');
        $this->assertEquals($codes[2], 'H0H0H0');
    }

    public function testAdjustCodesNoArray()
    {
        $mockEntityMgr = $this->getEntityManager();
        $calculator = new DistanceCalculator($mockEntityMgr);
        $codes      = $calculator->adjustCodes('T2L 0w2', 't3a5j4');
        $this->assertEquals($codes[0], 'T2L0W2');
        $this->assertEquals($codes[1], 'T3A5J4');
    }

    /**
     * @expectedException NS\DistanceBundle\Exceptions\UnknownPostalCodeException
     */
    public function testNoSourcePostalCode()
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('getByCodes'))
            ->getMock();

        $repo->expects($this->once())
            ->method('getByCodes')
            ->with(array('H0H0H0', 'T2A6J3'))
            ->willReturn($this->getPostalCodesObjects());

        $mockEntityMgr = $this->getEntityManager();

        $mockEntityMgr->expects($this->once())
            ->method('getRepository')
            ->with('NSDistanceBundle:PostalCode')
            ->willReturn($repo);

        $calculator = new DistanceCalculator($mockEntityMgr);
        $distance   = $calculator->getDistanceBetweenPostalCodes('H0H0H0', 'T2A6J3');
        $this->assertEmpty($distance);
    }

    /**
     * @dataProvider getPostalCodes
     */
    public function testCleanCode($code)
    {
        $mockEntityMgr = $this->getEntityManager();
        $calculator    = new DistanceCalculator($mockEntityMgr);
        $this->assertEquals('T2L0W2', $calculator->cleanCode($code));
    }

    public function getPostalCodes()
    {
        return array(
            array('code' => 'T2L 0W2'),
            array('code' => 't2L 0W2'),
            array('code' => 'T2l 0w2'),
            array('code' => 't2l0w2'),
            array('code' => 't2L0W2'),
            array('code' => 'T2l0W2'),
            array('code' => 'T2L0w2'),
        );
    }

    public function getPostalCodesObjects($multiple = false)
    {
        $source = new PostalCode();
        $source->setPostalCode('T3A5J4');
        $source->setLatitude(51.0177200000);
        $source->setLongitude(-114.1961230000);

        $dest = new PostalCode();
        $dest->setPostalCode('T2L0W2');
        $dest->setLatitude(51.0867200000);
        $dest->setLongitude(-114.1139010000);

        if ($multiple) {
            $mult = new PostalCode();
            $mult->setPostalCode('T3A0A1');
            $mult->setLatitude(51.0820790000);
            $mult->setLongitude(-114.1429310000);

            return array('T3A5J4' => $source, 'T2L0W2' => $dest, 'T3A0A1' => $mult);
        }

        return array('T3A5J4' => $source, 'T2L0W2' => $dest);
    }

    public function getEntityManager(array $methods = array())
    {
        $objMethods = array('find','persist','remove','flush','detach','refresh','merge','clear','getClassMetadata','getMetadataFactory','initializeObject','getRepository','contains');
        return $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->setMethods(array_merge($objMethods,$methods))
            ->getMock();

    }
}
