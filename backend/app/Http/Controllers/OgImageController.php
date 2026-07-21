<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class OgImageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $title = $request->query('title', config('app.name'));
        $subtitle = $request->query('subtitle', 'Sklep internetowy');
        $badge = $request->query('badge', '');

        // Wymiary obrazu Open Graph (standard)
        $width = 1200;
        $height = 630;

        // Utwórz pusty obraz
        $image = imagecreatetruecolor($width, $height);
        if (!$image) {
            return response('Nie udało się zainicjalizować biblioteki GD.', 500);
        }

        // Włącz wygładzanie krawędzi (antialiasing)
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Kolory
        $bgDark = imagecolorallocate($image, 22, 22, 21); // #161615
        $bgLight = imagecolorallocate($image, 30, 30, 29); // #1E1E1D
        $primaryColor = imagecolorallocate($image, 245, 48, 3); // #F53003 (Logo/Accent)
        $textColor = imagecolorallocate($image, 255, 255, 255); // Biały
        $subtitleColor = imagecolorallocate($image, 161, 160, 154); // Szary
        $badgeBg = imagecolorallocate($image, 38, 38, 37); // Ciemniejszy szary
        $badgeText = imagecolorallocate($image, 255, 255, 255);

        // Narysuj gradient w tle (ciemny, elegancki)
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = (int) (22 + $ratio * 15);
            $g = (int) (22 + $ratio * 15);
            $b = (int) (21 + $ratio * 15);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $width, $y, $color);
        }

        // Rysowanie ozdobnych linii i kształtów (premium grid)
        $gridColor = imagecolorallocatealpha($image, 255, 255, 255, 5); // Bardzo subtelna linia
        for ($x = 100; $x < $width; $x += 100) {
            imageline($image, $x, 0, $x, $height, $gridColor);
        }
        for ($y = 100; $y < $height; $y += 100) {
            imageline($image, 0, $y, $width, $y, $gridColor);
        }

        // Szukanie czcionki TTF w systemie (Windows lub Linux)
        $fontPath = null;
        $fontPaths = [
            public_path('fonts/instrument-sans/InstrumentSans-Bold.ttf'), // ewentualna lokalna
            'C:\Windows\Fonts\arialbd.ttf', // Windows Bold
            'C:\Windows\Fonts\arial.ttf', // Windows Regular
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', // Linux Debian/Ubuntu
            '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf', // Linux Arch/CentOS
        ];

        foreach ($fontPaths as $path) {
            if (file_exists($path)) {
                $fontPath = $path;
                break;
            }
        }

        // Renderowanie tekstu
        if ($fontPath) {
            // Rysowanie logo / nazwy marki w lewym górnym rogu
            imagettftext($image, 24, 0, 80, 80, $primaryColor, $fontPath, config('app.name'));

            // Rysowanie badge, jeśli podano
            if (filled($badge)) {
                // Oblicz rozmiar badge
                $badgeBox = imagettftext($image, 14, 0, 80, 140, $badgeText, $fontPath, strtoupper($badge));
                // Dodamy tło dla badge (prostokąt zaokrąglony)
                $badgeWidth = $badgeBox[2] - $badgeBox[0] + 30;
                $badgeHeight = 35;
                imagefilledrectangle($image, 80, 115, 80 + $badgeWidth, 115 + $badgeHeight, $badgeBg);
                // Narysuj tekst ponownie na tle
                imagettftext($image, 12, 0, 95, 137, $primaryColor, $fontPath, strtoupper($badge));
                $startY = 220;
            } else {
                $startY = 180;
            }

            // Zawijanie tytułu (wordwrap)
            $wrappedTitle = wordwrap($title, 35, "\n");
            imagettftext($image, 46, 0, 80, $startY + 40, $textColor, $fontPath, $wrappedTitle);

            // Podtytuł (np. kategoria / cena / opis)
            imagettftext($image, 20, 0, 80, $height - 80, $subtitleColor, $fontPath, $subtitle);
        } else {
            // Prosty fallback na wbudowane czcionki GD w razie braku plików TTF
            imagestring($image, 5, 80, 60, config('app.name'), $primaryColor);
            
            if (filled($badge)) {
                imagestring($image, 3, 80, 100, strtoupper($badge), $primaryColor);
                $startY = 140;
            } else {
                $startY = 100;
            }

            imagestring($image, 5, 80, $startY, $title, $textColor);
            imagestring($image, 4, 80, $height - 80, $subtitle, $subtitleColor);
        }

        // Buforowanie i wysyłka obrazka
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();

        imagedestroy($image);

        return response($imageData, 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
