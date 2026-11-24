<?php

namespace App\Domains\Catalog\Services;

class BarcodeService
{
    /**
     * Code 39 character patterns: 5 bars & 4 spaces (1 = wide, 0 = narrow).
     *
     * @var array<string, string>
     */
    protected const CODE39_PATTERNS = [
        '0' => '000110100', '1' => '100100001', '2' => '001100001', '3' => '101100000',
        '4' => '000110001', '5' => '100110000', '6' => '001110000', '7' => '000100101',
        '8' => '100100100', '9' => '001100100', 'A' => '100001001', 'B' => '001001001',
        'C' => '101001000', 'D' => '000011001', 'E' => '100011000', 'F' => '001011000',
        'G' => '000001101', 'H' => '100001100', 'I' => '001001100', 'J' => '000011100',
        'K' => '100000011', 'L' => '001000011', 'M' => '101000010', 'N' => '000010011',
        'O' => '100010010', 'P' => '001010010', 'Q' => '000000111', 'R' => '100000110',
        'S' => '001000110', 'T' => '000010110', 'U' => '110000001', 'V' => '011000001',
        'W' => '111000000', 'X' => '010010001', 'Y' => '110010000', 'Z' => '011010000',
        '-' => '010000101', '.' => '110000100', ' ' => '011000100', '*' => '010010100',
        '$' => '010101000', '/' => '010100010', '+' => '010001010', '%' => '000101010',
    ];

    /**
     * Generate scannable Code 39 SVG element.
     */
    public function generateCode39Svg(
        string $code,
        int $height = 28,
        float $narrowWidth = 1.3,
        float $wideWidth = 2.9
    ): string {
        $cleanCode = strtoupper(trim($code));
        $cleanCode = preg_replace('/[^0-9A-Z\-\. \$\/\+\%]/', '-', $cleanCode);
        if ($cleanCode === '') {
            $cleanCode = 'PRD-001';
        }

        $formatted = "*{$cleanCode}*";
        $chars = str_split($formatted);

        $rects = [];
        $currentX = 0.0;

        foreach ($chars as $index => $char) {
            $pattern = self::CODE39_PATTERNS[$char] ?? self::CODE39_PATTERNS['-'];

            for ($i = 0; $i < 9; $i++) {
                $isBar = ($i % 2 === 0);
                $isWide = ($pattern[$i] === '1');
                $width = $isWide ? $wideWidth : $narrowWidth;

                if ($isBar) {
                    $rects[] = sprintf(
                        '<rect x="%.2f" y="0" width="%.2f" height="%d" fill="#000000" />',
                        $currentX,
                        $width,
                        $height
                    );
                }

                $currentX += $width;
            }

            if ($index < count($chars) - 1) {
                $currentX += $narrowWidth;
            }
        }

        $totalWidth = (int) ceil($currentX);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" style="width: 100%%; max-width: %dpx; height: %dpx; display: block; margin: 0 auto;" preserveAspectRatio="xMidYMid meet">%s</svg>',
            $totalWidth,
            $height,
            $totalWidth,
            $height,
            implode('', $rects)
        );
    }
}
