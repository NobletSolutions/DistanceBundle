<?php
/**
 * Created by PhpStorm.
 * User: gnat
 * Date: 23/02/17
 * Time: 10:33 AM
 */

namespace NS\DistanceBundle\Tests\Validator;

use NS\DistanceBundle\Validator\PostalCodeValidator;

class PostalCodeValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $code
     * @param bool $expected
     *
     * @dataProvider getPostalCodes
     */
    public function testValidation($code,$expected)
    {
        $this->assertEquals($expected, PostalCodeValidator::validate($code));
    }

    public function getPostalCodes()
    {
        return array(
            array('T3A5J4',true),
    	    array('90210',true),
            array('12345-6789',true),
            array('12345 6789', true),
            array('D8R0B9',false),
            array('F8R0C1',false),
            array('I8R0C2',false),
            array('O0A0B0',false),
            array('QA0C0',false),
            array('U0A0E0',false),
            array('W0A0G0',false),
            array('Z0A0H0',false),
            array('d8R0B9',false),
            array('f8R0C1',false),
            array('i8R0C2',false),
            array('o0A0B0',false),
            array('qA0C0',false),
            array('u0A0E0',false),
            array('w0A0G0',false),
            array('z0A0h0',false),
        );
    }
}
