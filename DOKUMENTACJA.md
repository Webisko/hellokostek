# Dokumentacja Projektu Hello Kostek (Pracownia Artystyczna & Sklep)

Data ostatniej aktualizacji: **4 listopada 2026 r.**  
Status audytu i testów: **7-Stopniowy Audyt Produkcyjny (project-audit-master): 140/140 passed (782 assertions), npm run build: PASS (23 podstrony, kod wyjścia 0)**

---

## 1. Architektura i Stack Technologiczny

Projekt **Hello Kostek** to hybrydowa platforma e-commerce i wizytówka autorska artysty Macieja Kosteczki, zrealizowana w architekturze **Typ A** (Astro + React Islands + Laravel 13 + Filament CMS v5.6).

* **Frontend:**
  * **Astro v7** (Static Site Generation – SSG, wysokie Core Web Vitals, zero niepotrzebnego JS).
  * **React Islands v19** (komponenty interaktywne: karta dzieła, 1-click modal checkout, mapa Paczkomatów, opinie, FAQ, galeria).
  * **Tailwind CSS v4** (nowoczesne zmienne CSS, fonty lokalne bez Google Fonts CDN).
  * **Leaflet / OpenStreetMap** (interaktywna mapa wyboru Paczkomatów InPost i punktów Orlen Paczka z publicznym API ShipX).
* **Backend:**
  * **Laravel 13** (PHP 8.3+, REST API, transakcje bazodanowe, blokady pesymistyczne `lockForUpdate()`).
  * **FilamentPHP v5.6** (dedykowany panel CMS pod adresem `/admin`, polskie slugi i etykiety).
  * **Baza danych:** MySQL (produkcja SEOHost) / SQLite (lokalnie i w testach jednostkowych).
  * **Strefa czasowa:** `Europe/Warsaw` (`APP_TIMEZONE`).
  * **Kolejki i Harmonogram:** Baza danych (`queue_connection: database`), cron co minutę.

---

## 2. Baza Danych i Modele Eloquent

Struktura bazy danych została zoptymalizowana pod kątem sprzedaży dzieł unikatowych (1/1) oraz reprodukcji:

* `Product` – dzieło sztuki (statusy `is_active`, `manages_stock`, `regular_price_amount`, `vat_rate`, `metadata`).
* `ProductVariant` – warianty dzieła:
  * Oryginał (`-OR`): unikat 1/1, stan magazynowy `stock_quantity = 1`, `manages_stock = true`.
  * Wydruk archiwalny (`-PR`): reprodukcja kolekcjonerska, nielimitowany nakład.
* `ProductBundleItem` – obsługa zestawów dzieł z atomową dekrementacją stanów magazynowych składników.
* `Order` & `OrderItem` – historia zamówień. Klucz obcy `product_id` w tabeli `order_items` posiada regułę `nullOnDelete()`, co zapobiega kaskadowemu usuwaniu pozycji z faktur i historii klienta po usunięciu pracy w CMS.
* `PaymentTransaction` – rejestr transakcji płatniczych (Stripe, Przelewy24, BLIK).
* `Invoice` – faktury VAT generowane lokalnie (PDF) lub synchronizowane z systemami księgowymi.
* `ProductReview` – moderowane opinie klientów zintegrowane ze snapshotem Google Places.
* `ContentPage` – strony statyczne (Regulamin, Polityka Prywatności, O mnie) edytowalne w CMS.
* `StoreSetting` – centralne ustawienia sklepu (dane sprzedawcy, progi rezerwacji przelewów, baner RODO).

---

## 3. Panel CMS FilamentPHP (`/admin`)

* **Adres panelu:** `https://panel.hellokostek.pl/admin` (lokalnie `http://localhost:8000/admin`).
* **Język i nazewnictwo:** 100% polskie slugi (`/admin/produkty`, `/admin/zamowienia`, `/admin/faktury`, `/admin/ustawienia`) oraz polskie etykiety formularzy i tabel.
* **Upload plików:** Skonfigurowano limit `temporary_file_upload.rules` na **50 MB** (`backend/config/livewire.php`), umożliwiając bezpieczne wgrywanie wysokorozdzielczych skanów obrazów i plików PDF.
* **Ochrona danych produkcyjnych (`Zero CMS Overwrite`):** Główny seeder (`HelloKostekSeeder.php`) wykorzystuje metody `firstOrCreate`, gwarantując, że ponowne uruchomienie seederów na serwerze nie nadpisze cen, opisów ani stanów magazynowych zmodyfikowanych przez artystę.

---

## 4. Endpointy REST API i Integracje Zewnętrzne

Główne trasy zdefiniowane w `backend/routes/api.php` z ochroną Rate Limiting:

* `GET /api/catalog` – katalog publicznych, aktywnych produktów.
* `GET /api/catalog/products/{slug}` – szczegóły dzieła, historia cen Omnibus, powiązane warianty.
* `POST /api/coupons/validate` (`throttle:20,1`) – zero-trust walidacja kodów rabatowych wyłącznie po stronie serwera.
* `POST /api/checkout/place` (`throttle:30,1`) – atomowe składanie zamówienia, rezerwacja stanów, walidacja NIP i VIES.
* `GET /api/checkout/orders/{number}` – status zamówienia chroniony anti-IDOR (wymaga e-maila w nagłówku `X-Order-Email` lub sesji zalogowanego użytkownika).
* `GET /api/b2b/gus/{nip}` – automatyczne pobieranie danych kontrahenta z bazy GUS BIR i Białej Listy VAT.
* `POST /api/integrations/stripe/payment-callback` – webhook Stripe z weryfikacją podpisu HMAC, fail-closed na pusty klucz oraz blokadą pesymistyczną `lockForUpdate()`.
* `POST /api/integrations/przelewy24/payment-callback` – webhook Przelewy24 z weryfikacją `hash_equals()`.

---

## 5. Frontend Astro i Wyspy React (React Islands)

* **Optymalizacja Core Web Vitals:** Preload kluczowych grafik LCP (`fetchpriority="high"` dla logo oraz miniatur w Hero), format `.webp`, zerowe przesunięcia układu (CLS = 0).
* **Baner cookies (`CookieConsent.astro`):**
  * Zgodny z wytycznymi EROD i UODO (symetryczne przyciski „Zgadzam się” oraz „Tylko niezbędne”).
  * Pełne wsparcie dla wyboru „Tylko niezbędne” (wartość `essential_only` trwale zapobiega ponownemu otwieraniu banera po odświeżeniu strony).
* **Defensywna integracja CMS:** Komponenty (`Shop.tsx`, `Gallery.tsx`, `FaqSection.tsx`, `Testimonials.tsx`) są odporne na pustą tablicę danych z API (`[]`), nie zawieszają renderowania i nie rzucają wyjątków `TypeError`.

---

## 6. Ścieżka Zakupowa i Płatności

* **Zero-Trust Frontend:** Ceny, rabaty i dostępność są zawsze weryfikowane przez backend. Błąd połączenia sieciowego w koszyku blokuje przycisk zakupu z czytelnym komunikatem (brak ukrytych fallbacków cenowych).
* **Zapobieganie oversellingowi oryginałów 1/1:**
  * W momencie składania zamówienia stan magazynowy oryginału (`-OR`) jest blokowany atomowo przez `lockForUpdate()`.
  * Po potwierdzeniu wpłaty (webhook Stripe/P24) konkurujące nieopłacone sesje na ten sam unikat zostają automatycznie wygaszone i anulowane.
* **Płatności tradycyjne (przelew bankowy):** Zamówienie otrzymuje termin rezerwacji (domyślnie 3 dni robocze). Po upływie tego czasu zadanie cron zwalnia rezerwację i przywraca pracę do sprzedaży.

---

## 7. Logistyka i Dostawa

* **Paczkomaty InPost (Leaflet / OSM):**
  * Bezpłatna integracja z publicznym API ShipX (`https://api-shipx-pl.easypack24.net/v1/points`).
  * Funkcja `formatCityName()` koryguje wielkość liter w nazwach miast.
  * Dane wybranego punktu (`id`, `name`, `address`, `postal_code`, `city`) są trwale przekazywane do backendu.
* **Przesyłki kurierskie (InPost / Orlen / Kurier ubezpieczony):**
  * Parser adresowy `parseAddressFields` w `InPostService.php` automatycznie rozdziela ulicę, numer budynku i numer lokalu (np. `ul. Marszałkowska 102/4` -> `street: ul. Marszałkowska`, `building: 102`, `flat: 4`).
  * Generowanie etykiet przewozowych z panelu administracyjnego (`/admin/orders/{number}/inpost-label`).

---

## 8. Prawodawstwo PL/UE, RODO Cookieless, Omnibus i Bezpieczeństwo

* **Wymogi e-commerce:**
  * Przycisk finalizacji: „Zamawiam i płacę” (płatności elektroniczne) oraz „Zamawiam z obowiązkiem zapłaty” (przelew tradycyjny).
  * Dyrektywa Omnibus: automatyczna prezentacja najniższej ceny z 30 dni przed obniżką na karcie produktu.
  * Checkboxy zgód (Regulamin, Polityka Prywatności, zgoda na treści cyfrowe) są domyślnie odznaczone.
  * Stopka zawiera pełne dane rejestrowe: `hellokostek Maciej Kosteczka`, NIP: `625-236-36-56`, Siewierz / Łódź.
* **Czystość polszczyzny i mikrotypografia:**
  * Bezwzględny brak angielskiego Title Case (wyłącznie polskie Sentence case we wszystkich nagłówkach i przyciskach).
  * Całkowity zakaz klasy CSS `capitalize` na tekstach w języku polskim.
* **Bezpieczeństwo serwera (`.htaccess`):**
  * Zablokowany dostęp do plików `.env`, `.sqlite`, `.git`, `composer.json`.
  * Nagłówki HTTP: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`.

---

## 9. Zadania Harmonogramu (Cron) i Testy Automatyczne

W pliku `backend/routes/console.php` zdefiniowano zadania cron (wszystkie z flagą `->withoutOverlapping()`):

1. `app:expire-unpaid-orders` (co godzinę) – anuluje przeterminowane rezerwacje przelewowe i przywraca stan magazynowy dzieł.
2. `app:recover-abandoned-carts` (co godzinę) – wysyłka powiadomień o niedokończonych koszykach.
3. `app:cleanup-abandoned-carts` (codziennie) – czyszczenie starych szkiców zamówień.
4. `app:cleanup-price-history` (codziennie) – rotacja historii cen Omnibus starszych niż 90 dni.
5. `app:generate-sitemap` (codziennie o 03:30) – generowanie pliku `sitemap.xml`.
6. `app:aggregate-analytics-daily` (codziennie o 02:15) – agregacja danych analitycznych.
7. `queue:work --stop-when-empty` (co minutę) – bezpieczna obsługa kolejki maili i integracji na hostingu współdzielonym.

### Twarda bramka testowa
* `php artisan test`: **140 testów zakończonych sukcesem (782 asercje)**, 0 błędów.
* `npm run build`: kod wyjścia **0**, 23 strony wygenerowane w 2.5 s.

---

## 10. Konfiguracja Środowiska i Wdrożenie Produkcyjne

* Dane dostępowe serwera produkcyjnego (SEOHost / LH.pl, SSH, baza danych) znajdują się w pliku `credentials.local.md` (wykluczonym z Git).
* Środowisko produkcyjne wymaga:
  * `APP_ENV=production`
  * `APP_DEBUG=false`
  * `APP_TIMEZONE=Europe/Warsaw`
  * `APP_LOCALE=pl`
  * `ALLOWED_ORIGINS=https://hellokostek.pl`
  * `FRONTEND_URL=https://hellokostek.pl`

---

## 11. Dziennik Zmian (Changelog)

### 4 listopada 2026 r. – Pełny 7-Stopniowy Audyt Produkcyjny (project-audit-master)
* **Weryfikacja statusu zamówienia w `Success.tsx`:** Dodano automatyczne odpytywanie endpointu `GET /api/checkout/orders/{number}?email=...` oraz polling dla płatności elektronicznych. Wyeliminowano prezentowanie fałszywego sukcesu w ciemno w przypadku przerwanych lub oczekujących transakcji Przelewy24 / Stripe.
* **Ergonomia mobilna (iOS Safari):** Zmieniono klasy pól `<input>` oraz `<textarea>` z `text-sm` na `text-base` w formularzach kontaktowych i wyceny (`Home.tsx`, `Contact.tsx`), eliminując niechciany wymuszony Auto-Zoom widoku na iPhone.
* **Izolacja efektów hover na ekranach dotykowych (`global.css`):** Zamknięto reguły `.button:not(:disabled):hover` oraz animacje bąbli `.button__blobs div` w zapytaniu `@media (hover: hover) and (pointer: fine)`, zapobiegając zjawisku przyklejonego zielonego podświetlenia po dotknięciu ekranu smartfona.
* **Pełna symetria RODO (Zero Dark Patterns):** Wyrównano wagę wizualną przycisków „Tylko niezbędne” oraz „Zgadzam się” w `CookieConsent.astro` zgodnie z wytycznymi UODO/EROD.
* **Higiena bazy danych i seederów:** Usunięto deweloperskie konto `admin@genericshop.local` z pliku `HelloKostekSeeder.php`.
* **Stan twardej bramki testowej:** `php artisan test`: 140/140 passed (782 assertions), `npm run build`: kod wyjścia 0 (23 strony).
