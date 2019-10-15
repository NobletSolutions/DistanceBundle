<?php

namespace NS\DistanceBundle\Tests\Entity;

use NS\DistanceBundle\Entity\PostalCode;
use PHPUnit\Framework\TestCase;

class PostalCodeTest extends TestCase
{
    /**
     * @param $input
     * @param $output
     *
     * @dataProvider getPostalCodes
     */
    public function testFixPostalCodes($input, $output): void
    {
        $obj = new PostalCode();
        $obj->setPostalCode($input);
        $this->assertEquals($output,$obj->getPostalCode());
    }

    public function getPostalCodes(): array
    {
        return array(
            array('T3A 5J4','T3A5J4'),
            array('t3A5j4','T3A5J4'),
            array('t 2 l 0 w 2','T2L0W2')
        );
    }
}
