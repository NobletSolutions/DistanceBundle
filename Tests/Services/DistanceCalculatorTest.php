<?php

namespace NS\DistanceBundle\Tests\Services;

use \NS\DistanceBundle\Services\DistanceCalculator;

/**
 * Description of DistanceCalculatorTest
 *
 * @author gnat
 */
class DistanceCalculatorTest extends \PHPUnit_Framework_TestCase
{
    public function testAdjustCodesArray()
    {
        $mockEntityMgr = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $calculator = new DistanceCalculator($mockEntityMgr);
        $codes = $calculator->adjustCodes('T2L 0w2', array('t3a5j4','H0h0h0'));
        $this->assertEquals($codes[0], 'T2L0W2');
        $this->assertEquals($codes[1], 'T3A5J4');
        $this->assertEquals($codes[2], 'H0H0H0');
    }

    public function testAdjustCodesNoArray()
    {
        $mockEntityMgr = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $calculator = new DistanceCalculator($mockEntityMgr);
        $codes = $calculator->adjustCodes('T2L 0w2', 't3a5j4');
        $this->assertEquals($codes[0], 'T2L0W2');
        $this->assertEquals($codes[1], 'T3A5J4');
    }

    /**
     * @dataProvider getPostalCodes
     */
    public function testCleanCode($code)
    {
        $mockEntityMgr = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $calculator = new DistanceCalculator($mockEntityMgr);
        $this->assertEquals('T2L0W2', $calculator->cleanCode($code));
    }

    public function getPostalCodes()
    {
        return array(
            array('code'=>'T2L 0W2'),
            array('code'=>'t2L 0W2'),
            array('code'=>'T2l 0w2'),
            array('code'=>'t2l0w2'),
            array('code'=>'t2L0W2'),
            array('code'=>'T2l0W2'),
            array('code'=>'T2L0w2'),
            );
    }
}