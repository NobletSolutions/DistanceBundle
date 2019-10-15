<?php

namespace NS\DistanceBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;

class DistanceTest extends TestCase
{
    public function testDistanceEntity()
    {
        $distance = new \NS\DistanceBundle\Entity\Distance(1);
        $this->assertEquals(1.609344,$distance->getDistance('K'));
        $this->assertEquals(0.8684,$distance->getDistance('NM'));
        $this->assertEquals(1,$distance->getDistance('M'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidDistanceEntity()
    {
        $distance = new \NS\DistanceBundle\Entity\Distance(1);
        $this->assertEquals(1.609344,$distance->getDistance('KM'));
    }
}
