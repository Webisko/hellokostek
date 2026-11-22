# Dokumentacja projektu Hello Kostek (Pracownia artystyczna i sklep)

Data ostatniej aktualizacji: **22 listopada 2026 r.**  
Status audytu i testów: **Pełny audyt produkcyjny i wdrożenie poprawek (`project-audit-master`): `php artisan test` 149/149 passed (840 assertions), `composer audit` 0 podatności, `npm audit --omit=dev` 0 podatności, `npm run build` PASS (23 strony, kod wyjścia 0), `legacy-scan` 0 uwag**

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
  * **FilamentPHP v5.12** (dedykowany panel CMS pod adresem `/admin`, polskie slugi i etykiety, alerty niezapisanych zmian `unsavedChangesAlerts()`).
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
* `Order` & `OrderItem` – historia zamówień. Tabela `orders` posiada kryptograficzny `public_token` (`varchar(64)`, indeks unikalny) generowany automatycznie przy tworzeniu rekordu. Klucz obcy `product_id` w tabeli `order_items` posiada regułę `nullOnDelete()`, co zapobiega kaskadowemu usuwaniu pozycji z faktur i historii klienta po usunięciu pracy w CMS. Modele z polem JSON `metadata` (`Order`, `StoreSetting`, `ContentPage`, `Coupon`, `GalleryArtwork`) zabezpieczono hookiem `saving` wykonującym płytkie scalanie (`array_replace`).
* `User` – role (`role`, `is_admin`) są wykluczone z `$fillable` i nadawane wyłącznie przez `forceFill()`. Weryfikacja personelu opiera się o `User::isStaff()`.
* `PaymentTransaction` – rejestr transakcji płatniczych (Stripe, Przelewy24, BLIK).
* `Invoice` – faktury VAT generowane lokalnie (PDF) lub synchronizowane z systemami księgowymi.
* `ProductReview` – moderowane opinie klientów zintegrowane ze snapshotem Google Places.
* `ContentPage` – strony statyczne (Regulamin, Polityka Prywatności, O mnie) edytowalne w CMS.
* `StoreSetting` – centralne ustawienia sklepu (dane sprzedawcy, progi rezerwacji przelewów, baner RODO).

---

## 3. Panel CMS FilamentPHP (`/admin`)

* **Adres panelu:** `https://panel.hellokostek.pl/admin` (lokalnie `http://localhost:8000/admin`).
* **Język i nazewnictwo:** 100% polskie slugi (`/admin/produkty`, `/admin/zamowienia`, `/admin/faktury`, `/admin/ustawienia`, `/admin/moj-profil`, `/admin/status-systemu`) oraz polskie końcówki tras (`/utworz`, `/edycja`).
* **Profil użytkownika i zmiana hasła (`/admin/moj-profil`):** Strona profilu (`App\Filament\Profile\MyProfilePage`) z wyraźną etykietą **„Mój profil i hasło”** w menu użytkownika oraz w menu bocznym (grupa *Ustawienia*). Komponent `CustomUpdatePassword` udostępnia podgląd haseł (`revealable()`), obok ustawień danych osobowych i weryfikacji dwuetapowej (2FA).
* **Ustawienia sklepu (`/admin/ustawienia`):** Bezpośredni adres `/admin/ustawienia` (bez `/1/edit`) uporządkowany w zakładkach `Wygląd` (równy 3-kolumnowy układ wgrywania logo, faviconu i tła logowania, wyrównany pasek ogłoszeń), `E-commerce` (dane sklepu, waluta PLN zablokowana przed przypadkową zmianą, dane sprzedawcy/faktur VAT, zwijane odzyskiwanie koszyków), `Płatności i dostawa` (próg darmowej dostawy i metody wysyłki na górze, zwijane sekcje Stripe/Przelewy24/InPost/ORLEN Paczka ze statusem w opisie), `SEO` i `System i poczta` (zwijana sekcja zaawansowanych ustawień technicznych). Normalizacja pustych pól sekretnych w `EditStoreSetting` eliminuje fałszywe komunikaty „Masz niezapisane zmiany” przy wyjściu bez edycji, a w nagłówku pozostawiono wyłącznie przycisk **Zapisz**.
* **Operacje i ergonomia pracy:**
  * **Promocje wariantów i Omnibus (`ProductForm`, `ProductsTable`):** Pola `print_sale_price` i `original_sale_price` bezpośrednio w sekcjach obu wariantów (`-PR` oraz `-OR`), zsynchronizowane z `Product::syncVariantsFromData()`, historią cen `ProductPriceHistory` oraz prezentacją przekreślonych cen w tabeli produktów.
  * **Kokpit, badge i Status systemu:** `StoreDashboard` filtruje „Dzisiejszy przychód” oraz „Zamówienia do wysyłki” wyłącznie do zamówień opłaconych (`payment_status = 'paid'`), udostępnia pasek szybkich skrótów (`Dodaj obraz do sklepu`, `Dodaj do galerii`, `Zobacz stronę sklepu`), spójne statusy płatności i klikalne głębokie linki `?record=` oraz `?tab=`. W górnym pasku panelu znajduje się link **„Otwórz sklep”**. Menu boczne wyświetla liczniki spraw przy zasobach `Zamówienia` (opłacone do wysyłki), `Zapytania` (`status = 'new'`) oraz `Opinie` (oczekujące na publikację). `OperationsHealth` używa dynamicznych adresów `Resource::getUrl()`, eliminując błędy 404.
  * **Zamówienia, zapytania i szybkie akcje:** `ListOrders` i `ListContactInquiries` posiadają zakładki statusów z licznikami (`Do wysyłki`, `Oczekują na płatność`, `Nowe`, `W trakcie` itd.). W podglądzie zamówienia (`OrderInfolist` / `OrdersTable`) dostępne są akcje 1-klik **„Oznacz jako opłacone”** i **„Oznacz jako wysłane”**, czytelna kolumna **„Wariant”** (`Oryginał` / `Wydruk kolekcjonerski`), klikalne linki `mailto:`/`tel:` oraz ukrycie technicznych logów integracji przed rolą `Manager`. W zapytaniach (`ContactInquiryForm` / `ContactInquiriesTable`) dodano szybkie akcje `Odpowiedz e-mailem` i polskie etykiety pól briefu.
  * **Tabele produktów, galerii, stron i opinii:** `ProductsTable` ukrywa przed Menedżerem kolumny techniczne (`slug`, `SKU`, `Typ`, `Atrybuty`), powiększa miniatury (`56px`), dodaje przełączniki 1-klik (`ToggleColumn` dla `is_active` i `is_visible`) oraz link **„Zobacz w sklepie”**. `ProductReviewsTable` używa `ToggleColumn` dla publikacji opinii. `ContentPagesTable` i `ContentPagePolicy` chronią strony systemowe (`regulamin`, `polityka-prywatnosci`, `o-mnie`) przed przypadkowym usunięciem przez Menedżera. `EditUser` i `CreateUser` inicjalizują puste pole `password` jako `''`, eliminując fałszywe komunikaty „Masz niezapisane zmiany”.
* **Upload plików:** Skonfigurowano limit `temporary_file_upload.rules` na **50 MB** (`backend/config/livewire.php`), umożliwiając bezpieczne wgrywanie wysokorozdzielczych skanów obrazów i plików PDF.
* **Kontrola dostępu i formularze CMS:** Kontrolery panelowe (`InPostLabelController`, `OrlenPaczkaLabelController`, `SidebarController`, `AdminOrderExportController`, `AdminContactExportController`) wymuszają `auth()->user()?->isStaff()`. Reguły `unique` w zasobach z soft delete ignorują usunięte rekordy (`withoutTrashed()`), a panel ostrzega przed opuszczeniem formularza z rzeczywistymi niezapisanymi zmianami.
* **Ochrona danych produkcyjnych (`Zero CMS Overwrite`):** Główny seeder (`HelloKostekSeeder.php`) wykorzystuje metody `firstOrCreate` bez operacji `delete()` ani nadpisywania ustawień email, a brakujące hasło konta startowego generuje losowo przez `Str::password(20)`.

---

## 4. Endpointy REST API i Integracje Zewnętrzne

Główne trasy zdefiniowane w `backend/routes/api.php` z ochroną Rate Limiting:

* `GET /api/catalog` – katalog publicznych, aktywnych produktów (zoptymalizowany bez zapytań N+1 oraz z poprawnymi adresami `/sklep/{slug}`).
* `GET /api/catalog/products/{slug}` – szczegóły dzieła, historia cen Omnibus liczona odrębnie per wariant (`lowest_price_before_discount_last_30_days` dla `-OR` i `-PR` niezależnie), powiązane warianty.
* `POST /api/coupons/validate` (`throttle:20,1`) – zero-trust walidacja kodów rabatowych wyłącznie po stronie serwera.
* `POST /api/inquiries` (`throttle:20,1`) – przyjmowanie zapytań z walidacją `email:rfc,dns`, filtrowaniem załączników (maks. 5 plików, MIME `jpg/jpeg/png/webp/pdf`, limit 10 MB), sanitizacją nagłówków `\r\n` w mailach oraz cichą pułapką antyspamową (`hp_field`, `website_url`, `_form_started_at < 1000 ms`).
* `POST /api/checkout/place` (`throttle:30,1`) – atomowe składanie zamówienia wewnątrz `DB::transaction()` (włącznie z `Coupon::lockForUpdate()`), rezerwacją stanów, wyliczaniem `customer_segment` wyłącznie z zalogowanego użytkownika (ignorując wartość od klienta) oraz walidacją NIP / VIES.
* `GET /api/checkout/orders/{number}` – status zamówienia chroniony anti-IDOR (wymaga kryptograficznego `public_token` w nagłówku `X-Order-Token` / parametrze `token`, ewentualnie e-maila `X-Order-Email` dla zachowania wstecznej kompatybilności, lub sesji właściciela/personelu).
* `POST /api/returns` – zwroty RMA z blokadą IDOR (`order_id` dostępny wyłącznie dla własnych zamówień użytkownika z `user_id === $user->id`).
* `GET /api/b2b/gus/{nip}` – automatyczne pobieranie danych kontrahenta z bazy GUS BIR i Białej Listy VAT.
* `POST /api/integrations/stripe/payment-callback` – webhook Stripe z weryfikacją podpisu HMAC, fail-closed na pusty klucz oraz blokadą pesymistyczną `lockForUpdate()`.
* `POST /api/integrations/przelewy24/payment-callback` – webhook Przelewy24 z weryfikacją `hash_equals()` oraz idempotentnością pod `lockForUpdate()`.

---

## 5. Frontend Astro i Wyspy React (React Islands)

* **Optymalizacja Core Web Vitals i brak błędów hydratacji:** Preload kluczowych grafik LCP (`fetchpriority="high"` dla logo oraz miniatur w Hero), format `.webp`, zerowe przesunięcia układu (CLS = 0). Skrypt korekty sierot typograficznych w `Layout.astro` pomija węzły wewnątrz `<astro-island>`, eliminując błędy hydratacji React `#418`.
* **Bezpieczeństwo treści CMS (XSS):** Dynamiczny HTML pobierany z API CMS (`AboutMe.tsx`, `FaqSection.tsx`, `PrivacyPolicy.tsx`, `Terms.tsx`) jest sanityzowany przez `DOMPurify` (`src/utils/sanitizeHtml.ts`).
* **SEO i dane strukturalne:** Wstrzyknięto JSON-LD `FAQPage` na stronie głównej oraz `Product` + `Offer` + `BreadcrumbList` (`@graph`) na kartach `/sklep/{id}`. Sitemapa (`astro.config.mjs`) i `public/robots.txt` wykluczają strony sukcesu `/sukces-*` oraz zezwalają na indeksację przez `OAI-SearchBot` i `Claude-SearchBot`.
* **Baner cookies (`CookieConsent.astro`):**
  * Zgodny z wytycznymi EROD i UODO (symetryczne przyciski „Zgadzam się” oraz „Tylko niezbędne”).
  * Pełne wsparcie dla wyboru „Tylko niezbędne” (wartość `essential_only` trwale zapobiega ponownemu otwieraniu banera po odświeżeniu strony).
* **Defensywna integracja CMS i dostępność:** Komponenty (`Shop.tsx`, `Gallery.tsx`, `FaqSection.tsx`, `Testimonials.tsx`) są odporne na pustą tablicę danych z API (`[]`), a zamknięte szuflady i akordeony korzystają z atrybutu `inert`.

---

## 6. Ścieżka Zakupowa i Płatności

* **Zero-Trust Frontend i obsługa faktury VAT:** Ceny, rabaty i próg darmowej wysyłki (`free_shipping_threshold` — edytowalny w CMS w `/admin/ustawienia` → zakładka `Płatności i dostawa` → `Próg darmowej dostawy i metody wysyłki` w zł; wartość `0` wyłącza próg) są pobierane z API i weryfikowane przez backend (`PricingEngine`). Błąd pobrania aktualnych danych produktu w `ProductDetail.tsx` blokuje przycisk zakupu z komunikatem i przyciskiem ponowienia próby. Formularz zamówienia obsługuje opcjonalne dane do faktury VAT (firma, walidacja sumy kontrolnej NIP modulo-11, adres rozliczeniowy dla dostaw do punktu) oraz walidację inline bez wyłączonych przycisków „martwego kliknięcia”.
* **Weryfikacja statusu płatności (`Success.tsx`):** Strona podsumowania autoryzuje się przez `token` (`X-Order-Token`), rozróżnia statusy `awaiting_payment` i `pending`, wykonuje do 5 prób pollingu i nigdy nie wyświetla fałszywego potwierdzenia przed statusem `paid`.
* **Zapobieganie oversellingowi oryginałów 1/1:**
  * W momencie składania zamówienia stan magazynowy oryginału (`-OR`) jest blokowany atomowo przez `lockForUpdate()`.
  * Nieopłacone zamówienia `stripe` oraz `przelewy24` otrzymują 45-minutową rezerwację (`reservation_expires_at`); po wygaśnięciu zadanie cron wygasza sesje Stripe `cs_*` i przywraca dostępność dzieła.
  * Po potwierdzeniu wpłaty (webhook Stripe/P24) konkurujące nieopłacone sesje na ten sam unikat zostają automatycznie wygaszone i anulowane.
* **Płatności tradycyjne (przelew bankowy):** Zamówienie otrzymuje termin rezerwacji (domyślnie 3 dni robocze). Po upływie tego czasu zadanie cron zwalnia rezerwację i przywraca pracę do sprzedaży.

---

## 7. Logistyka i Dostawa

* **Paczkomaty InPost (Leaflet / OSM):**
  * Bezpłatna integracja z publicznym API ShipX (`https://api-shipx-pl.easypack24.net/v1/points`) z 350 ms debounce przy przesuwaniu mapy i escapowaniem nazw punktów w markerach `L.divIcon`.
  * Funkcja `formatCityName()` koryguje wielkość liter w nazwach miast.
  * Dane wybranego punktu (`id`, `name`, `address`, `postal_code`, `city`) są trwale przekazywane do backendu.
* **Przesyłki kurierskie (InPost / Orlen / Kurier ubezpieczony):**
  * Parser adresowy `parseAddressFields` w `InPostService.php` automatycznie rozdziela ulicę, numer budynku i numer lokalu bez podstawiania fikcyjnych wartości awaryjnych (brak wymaganych danych kończy się wyjątkiem `\InvalidArgumentException`).
  * Generowanie etykiet przewozowych z panelu administracyjnego (`/admin/orders/{number}/inpost-label`).

---

## 8. Prawodawstwo PL/UE, RODO Cookieless, Omnibus i Bezpieczeństwo

* **Wymogi e-commerce:**
  * Przycisk finalizacji: „Zamawiam i płacę” (płatności elektroniczne) oraz „Zamawiam z obowiązkiem zapłaty” (przelew tradycyjny).
  * Dyrektywa Omnibus: automatyczna prezentacja najniższej ceny z 30 dni przed obniżką na karcie produktu.
  * Checkboxy zgód (Regulamin, Polityka prywatności, zgoda na treści cyfrowe) są domyślnie odznaczone.
  * Stopka zawiera pełne dane rejestrowe: `hellokostek Maciej Kosteczka`, `Rynek 33, 42-470 Siewierz`, NIP: `625-236-36-56` oraz odnośnik `Zwroty i odstąpienie` (`/regulamin#zwroty-i-odstapienie`).
* **Czystość polszczyzny i mikrotypografia:**
  * Bezwzględny brak angielskiego Title Case (wyłącznie polskie Sentence case we wszystkich nagłówkach i przyciskach).
  * Całkowity zakaz klasy CSS `capitalize` na tekstach w języku polskim.
* **Bezpieczeństwo serwera (`.htaccess`):**
  * Zablokowany dostęp do plików `.env`, `.sqlite`, `.git`, `composer.json`.
  * Nagłówki HTTP: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: geolocation=(), microphone=(), camera=()` oraz `Strict-Transport-Security` dla połączeń HTTPS.

---

## 9. Zadania Harmonogramu (Cron) i Testy Automatyczne

W pliku `backend/routes/console.php` zdefiniowano zadania cron (wszystkie z flagą `->withoutOverlapping()`):

1. `app:expire-unpaid-orders` (co godzinę) – anuluje przeterminowane rezerwacje przelewowe oraz nieopłacone zamówienia `stripe`/`przelewy24`, wygasza sesje Stripe i przywraca stan magazynowy dzieł.
2. `app:recover-abandoned-carts` (co godzinę) – wysyłka powiadomień o niedokończonych koszykach.
3. `app:cleanup-abandoned-carts` (codziennie) – czyszczenie starych szkiców zamówień.
4. `app:cleanup-price-history` (codziennie) – rotacja historii cen Omnibus starszych niż 90 dni.
5. `app:generate-sitemap` (codziennie o 03:30) – generowanie pliku `sitemap.xml`.
6. `app:aggregate-analytics-daily` (codziennie o 02:15) – agregacja danych analitycznych.
7. `media:convert-webp` (co godzinę) – konwersja wgranych mediów do WebP poza wątkiem HTTP.
8. `queue:work --stop-when-empty --max-time=50` (co minutę) – bezpieczna obsługa kolejki maili i integracji na hostingu współdzielonym.

### Twarda bramka testowa
* `php artisan test`: **166 testów zakończonych sukcesem (969 asercji)**, 0 błędów (w tym zestawy regresyjne `AuditSecurityRegressionTest` oraz `CmsAuditFixesTest`).
* `composer audit --no-interaction`: **0 podatności**.
* `npm audit --omit=dev`: **0 podatności**.
* `npm run build`: kod wyjścia **0**, 23 strony wygenerowane w 4.56 s.
* `npx modern-web-guidance@latest legacy-scan src`: **0 uwag** (38 przeanalizowanych przewodników).

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

## 11. Dziennik zmian (Changelog)

### 22 listopada 2026 r. – Naprawa alertów „niezapisane zmiany” i pakiet optymalizacji UX/UI CMS (etap 2)
* **Naprawa fałszywych alertów „Masz niezapisane zmiany” ([EditUser.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Users/Pages/EditUser.php), [CreateUser.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Users/Pages/CreateUser.php)):** Zainicjalizowano pole `password` jako `''` w `mutateFormDataBeforeFill()` / `afterFill()`, eliminując rozbieżność hashy MD5 Livewire po wejściu i wyjściu („Anuluj” / zmiana zakładki) bez dokonywania edycji. Zweryfikowano w przeglądarce wszystkie strony edycji/tworzenia (`/admin/uzytkownicy/{id}/edycja`, `/admin/uzytkownicy/utworz`, `/admin/ustawienia`, `/admin/zamowienia/utworz`, `/admin/multimedia/utworz`, `/admin/multimedia/{id}/edycja`, `/admin/moj-profil`).
* **Pakiet 1 — Kokpit i szybki podgląd sklepu:** Dodano przycisk **„Otwórz sklep”** w górnym pasku panelu (`topbar-theme-switcher.blade.php` + `StoreSettings::storefrontUrl()`), pasek szybkich akcji w Kokpicie (`Dodaj obraz do sklepu`, `Dodaj do galerii`, `Zobacz stronę sklepu`), ujednolicono statusy płatności w „Ostatnich zamówieniach” (`OrderResource::displayStatus*`) oraz naprawiono głębokie linki `?record=` i `?action=create` (`ListProductReviews`, `ListContactInquiries`, `ListProducts`, `ManageGalleryArtworks`, `ListOrders`).
* **Pakiet 2 — Produkty i Galeria:** Odchudzono `ProductsTable` dla Menedżera (ukryto `slug`, `SKU`, `Typ`, `Atrybuty` oraz ukryto `slug` w `ProductForm` z bezpiecznym fallbackiem generowania przy tworzeniu), zamieniono ikony statusu na `ToggleColumn`, powiększono miniatury (`56px`) i dodano akcję „Zobacz w sklepie” oraz filtr kategorii w `GalleryArtworkTable`.
* **Pakiet 3 — Zamówienia:** Dodano 4 zakładki statusów z licznikami (`ListOrders`), ukryto w `OrderInfolist` przed Menedżerem sekcje techniczne (logi integracji, historię admina, transakcje, akcje realizacji, segment klienta), zastąpiono `SKU`/`Typ` kolumną **„Wariant”**, dodano klikalne akcje `mailto:`/`tel:` oraz przyciski 1-klik **„Oznacz jako opłacone”** i **„Oznacz jako wysłane”** w `OrdersTable`.
* **Pakiet 4 — Zapytania o portrety:** Dodano 5 zakładek statusów (`ListContactInquiries`), akcje **„Odpowiedz e-mailem”** w tabeli/modalu/polu e-mail oraz `tel:`, polskie etykiety parametrów briefu i etykietę „Notatki wewnętrzne”, a sekcję `Metadane połączenia` ukryto przed Menedżerem.
* **Pakiet 5 — Strony i Opinie:** Zabezpieczono strony systemowe (`regulamin`, `polityka-prywatnosci`, `o-mnie`) w `ContentPagePolicy` i `ContentPagesTable` przed przypadkowym usunięciem przez Menedżera, ukryto `slug`/`Szablon`, dodano „Podgląd na stronie”. W `ProductReviewsTable` wprowadzono przełącznik 1-klik `ToggleColumn` (przez `forceFill`), filtr widoczności oraz licznik nieopublikowanych opinii w menu (`ProductReviewResource`).
* **Weryfikacja:** `php artisan test`: **166 passed (969 assertions)**, budowa Vite `theme-DIIzxxHA.css`, testy w przeglądarce (`127.0.0.1:8017`) jako Administrator i Menedżer.
* **Wyśrodkowanie zwiniętego paska bocznego, branding, ikona Galerii, ukrycie Multimediów i porządek w menu ([CustomLogin.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Pages/Auth/CustomLogin.php), [GalleryArtworkResource.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/GalleryArtworkResource.php), [MediaResource.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/MediaResource/MediaResource.php), [MediaPolicy.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Policies/MediaPolicy.php), [StoreSettings.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Support/StoreSettings.php), [theme.css](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/resources/css/filament/admin/theme.css), [custom-styles.blade.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/resources/views/filament/components/custom-styles.blade.php)):** Naprawiono asymetryczne wyrównanie ikon w trybie `fi-sidebar-collapsed`, podpięto `syncSidebarCollapsedClass()` pod `livewire:navigated` dla nawigacji SPA, usunięto nagłówek `<h1>Zaloguj się</h1>` z karty logowania i powiększono logo logowania (`3.5rem` / `1.625rem`), ustawiono kwadratowe logo z ramką i monogramem HK (`admin-logo.png`, `256×256 PNG` z przezroczystym tłem) jako logo panelu, wygenerowano czytelny favicon **HK** (`favicon.ico`, `favicon-32x32.png`, `favicon-192x192.png`, `apple-touch-icon.png`, `admin-favicon.png`) dla strony i panelu, zmieniono ikonę zakładki **Galeria** na `Heroicon::OutlinedSquares2x2`, dostęp do **Multimediów** (`/admin/multimedia`) ograniczono do Administratora, a menu boczne przeorganizowano na logiczny układ **Przegląd (`Kokpit`, `Analityka`) → `Sprzedaż` → `Sklep` → `Strona i galeria` → `Ustawienia`** (na samym dole).
* **Proporcjonalny podgląd głównego obrazu i siatka miniatur w zakładce „Zdjęcia produktu” ([ProductForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Products/Schemas/ProductForm.php), [GalleryArtworkForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/Schemas/GalleryArtworkForm.php), [theme.css](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/resources/css/filament/admin/theme.css)):** Przeprojektowano zakładkę **Zdjęcia produktu** na układ 2-kolumnowy (`5/12` + `7/12` na desktopie): `featured_image_path` oraz `image_path` w Galerii otrzymały klasę `.fi-artwork-featured-upload` (`max-width: 420px`, `imagePreviewHeight('320')`, neutralne tło `--shop-surface-soft` bez czarnych pasów bocznych i spłaszczonego banera), natomiast `gallery_image_paths` ustawiono w prawej kolumnie jako siatkę kwadratowych miniatur (`panelLayout('grid')`, `appendFiles()`, `imagePreviewHeight('180')`, `.fi-artwork-gallery-upload`) z obsługą zmiany kolejności metodą przeciągnij i upuść. Zweryfikowano testem `CmsAuditFixesTest` (17/17) i w przeglądarce (`127.0.0.1:8017`, podgląd główny `441×320 px`, 3-kolumnowe kafelki `193×193 px`).
* **Naprawa przycinanych list rozwijanych `Select` oraz spójny pojedynczy wybór kategorii ([theme.css](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/resources/css/filament/admin/theme.css), [ProductForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Products/Schemas/ProductForm.php), [GalleryArtworkForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/Schemas/GalleryArtworkForm.php), [ComprehensiveCmsCrudAndSyncTest.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/tests/Feature/ComprehensiveCmsCrudAndSyncTest.php)):** Zmieniono `.fi-panel-admin .fi-input-wrp` z `overflow: hidden !important` na `overflow: visible !important` (usuwając przycinanie panelu `.fi-dropdown-panel` i błędne pozycjonowanie Floating UI w całym CMS) oraz wyłączono zbędne zaokrąglenia/cienie na wewnętrznym polu `.fi-select-input-search-ctn .fi-input`. W `ProductForm` usunięto `->multiple()` i `->searchable()` na relacji `categories` (zastępując je przez `->preload()->native(false)`), a w `GalleryArtworkForm` zamieniono `->searchable()` na `->preload()->native(false)` dla jednolitego pojedynczego wyboru w obu formularzach. Zweryfikowano testami `ComprehensiveCmsCrudAndSyncTest` + `CmsAuditFixesTest` (20 passed, 270 assertions) oraz w przeglądarce (`127.0.0.1:8017`, panele `533×157 px` w Galerii i `526.5×157 px` w Produktach z pełną listą `Olej`, `Akryl`, `Akwarela`, `Rysunek`).
* **Słowniki technik i formatów prac, przycisk `+` oraz dynamiczne kategorie na froncie ([StoreSettings.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Support/StoreSettings.php), [StoreSettingForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/StoreSettings/Schemas/StoreSettingForm.php), [EditStoreSetting.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/StoreSettings/Pages/EditStoreSetting.php), [ProductCategory.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Models/ProductCategory.php), [ProductCategoryForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/ProductCategories/Schemas/ProductCategoryForm.php), [ProductCategoriesTable.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/ProductCategories/Tables/ProductCategoriesTable.php), [ProductForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Products/Schemas/ProductForm.php), [GalleryArtworkForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/Schemas/GalleryArtworkForm.php), [Shop.tsx](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Shop.tsx), [ProductDetail.tsx](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/ProductDetail.tsx), [sklep/[id].astro](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/pages/sklep/%5Bid%5D.astro)):** Zamieniono pola tekstowe `<datalist>` dla **Techniki wykonania** i **Formatu** na natywne dla panelu listy `Select` zasilane centralnie przez `StoreSettings` (`artworkTechniques()`, `artworkFormats()`), dodano szybkie dodawanie (`+`, `createOptionForm`) dla kategorii, technik i formatów bezpośrednio w formularzach Produktu i Galerii oraz nową zakładkę **Słowniki prac** w Ustawieniach (z pełną edycją, usuwaniem i zmianą kolejności dla Menedżera). Dodano automatyczne generowanie unikalnego sluga w `ProductCategory::creating()` / `createQuickByName()` i uproszczono widok Kategorii dla Menedżera (ukryto `slug`, `SEO` i `Atrybuty kategorii`), a w `Shop.tsx`, `ProductDetail.tsx` i `sklep/[id].astro` usunięto sztywne ograniczenie kategorii do `akwarela`/`rysunek`, udostępniając dynamiczne filtry i etykiety dla dowolnych kategorii z bazy. Zweryfikowano pełnym pakietem `php artisan test` (**167 passed, 992 assertions**), budową Astro (`npm run build`, 23 strony) i kontrolą wizualną w przeglądarce (`127.0.0.1:8017`).
* **Dwukolumnowy układ edycji dzieła w Galerii ([GalleryArtworkForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/Schemas/GalleryArtworkForm.php), [GalleryArtworkTable.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/Tables/GalleryArtworkTable.php), [ManageGalleryArtworks.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/Pages/ManageGalleryArtworks.php), [theme.css](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/resources/css/filament/admin/theme.css)):** Rozszerzono modal `EditAction` i `CreateAction` Galerii do `modalWidth('7xl')` oraz podzielono formularz na dwie wyrównane wysokościowo kolumny (`h-full`): po lewej **Szczegóły pracy** (`7/12`) i po prawej **Publikacja i zdjęcie** (`5/12`) z przełącznikiem `Widoczne w galerii` umieszczonym nad proporcjonalnym podglądem `Zdjęcie pracy` (`imagePreviewHeight('400')`, wymuszone `max-width: 420px !important`), co wyrównało wysokość obu kart (~619 px vs ~607 px) bez przewijania na desktopie. Zweryfikowano testami `CmsAuditFixesTest` + `ComprehensiveCmsCrudAndSyncTest` (21 passed, 287 assertions) i kontrolą wizualną (`127.0.0.1:8017`).
* **Zintegrowana zakładka „Dane produktu” w układzie dwukolumnowym ([ProductForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Products/Schemas/ProductForm.php), [theme.css](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/resources/css/filament/admin/theme.css)):** Połączono zakładki *Podstawowe dane* oraz *Zdjęcia produktu* w jedną zakładkę **Dane produktu** (z zachowaniem osobnej zakładki **SEO**, dokąd przeniesiono `Tekst alternatywny głównego obrazu (alt)`). W górnej części ułożono dwie wyrównane wysokościowo kolumny: po lewej **Szczegóły pracy** (`7/12`, z przełącznikami publikacji `Aktywny` / `Widoczny w sklepie` / `Możliwy do zakupu` u dołu), a po prawej **Zdjęcia pracy** (`5/12`: `Główny obraz pracy` oraz poniżej siatka `Dodatkowe zdjęcia pracy` `130 px` w natywnej siatce 3-kolumnowej FilePond); pod nimi pełną szerokość zajmują sekcje **Warianty i ceny w sklepie** oraz zwijane **Zaawansowane ustawienia księgowe** (Admin).
* **Przełącznik wariantu w nagłówku sekcji oraz porządki w podpowiedziach `hintIcon` ([ProductForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Products/Schemas/ProductForm.php), [GalleryArtworkForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/Schemas/GalleryArtworkForm.php), [StoreSettingForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/StoreSettings/Schemas/StoreSettingForm.php), [CouponForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Coupons/Schemas/CouponForm.php), [theme.css](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/resources/css/filament/admin/theme.css)):** Przeniesiono przełącznik `has_original` (**Dostępny w sprzedaży**) do prawej części nagłówka sekcji `Wariant 2: praca oryginalna` (`afterHeader`), przeniesiono instruktażowe `helperText` do spójnej ikony `?` (`hintIcon(..., tooltip: ...)`) w `ProductForm`, `GalleryArtworkForm`, `StoreSettingForm` i `CouponForm`, usunięto zbędne podpisy o walucie PLN (z zachowaniem krytycznych ostrzeżeń technicznych i wskazówki zmiany hasła w `UserForm`) oraz dodano reguły CSS w `theme.css` dla jednoliniowych etykiet z subtelną ikoną `?` i wyrównania `.fi-section-header-after-ctn`. Zweryfikowano testami (21 passed, 287 assertions) i w przeglądarce (`127.0.0.1:8017`: `0 px` różnicy wysokości nagłówków, kart i pól cen obu wariantów; pełny formularz mieści się nad linią zgięcia `1440×900`).
* **Lista wyboru emoji w formularzu opinii ([ProductReviewForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/ProductReviews/Schemas/ProductReviewForm.php)):** Zastąpiono zwykłe pole tekstowe `emoji` natywną rozwijaną listą `Select` z 20 gotowymi emoji z polskimi podpisami (obejmującą wszystkie ikony obecne w bazie) oraz przyciskiem **`+`** (`createOptionForm`) do jednorazowego wpisania własnej ikony (z fallbackiem zachowującym nietypowe wartości z bazy). Zweryfikowano testami (21 passed, 287 assertions) i w przeglądarce (`127.0.0.1:8017/admin/opinie-o-produktach`).

### 22 listopada 2026 r. – Rozdzielenie uprawnień Administrator / Menedżer, ochrona haseł i poprawka ramki FilePond
* **Podział ról RBAC (Administrator vs Menedżer):**
  * Konto `admin@webisko.pl` zachowuje pełną rolę **Administrator** (`UserRole::Admin`), natomiast konto klienta `admin@hellokostek.pl` przełączono na rolę **Menedżer** (`UserRole::Manager`, `is_admin = false` — zarówno lokalnie, jak i w `HelloKostekSeeder` dla istniejącego rekordu nie podnosi już roli ponownie).
  * **Ochrona konta Administratora ([UserPolicy.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Policies/UserPolicy.php), [EditUser.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Users/Pages/EditUser.php), [UsersTable.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Users/Tables/UsersTable.php)):** Menedżer widzi konta Administratorów na liście użytkowników, ale nie ma akcji edycji ani usunięcia i dostaje `403 Forbidden` przy próbie bezpośredniej edycji, usunięcia lub nadania roli `Administrator`.
  * **Uproszczony CMS i Zero-Trust w Ustawieniach ([StoreSettingForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/StoreSettings/Schemas/StoreSettingForm.php), [EditStoreSetting.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/StoreSettings/Pages/EditStoreSetting.php)):** Dla Menedżera ukryto zakładkę `SEO`, sekcję brandingu panelu, wszystkie integracje API (Stripe, P24, InPost, ORLEN), techniczne pola metod dostawy (`Kod techniczny metody`, `COD`, `punkt odbioru`, `Strefy wysyłki`), `Zaawansowane ustawienia techniczne`, `Status systemu`, przycisk „Układ” (`SidebarController` → tylko `Admin`) oraz pola techniczne w `ProductForm`, `ContentPageForm` i `CouponForm`. Pozostawiono mu wszystkie funkcje biznesowe (w tym Pasek ogłoszeń, Próg darmowej dostawy + nazwy/ceny wysyłek, Dane sprzedawcy/faktur, Odzyskiwanie porzuconych koszyków i Tryb konserwacji), a `EditStoreSetting::mutateFormDataBeforeSave()` twardo zabezpiecza klucze administratorskie po stronie serwera.
* **Eliminacja monitu przeglądarki o zapis hasła i ramka FilePond:** Na polach sekretów/haseł dodano `autocomplete="new-password"` / `"current-password"` oraz atrybuty ignorujące menedżery haseł (`data-1p-ignore`, `data-lpignore`, `data-bwignore`). W `theme.css` i `StoreSettingForm.php` (`imagePreviewHeight('148')`) usunięto podwójne dolne obramowanie pod podglądem logo i faviconu.
* **Weryfikacja:** `php artisan test`: **161 passed (938 assertions)**, budowa Vite `theme-BxUiQ_9y.css`, testy w przeglądarce (`127.0.0.1:8017`) oddzielnie jako Administrator i jako Menedżer.

### 22 listopada 2026 r. – Naprawa podglądów FilePond w CSP, przycisku „Zapisz” i powiadomień toast
* **Podglądy obrazów w CMS (CSP):** Rozszerzono politykę `Content-Security-Policy` ([config/app.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/config/app.php), [AddSecurityHeaders.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Middleware/AddSecurityHeaders.php)) o obsługę `blob:` w `img-src` / `connect-src` oraz `worker-src 'self' blob:`, dzięki czemu FilePond wyświetla podglądy logotypów i zdjęć na białym tle bez blokad CSP.
* **Działanie przycisku „Zapisz”, ikony i toasty:** Na 6 stronach edycji z przyciskiem akcji w nagłówku (`EditStoreSetting`, `EditContentPage`, `EditCoupon`, `EditCustomer`, `EditOrder`, `EditProductCategory`) podpięto jawne wywołanie `->submit(null)->action(fn () => $this->save())`, w `EditStoreSetting` dodano komunikat „Ustawienia zostały zapisane” i czyszczenie cache `StoreSettings::purgeCache()`, w `theme.css` usunięto błędne `fill: currentColor` z ikon przycisków primary (przywracając cienki ptaszek `✓`) oraz ustawiono `z-index: 9999` dla powiadomień `.fi-no`.
* **Weryfikacja:** `php artisan test`: **157 passed (898 assertions)**, Vite build `theme-BB2YeT6_.css`, test kliknięcia **Zapisz** i podglądów FilePond potwierdzony w przeglądarce (`127.0.0.1:8017`).

### 22 listopada 2026 r. – Ujednolicenie zapisu `hellokostek` oraz branding panelu CMS (monogram HK)
* **Spójny zapis nazwy `hellokostek`:** Zastąpiono dawne wystąpienia `Hello Kostek` zapisem `hellokostek` w konfiguracji (`backend/config/shop.php`), transporcie mailowym (`NativePhpMailTransport.php`), nagłówku maila zapytań (`new-inquiry-admin.blade.php`), seederze (`HelloKostekSeeder.php`) oraz w produkcyjnych metadanych SEO (`seo_title`, `seo_description`, `excerpt`), ustawieniach i profilu administratora (bez ingerencji w ceny, stany i treści produktów).
* **Logo i favicon panelu CMS:** Dodano komponent lockupu [brand-logo.blade.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/resources/views/filament/components/brand-logo.blade.php) wyświetlający monogram HK (`admin-logo.png` identyczny z faviconem strony głównej) obok napisu `hellokostek CMS`, z obsługą trybu ciemnego (`.dark .fi-brand-logo-img { filter: invert(1); }`) i zwiniętego paska bocznego (`html.fi-sidebar-collapsed`). Podpięto także `admin-favicon.png` z fallbackiem na `favicon.ico`.
* **Weryfikacja:** `php artisan test`: **156 passed (890 assertions)**, budowa Vite `theme-QVHoSuyC.css`, weryfikacja w przeglądarce (`127.0.0.1:8017`).

### 22 listopada 2026 r. – Kompleksowe poprawki UX, wizualne i funkcjonalne panelu Filament CMS
* **Ustawienia sklepu (`/admin/ustawienia`) i arkusz stylów `theme.css`:**
  * Naprawiono zepsute style FilePond w `theme.css` (usunięto `inline-flex` sklejający napis „lubwybierzpliki” oraz `height: 100% !important` zgniatający pole „Tło ekranu logowania”), ustawiając równą wysokość `148px` dla 3 kolumn uploadu w zakładce **Wygląd** oraz zaktualizowano pasek ogłoszeń (placeholder `200 zł`).
  * Wyeliminowano fałszywy komunikat „Masz niezapisane zmiany” przy opuszczaniu ustawień bez edycji poprzez inicjalizację 8 sekretnych pól hasłowych jako `''` zamiast `null` w `EditStoreSetting::mutateFormDataBeforeFill()` (z zachowaniem alertu przy realnej edycji), a przycisk „Anuluj” zastąpiono linkiem „Wróć do kokpitu”.
  * Przeniesiono próg darmowej dostawy i metody/strefy wysyłki na górę zakładki **Płatności i dostawa**, zwinięto sekcje Stripe/Przelewy24/InPost/ORLEN Paczka ze statusem konfiguracji w opisie, zablokowano przypadkową edycję waluty `PLN` w `E-commerce` i `CouponForm`, usunięto nieużywane `exchange_rates` oraz przeniesiono techniczne/ryzykowne pola w `System i poczta` i `SEO` do zwiniętych sekcji zaawansowanych.
* **Profil użytkownika i zmiana hasła (`/admin/moj-profil`):** Utworzono `MyProfilePage` i `CustomUpdatePassword` (`revealable()`), dodano widoczną pozycję „Mój profil i hasło” w menu użytkownika i w nawigacji bocznej (`Analityka & system`), dodano podgląd hasła w `UserForm` oraz naprawiono rozmiar ikon 2FA w `theme.css`.
* **Promocje wariantów, Kokpit, Status systemu i modale:** Dodano pola `print_sale_price` i `original_sale_price` w `ProductForm` z synchronizacją wariantów `-PR`/`-OR`, historią cen Omnibus i widokiem w `ProductsTable`; dodano warunek `payment_status = 'paid'` w statystykach `StoreDashboard`; dodano liczniki badge w `OrderResource` i `ContactInquiryResource`; dodano wybór wariantu (`product_variant_id`) w `OrderForm`; naprawiono 4 martwe linki (404) w `OperationsHealth` poprzez `Resource::getUrl()`; zawężono style nagłówkowych akcji modali w `theme.css` wyłącznie do slide-overów (przywrócenie przycisku „Anuluj” w oknach potwierdzenia).
* **Weryfikacja:** Przebudowa Vite backendu (`npm run build`), `php artisan test`: **155/155 passed (883 assertions)** oraz pełna weryfikacja w przeglądarce (`127.0.0.1:8017`).

### 22 listopada 2026 r. – Pełny audyt produkcyjny i wdrożenie poprawek lokalnych (`project-audit-master`)
* **Aktualizacja zależności i audyt CVE:** Zaktualizowano pakiety backendu (`laravel/framework`, `livewire/livewire`, `filament/filament`, `symfony/*`) oraz frontendu (`dompurify`, poprawki `npm audit fix`). Zarówno `composer audit`, jak i `npm audit --omit=dev` wykazują **0 podatności**.
* **Utwardzenie autoryzacji i anty-IDOR:** Wykluczono `role` i `is_admin` z `$fillable` w `User.php`, dodano `isStaff()` oraz bramki w kontrolerach panelowych (`InPostLabelController`, `OrlenPaczkaLabelController`, `SidebarController`, eksporty). Wyeliminowano IDOR w `OrderReturnController` oraz zaufanie do `customer_segment` po stronie klienta; wprowadzono kryptograficzny `public_token` w tabeli `orders` (`X-Order-Token`) i bezpieczne przekierowanie z `ProductDetail.tsx` do `Success.tsx`.
* **Antyspam, walidacja uploadu i nagłówków e-mail:** W `ContactInquiryController` dodano `email:rfc,dns`, walidację MIME/rozmiaru plików, filtrację `\r\n` w `ContactInquiryAdminMail` oraz pułapkę `hp_field`/`_form_started_at`; podpięto podwójny honeypot w `Contact.tsx` i `Home.tsx`.
* **Odporność na wyścigi i wygaszanie sesji:** Przeniesiono walidację i `Coupon::lockForUpdate()` do `DB::transaction()` w `CheckoutOrderService`, dodano `lockForUpdate()` w `PaymentCallbackService` oraz 45-minutową rezerwację i cronowe wygaszanie sesji `stripe`/`przelewy24` w `routes/console.php`.
* **Jakość UI, React Hydration #418, UX mobilny i VAT:** Zablokowano ingerencję `fixTypographyOrphans()` w `<astro-island>` (`Layout.astro`), dodano sanityzację `DOMPurify`, wdrożono dynamiczny próg darmowej wysyłki z `/api/store/settings`, blokadę checkoutu przy błędzie API z przyciskiem odświeżenia, opcjonalną fakturę VAT z walidacją NIP oraz walidację inline z `role="alert"` zamiast natywnych `alert()` i zablokowanych przycisków.
* **SEO, dane strukturalne i prawodawstwo PL/UE:** Dodano JSON-LD `FAQPage` (`index.astro`) i `Product` + `Offer` + `BreadcrumbList` (`sklep/[id].astro`), wykluczono `/sukces-*` z sitemapy i `robots.txt`, uzupełniono adres `Rynek 33, 42-470 Siewierz` oraz link do zwrotów w `Footer.astro`, dodano `Permissions-Policy` i `Strict-Transport-Security` w `.htaccess`, poprawiono mikrotypografię na polskie Sentence case oraz kontrast WCAG.
* **Weryfikacja:** `php artisan migrate --force` + `php artisan test`: **147/147 passed (823 assertions)**, `npm run build`: **23 strony**, `legacy-scan`: **0 uwag**, lokalny audyt w przeglądarce (`1440×900`, `768×1024`, `375×812`): **0 błędów React `#418`**, **0 przepełnień poziomych**.

### 4 listopada 2026 r. – Pełny 7-Stopniowy Audyt Produkcyjny (project-audit-master)
* **Weryfikacja statusu zamówienia w `Success.tsx`:** Dodano automatyczne odpytywanie endpointu `GET /api/checkout/orders/{number}?email=...` oraz polling dla płatności elektronicznych. Wyeliminowano prezentowanie fałszywego sukcesu w ciemno w przypadku przerwanych lub oczekujących transakcji Przelewy24 / Stripe.
* **Ergonomia mobilna (iOS Safari):** Zmieniono klasy pól `<input>` oraz `<textarea>` z `text-sm` na `text-base` w formularzach kontaktowych i wyceny (`Home.tsx`, `Contact.tsx`), eliminując niechciany wymuszony Auto-Zoom widoku na iPhone.
* **Izolacja efektów hover na ekranach dotykowych (`global.css`):** Zamknięto reguły `.button:not(:disabled):hover` oraz animacje bąbli `.button__blobs div` w zapytaniu `@media (hover: hover) and (pointer: fine)`, zapobiegając zjawisku przyklejonego zielonego podświetlenia po dotknięciu ekranu smartfona.
* **Pełna symetria RODO (Zero Dark Patterns):** Wyrównano wagę wizualną przycisków „Tylko niezbędne” oraz „Zgadzam się” w `CookieConsent.astro` zgodnie z wytycznymi UODO/EROD.
* **Higiena bazy danych i seederów:** Usunięto deweloperskie konto `admin@genericshop.local` z pliku `HelloKostekSeeder.php`.
* **Stan twardej bramki testowej:** `php artisan test`: 140/140 passed (782 assertions), `npm run build`: kod wyjścia 0 (23 strony).
