<?php

namespace App\Support;

class ImageResizer
{
    /**
     * Resize $absolutePath in place to at most $maxDim on the longer side,
     * re-saving as JPEG at $quality. No-op for files already smaller.
     *
     * Returns true on success or no-op, false if the resize failed.
     */
    public static function fit(string $absolutePath, int $maxDim = 1600, int $quality = 85): bool
    {
        if (! file_exists($absolutePath)) {
            return false;
        }

        $info = @getimagesize($absolutePath);
        if (! $info) {
            return false;
        }

        [$width, $height] = $info;
        if (max($width, $height) <= $maxDim) {
            // Already small enough — leave the file as-is.
            return true;
        }

        $ratio = $maxDim / max($width, $height);
        $newW = max(1, (int) round($width * $ratio));
        $newH = max(1, (int) round($height * $ratio));

        $src = null;
        switch ($info['mime']) {
            case 'image/jpeg':
                $src = @imagecreatefromjpeg($absolutePath);
                break;
            case 'image/png':
                $src = @imagecreatefrompng($absolutePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($absolutePath);
                }
                break;
            case 'image/gif':
                $src = @imagecreatefromgif($absolutePath);
                break;
        }
        if (! $src) {
            return false;
        }

        $dst = imagecreatetruecolor($newW, $newH);
        // White background for any source with transparency, since we re-save as JPEG.
        imagefilledrectangle($dst, 0, 0, $newW, $newH, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

        $ok = imagejpeg($dst, $absolutePath, $quality);

        imagedestroy($src);
        imagedestroy($dst);

        return $ok;
    }
}
