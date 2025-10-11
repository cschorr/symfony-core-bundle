<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case NON_BINARY = 'non_binary';
    case DIVERSE = 'diverse';
    case PREFER_NOT_TO_SAY = 'prefer_not_to_say';

    public function getLabel(): string
    {
        return match ($this) {
            self::MALE => 'Male',
            self::FEMALE => 'Female',
            self::NON_BINARY => 'Non-Binary',
            self::DIVERSE => 'Diverse',
            self::PREFER_NOT_TO_SAY => 'Prefer Not to Say',
        };
    }

    public function getPronoun(): string
    {
        return match ($this) {
            self::MALE => 'he/him',
            self::FEMALE => 'she/her',
            self::NON_BINARY => 'they/them',
            self::DIVERSE => 'they/them',
            self::PREFER_NOT_TO_SAY => 'they/them',
        };
    }

    public function getSalutation(): string
    {
        return match ($this) {
            self::MALE => 'Mr.',
            self::FEMALE => 'Ms.',
            self::NON_BINARY => 'Mx.',
            self::DIVERSE => 'Mx.',
            self::PREFER_NOT_TO_SAY => '',
        };
    }
}
