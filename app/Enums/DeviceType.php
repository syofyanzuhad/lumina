<?php

namespace App\Enums;

enum DeviceType: string
{
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Desktop = 'desktop';
    case Unknown = 'unknown';

    public static function fromScreenWidth(?int $width): self
    {
        if ($width === null || $width <= 0) {
            return self::Unknown;
        }

        if ($width < 768) {
            return self::Mobile;
        }

        if ($width >= 768 && $width <= 1024) {
            return self::Tablet;
        }

        return self::Desktop;
    }
}
