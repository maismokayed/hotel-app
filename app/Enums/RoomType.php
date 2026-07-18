<?php

namespace App\Enums;

enum RoomType: string
{
    case SINGLE = 'single';
    case DOUBLE = 'double';
    case TWIN = 'twin';
    case SUITE = 'suite';
    case DELUXE = 'deluxe';

    public function label(): array
    {
        return match ($this) {
            self::SINGLE => ['ar' => 'مفردة',    'en' => 'Single'],
            self::DOUBLE => ['ar' => 'مزدوجة',   'en' => 'Double'],
            self::TWIN   => ['ar' => 'توأم',      'en' => 'Twin'],
            self::SUITE  => ['ar' => 'جناح',      'en' => 'Suite'],
            self::DELUXE => ['ar' => 'ديلوكس',    'en' => 'Deluxe'],
        };
    }
}
