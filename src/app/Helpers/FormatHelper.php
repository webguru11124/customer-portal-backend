<?php

declare(strict_types=1);

namespace App\Helpers;

class FormatHelper
{
    public static function isValidEmail(string $email): bool
    {
        return $email === filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function isValidPhone(string $phoneNumber): bool
    {
        return (bool) preg_match('/^[2-9]\d{2}[2-9]\d{6}$/', $phoneNumber);
    }

    /**
     * @param string|string[] $input
     *
     * @return string|string[]
     */
    public static function stringToHashtag(string|array $input): string|array
    {
        $transform = fn (string $input) => '{' . $input . '}';

        return is_string($input)
            ? $transform($input)
            : array_map($transform, $input);
    }
}
