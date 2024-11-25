<?php

namespace App\Tests\Fixtures;

class GenerateTestImage
{
    public static function create(): string
    {
        // Create a blank image
        $image = imagecreatetruecolor(100, 100);

        // Fill the image with a color
        $bg = imagecolorallocate($image, 255, 255, 255);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $bg);

        // Add some text
        imagestring($image, 5, 10, 40, 'Test Image', $text_color);

        // Save the image
        $outputPath = __DIR__ . '/test.jpg';
        imagejpeg($image, $outputPath);
        imagedestroy($image);

        return $outputPath;
    }
}
