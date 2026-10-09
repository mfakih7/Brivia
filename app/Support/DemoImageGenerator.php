<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Generates abstract, text-free interface illustrations locally (GD) for demo content.
 * They depict no real product, client or screenshot.
 */
class DemoImageGenerator
{
    /** @param array{0:int,1:int,2:int} $tint */
    public function make(string $style, array $tint, int $seed, int $w = 1600, int $h = 1000): UploadedFile
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);

        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            imageline($img, 0, $y, $w, $y, imagecolorallocate($img, (int) (6 + $tint[0] * $t), (int) (21 + $tint[1] * $t), (int) (40 + $tint[2] * $t)));
        }

        $white = fn (int $a) => imagecolorallocatealpha($img, 255, 255, 255, $a);
        $cyan = imagecolorallocatealpha($img, 0, 203, 234, 30);
        $blue = imagecolorallocatealpha($img, 0, 107, 255, 25);
        $rnd = fn (int $i, int $mod) => ($i * 37 + $seed * 53) % $mod;

        match ($style) {
            'website' => $this->website($img, $w, $h, $white, $cyan, $blue, $rnd),
            'dashboard' => $this->dashboard($img, $w, $h, $white, $cyan, $blue, $rnd),
            'mobile' => $this->mobile($img, $w, $h, $white, $cyan, $blue, $rnd),
            default => $this->legacy($img, $w, $h, $white, $cyan, $blue, $rnd),
        };

        imagesetthickness($img, 5);
        imagearc($img, $w - 300 - $seed * 90, $h + 160, 1500, 900, 200, 340, $cyan);

        $path = tempnam(sys_get_temp_dir(), 'brvdemo');
        imagepng($img, $path);
        imagedestroy($img);

        return new UploadedFile($path, "demo-{$style}-{$seed}.png", 'image/png', null, true);
    }

    private function website($img, int $w, int $h, $white, $cyan, $blue, $rnd): void
    {
        imagefilledrectangle($img, 140, 120, $w - 140, $h - 120, $white(108));
        imagefilledrectangle($img, 140, 120, $w - 140, 190, $white(95));
        foreach ([0, 1, 2, 3] as $i) {
            imagefilledrectangle($img, $w - 640 + $i * 110, 145, $w - 560 + $i * 110, 165, $white(70));
        }
        imagefilledrectangle($img, 220, 260, 860, 330, $white(60));
        imagefilledrectangle($img, 220, 360, 760, 395, $white(85));
        imagefilledrectangle($img, 220, 440, 420, 500, $blue);
        imagefilledellipse($img, 1150, 420, 420, 340, $cyan);
        foreach ([0, 1, 2] as $i) {
            imagefilledrectangle($img, 220 + $i * 400, 600, 560 + $i * 400, 820, $white(98));
            imagefilledrectangle($img, 250 + $i * 400, 630, 330 + $i * 400, 690, $i === 1 ? $cyan : $blue);
        }
    }

    private function dashboard($img, int $w, int $h, $white, $cyan, $blue, $rnd): void
    {
        imagefilledrectangle($img, 140, 120, $w - 140, $h - 120, $white(110));
        imagefilledrectangle($img, 140, 120, 400, $h - 120, $white(96));
        foreach (range(0, 5) as $i) {
            imagefilledrectangle($img, 180, 200 + $i * 60, 360, 225 + $i * 60, $white($i === 0 ? 70 : 100));
        }
        foreach ([0, 1, 2] as $i) {
            imagefilledrectangle($img, 450 + $i * 330, 170, 750 + $i * 330, 320, $white(96));
            imagefilledrectangle($img, 480 + $i * 330, 250, 600 + $i * 330, 280, $white(60));
        }
        for ($i = 0; $i < 12; $i++) {
            $bar = 80 + $rnd($i, 300);
            imagefilledrectangle($img, 470 + $i * 80, 820 - $bar, 520 + $i * 80, 820, $i % 3 === 0 ? $cyan : $blue);
        }
    }

    private function mobile($img, int $w, int $h, $white, $cyan, $blue, $rnd): void
    {
        foreach ([0, 1, 2] as $i) {
            $x = 260 + $i * 400;
            $y = 120 + ($i === 1 ? 0 : 60);
            imagefilledrectangle($img, $x, $y, $x + 300, $y + 700, $white(100));
            imagefilledrectangle($img, $x + 25, $y + 40, $x + 275, $y + 180, $i === 1 ? $cyan : $blue);
            foreach (range(0, 4) as $r) {
                imagefilledrectangle($img, $x + 25, $y + 220 + $r * 85, $x + 275, $y + 280 + $r * 85, $white(96));
                imagefilledellipse($img, $x + 60, $y + 250 + $r * 85, 36, 36, $white(70));
            }
        }
    }

    private function legacy($img, int $w, int $h, $white, $cyan, $blue, $rnd): void
    {
        imagefilledrectangle($img, 120, 160, 760, 840, $white(112));
        foreach (range(0, 9) as $r) {
            imagefilledrectangle($img, 150, 200 + $r * 60, 730, 240 + $r * 60, $white($r % 2 ? 104 : 98));
        }
        imagefilledrectangle($img, 840, 160, $w - 120, 840, $white(100));
        imagefilledrectangle($img, 880, 200, $w - 160, 280, $blue);
        foreach ([0, 1] as $i) {
            imagefilledrectangle($img, 880 + $i * 300, 330, 1140 + $i * 300, 560, $white(94));
        }
        imagefilledrectangle($img, 880, 610, $w - 160, 800, $cyan);
    }
}
