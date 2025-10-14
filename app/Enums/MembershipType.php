<?php

namespace App\Enums;

enum MembershipType: string
{
    case Premium = 'premium';
    case Regular = 'regular';

    public function label(): string
    {
        return match ($this) {
            self::Premium => 'Premium Member',
            self::Regular => 'Regular Member',
        };
    }
}
