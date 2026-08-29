<?php

namespace Database\Seeders\Support;

/**
 * مولّد صور تجريبية (Placeholder) للفنادق والغرف باستخدام مكتبة GD.
 *
 * الهدف منه أن تحتوي بيانات الـ Demo على صور فعلية بدون الحاجة
 * لتنزيل صور من الإنترنت أو إضافة ملفات صور إلى المستودع.
 */
class DemoImageGenerator
{
    /** حالة المولّد العشوائي (LCG) حتى تكون الصور ثابتة لنفس البذرة. */
    private int $state;

    public function __construct(int $seed)
    {
        $this->state = abs($seed) % 2147483647 ?: 42;
    }

    /** هل إضافة GD متوفرة؟ */
    public static function available(): bool
    {
        return function_exists('imagecreatetruecolor')
            && function_exists('imagejpeg')
            && function_exists('imagecreate');
    }

    /** صورة واجهة فندق (مبانٍ + سماء متدرجة). */
    public static function hotel(string $title, string $subtitle, int $seed): ?string
    {
        return (new self($seed))->render($title, $subtitle, 1200, 800, 'hotel');
    }

    /** صورة غرفة (سرير + شباك). */
    public static function room(string $title, string $subtitle, int $seed): ?string
    {
        return (new self($seed))->render($title, $subtitle, 900, 600, 'room');
    }

    private function render(string $title, string $subtitle, int $width, int $height, string $scene): ?string
    {
        if (! self::available()) {
            return null;
        }

        $image = imagecreatetruecolor($width, $height);
        $hue   = $this->rand(0, 359);

        $this->gradient($image, $width, $height, $hue);

        $scene === 'hotel'
            ? $this->drawBuildings($image, $width, $height, $hue)
            : $this->drawRoom($image, $width, $height, $hue);

        $this->drawCaption($image, $width, $height, $title, $subtitle);

        ob_start();
        imagejpeg($image, null, 82);
        $bytes = ob_get_clean();

        imagedestroy($image);

        return $bytes ?: null;
    }

    /** خلفية متدرجة عمودياً. */
    private function gradient($image, int $width, int $height, int $hue): void
    {
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / max(1, $height - 1);
            [$r, $g, $b] = $this->hsl($hue, 0.42, 0.62 - (0.34 * $ratio));

            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }
    }

    /** مبانٍ بسيطة مع شبابيك مضاءة. */
    private function drawBuildings($image, int $width, int $height, int $hue): void
    {
        $ground = (int) ($height * 0.78);

        [$r, $g, $b] = $this->hsl(($hue + 200) % 360, 0.30, 0.16);
        imagefilledrectangle($image, 0, $ground, $width, $height, imagecolorallocate($image, $r, $g, $b));

        $windowLit  = imagecolorallocate($image, 250, 226, 150);
        $windowDark = imagecolorallocate($image, 40, 48, 62);

        $x = -20;

        while ($x < $width) {
            $buildingWidth  = $this->rand((int) ($width * 0.08), (int) ($width * 0.16));
            $buildingHeight = $this->rand((int) ($height * 0.18), (int) ($height * 0.50));
            $top            = $ground - $buildingHeight;

            [$r, $g, $b] = $this->hsl(($hue + 210) % 360, 0.28, $this->rand(18, 30) / 100);
            imagefilledrectangle($image, $x, $top, $x + $buildingWidth, $ground, imagecolorallocate($image, $r, $g, $b));

            for ($wy = $top + 14; $wy < $ground - 16; $wy += 26) {
                for ($wx = $x + 12; $wx < $x + $buildingWidth - 14; $wx += 24) {
                    $color = $this->rand(0, 100) > 45 ? $windowLit : $windowDark;
                    imagefilledrectangle($image, $wx, $wy, $wx + 10, $wy + 14, $color);
                }
            }

            $x += $buildingWidth + $this->rand(6, 22);
        }
    }

    /** مشهد غرفة مبسّط: سرير + وسادتان + شباك. */
    private function drawRoom($image, int $width, int $height, int $hue): void
    {
        $floor = (int) ($height * 0.62);

        [$r, $g, $b] = $this->hsl(($hue + 24) % 360, 0.24, 0.24);
        imagefilledrectangle($image, 0, $floor, $width, $height, imagecolorallocate($image, $r, $g, $b));

        // شباك
        $windowLeft = (int) ($width * 0.62);
        $windowTop  = (int) ($height * 0.14);
        imagefilledrectangle(
            $image,
            $windowLeft,
            $windowTop,
            $windowLeft + (int) ($width * 0.26),
            $windowTop + (int) ($height * 0.30),
            imagecolorallocate($image, 178, 214, 236)
        );

        // سرير
        $bedLeft  = (int) ($width * 0.10);
        $bedRight = (int) ($width * 0.58);
        $bedTop   = (int) ($height * 0.48);

        imagefilledrectangle($image, $bedLeft, $bedTop, $bedRight, $floor + 40, imagecolorallocate($image, 236, 232, 224));
        imagefilledrectangle($image, $bedLeft, $bedTop - 46, $bedLeft + 26, $floor + 40, imagecolorallocate($image, 96, 74, 58));

        [$r, $g, $b] = $this->hsl(($hue + 180) % 360, 0.40, 0.42);
        imagefilledrectangle(
            $image,
            $bedLeft,
            (int) ($bedTop + ($floor - $bedTop) * 0.75),
            $bedRight,
            $floor + 22,
            imagecolorallocate($image, $r, $g, $b)
        );

        $pillow = imagecolorallocate($image, 252, 250, 246);
        imagefilledrectangle($image, $bedLeft + 40, $bedTop - 26, $bedLeft + 150, $bedTop + 12, $pillow);
        imagefilledrectangle($image, $bedLeft + 170, $bedTop - 26, $bedLeft + 280, $bedTop + 12, $pillow);
    }

    /** شريط سفلي شفاف + اسم الفندق/الغرفة. */
    private function drawCaption($image, int $width, int $height, string $title, string $subtitle): void
    {
        $barTop = (int) ($height * 0.80);

        $overlay = imagecolorallocatealpha($image, 12, 16, 24, 55);
        imagefilledrectangle($image, 0, $barTop, $width, $height, $overlay);

        $scale = max(2, (int) round($width / 420));

        $this->drawText($image, $title, (int) ($width / 2), $barTop + (int) ($height * 0.045), $scale, [255, 255, 255]);

        if ($subtitle !== '') {
            $this->drawText($image, $subtitle, (int) ($width / 2), $barTop + (int) ($height * 0.115), max(1, $scale - 1), [226, 232, 240]);
        }
    }

    /**
     * كتابة نص بخط GD المدمج ثم تكبيره (الخطوط المدمجة تدعم ASCII فقط،
     * لذلك نستخدم الاسم الإنكليزي ونتجاهل باقي المحارف).
     */
    private function drawText($image, string $text, int $centerX, int $y, int $scale, array $rgb): void
    {
        $text = trim(preg_replace('/[^\x20-\x7E]/', '', $text));

        if ($text === '') {
            return;
        }

        $font       = 5;
        $textWidth  = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);

        $layer      = imagecreate($textWidth, $textHeight);
        $background = imagecolorallocate($layer, 0, 0, 0);
        imagecolortransparent($layer, $background);
        $foreground = imagecolorallocate($layer, $rgb[0], $rgb[1], $rgb[2]);
        imagestring($layer, $font, 0, 0, $text, $foreground);

        imagecopyresampled(
            $image,
            $layer,
            (int) ($centerX - ($textWidth * $scale) / 2),
            $y,
            0,
            0,
            $textWidth * $scale,
            $textHeight * $scale,
            $textWidth,
            $textHeight
        );

        imagedestroy($layer);
    }

    /** HSL -> RGB (الإشباع والإضاءة بين 0 و 1). */
    private function hsl(int $hue, float $saturation, float $lightness): array
    {
        $lightness = max(0.0, min(1.0, $lightness));

        $c = (1 - abs(2 * $lightness - 1)) * $saturation;
        $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
        $m = $lightness - $c / 2;

        [$r, $g, $b] = match (true) {
            $hue < 60  => [$c, $x, 0],
            $hue < 120 => [$x, $c, 0],
            $hue < 180 => [0, $c, $x],
            $hue < 240 => [0, $x, $c],
            $hue < 300 => [$x, 0, $c],
            default    => [$c, 0, $x],
        };

        return [
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        ];
    }

    /** مولّد عشوائي بسيط وثابت النتيجة لنفس البذرة. */
    private function rand(int $min, int $max): int
    {
        $this->state = ($this->state * 1103515245 + 12345) % 2147483648;

        return $min + ($this->state % max(1, $max - $min + 1));
    }
}
