<?php

namespace Lumina\Core\Enums;

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

    public static function fromUserAgent(string $userAgent): self
    {
        if (trim($userAgent) === '') {
            return self::Unknown;
        }

        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
            return self::Tablet;
        }

        if (preg_match('/(mobile|iphone|ipod|android|blackberry|opera mini|opera mobi|windows phone|iemobile)/i', $userAgent)) {
            return self::Mobile;
        }

        return self::Desktop;
    }
}
