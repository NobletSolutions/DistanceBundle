<?php

namespace NS\DistanceBundle\Validator;

class PostalCodeValidator
{
    public static function normalize(string $postalCode): string
    {
        return strtoupper(preg_replace('/\s+/', '', $postalCode));
    }

    public static function formatCanadianCode(string $normalizedCode): ?string
    {
        if (strlen($normalizedCode) === 6 && self::validate($normalizedCode)) {
            return substr_replace($normalizedCode, ' ', 3, 0);
        }

        return null;
    }

    public static function validate($postalCode)
    {
        return (preg_match('/^([0-9]{5})([- ]?[0-9]{4})?$/i', $postalCode) || preg_match('/(^\d{5}(-\d{4})?$)|(^[ABCEGHJKLMNPRSTVXYabceghjklmnprstvxy]{1}\d{1}[A-Za-z]{1} *\d{1}[A-Za-z]{1}\d{1}$)/', $postalCode));
    }
}
