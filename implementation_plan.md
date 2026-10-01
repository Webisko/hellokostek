# Plan Wdrożenia: Naprawa Pierwszego Zdjęcia w Galerii ("Portret Kobiety")

## 1. Diagnoza Problemu

Użytkownik zgłosił, że po wejściu na stronę galerii (`/galeria`) pierwsze zdjęcie najpierw wyświetla się prawidłowo, a po około sekundzie zmienia się na inne (nieprawidłowe).

### Przyczyna źródłowa:
1. **Renderowanie początkowe (Astro SSG/SSR):**
   - Podczas generowania statycznego HTML Astro korzysta z pliku [`src/data/gallery.ts`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/data/gallery.ts).
   - Pierwsza pozycja (`gallery-1`, *"Portret Kobiety"*) posiadała jako źródło importowany plik `src/assets/portret_Leona.webp`, który przedstawia właściwy portret kobiety w niebieskiej bluzce.
2. **Hydratacja asynchroniczna w React (`useEffect` po ~1s):**
   - Komponent [`Gallery.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Gallery.tsx) pobiera asynchronicznie listę prac z API backendu: `https://panel.hellokostek.pl/api/gallery`.
   - W bazie danych na serwerze produkcyjnym oraz w pliku seedera [`HelloKostekSeeder.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/database/seeders/HelloKostekSeeder.php) rekord `gallery-1` (*"Portret Kobiety"*) miał błędnie przypisaną ścieżkę:
     `image_path = "images/portret_franka_mobile.webp"` (zdjęcie małego chłopca Franka na sztaludze).
   - Po odebraniu odpowiedzi z API React nadpisywał stan `artworks`, przez co po ~1 sekundzie pierwsze zdjęcie na oczach użytkownika zamieniało się z kobiety na Franka.

---

## 2. Proponowane Rozwiązanie i Plan Działań

### Krok 1: Wyrównanie zasobów w projekcie lokalnym (Frontend & Backend)
1. Skopiowanie pliku [`portret_Leona.webp`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/assets/portret_Leona.webp) do folderu statycznego `public/images/portret_Leona.webp`.
2. Zaktualizowanie [`src/data/gallery.ts`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/data/gallery.ts) dla `gallery-1`:
   - `imageUrl: "/images/portret_Leona.webp"`
   - `originalUrl: "/images/portret_Leona.webp"`
3. Zaktualizowanie [`HelloKostekSeeder.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/database/seeders/HelloKostekSeeder.php) dla pozycji `Portret Kobiety`:
   - `'image_path' => 'images/portret_Leona.webp'`
   - `'original_url' => '/images/portret_Leona.webp'`

### Krok 2: Wdrożenie na serwer produkcyjny SEOHost (`h93.seohost.pl`)
1. Przesłanie pliku `portret_Leona.webp` przez SSH/SCP na serwer:
   - `/home/srv124983/domains/hellokostek.pl/public_html/images/portret_Leona.webp`
   - `/home/srv124983/domains/hellokostek.pl/backend/public/images/portret_Leona.webp`
2. Bezpośrednia aktualizacja rekordu w bazie danych MySQL na serwerze SEOHost:
   - Ustawienie dla `Portret Kobiety` (`sort_order = 10` lub `id = 1`):
     `image_path = 'images/portret_Leona.webp'`, `original_url = '/images/portret_Leona.webp'`.
3. Przebudowanie frontendu lokalnie (`npm run build`) i zsynchronizowanie `dist/` do `/home/srv124983/domains/hellokostek.pl/public_html/`.

### Krok 3: Weryfikacja końcowa
1. Test punktu końcowego API: `https://panel.hellokostek.pl/api/gallery` – potwierdzenie, że pozycja 1 zwraca `images/portret_Leona.webp`.
2. Test dostępności pliku graficznego HTTP: `https://panel.hellokostek.pl/images/portret_Leona.webp` oraz `https://hellokostek.pl/images/portret_Leona.webp` (kod 200 OK).
3. Test w przeglądarce na `https://hellokostek.pl/galeria` – brak przeskakiwania obrazu po hydratacji, prawidłowy portret kobiety od początku do końca.
