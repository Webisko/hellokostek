# Lista Zadań: Naprawa Pierwszego Zdjęcia w Galerii

- [x] Krok 1: Wyrównanie zasobów w projekcie lokalnym <!-- id: 0 -->
    - [x] Skopiowanie `portret_Leona.webp` do `public/images/portret_Leona.webp` <!-- id: 1 -->
    - [x] Aktualizacja `src/data/gallery.ts` (`gallery-1` -> `/images/portret_Leona.webp`) <!-- id: 2 -->
    - [x] Aktualizacja `HelloKostekSeeder.php` (`Portret Kobiety` -> `images/portret_Leona.webp`) <!-- id: 3 -->
- [x] Krok 2: Wdrożenie na serwer produkcyjny SEOHost <!-- id: 4 -->
    - [x] Przesłanie pliku `portret_Leona.webp` do `public_html/images/` oraz `backend/public/images/` <!-- id: 5 -->
    - [x] Aktualizacja rekordu w bazie danych MySQL na serwerze (`image_path = 'images/portret_Leona.webp'`) <!-- id: 6 -->
    - [x] Build frontendu (`npm run build`) i wdrożenie `dist/` na SEOHost <!-- id: 7 -->
- [x] Krok 3: Weryfikacja końcowa <!-- id: 8 -->
    - [x] Sprawdzenie API `GET https://panel.hellokostek.pl/api/gallery` <!-- id: 9 -->
    - [x] Sprawdzenie kodu HTTP pliku `https://hellokostek.pl/images/portret_Leona.webp` <!-- id: 10 -->
    - [x] Weryfikacja w przeglądarce pod adresem `https://hellokostek.pl/galeria` (potwierdzona przez użytkownika w oknie incognito) <!-- id: 11 -->
