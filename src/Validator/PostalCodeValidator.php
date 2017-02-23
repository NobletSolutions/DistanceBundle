<?php
/**
 * Created by PhpStorm.
 * User: gnat
 * Date: 23/02/17
 * Time: 10:28 AM
 */

namespace NS\DistanceBundle\Validator;

class PostalCodeValidator
{
    public static function validate($postalCode)
    {
        return (preg_match('/^([0-9]{5})([- ]?[0-9]{4})?$/i', $postalCode) || preg_match('/(^\d{5}(-\d{4})?$)|(^[ABCEGHJKLMNPRSTVXYabceghjklmnprstvxy]{1}\d{1}[A-Za-z]{1} *\d{1}[A-Za-z]{1}\d{1}$)/', $postalCode));
    }
}
