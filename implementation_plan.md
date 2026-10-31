# Raport 4-Stopniowego Audytu Projektu i Plan Naprawy (Project Audit Master)

Data audytu: **31 października 2026 r.**  
Projekt: **Hello Kostek (Pracownia Artystyczna & Sklep – Maciej Kosteczka)**  
Technologie: **Astro v7 + React Islands v19 + Tailwind CSS v4 | Laravel 13 + Filament CMS v5.6 + SQLite/MySQL**  
Standard audytu: **`project-audit-master` (Protokół 4-stopniowy)**

---

## 1. Podsumowanie Wynikowe Audytu

| Kategoria | Sprawdzonych obszarów | 🔴 Krytyczne | 🟠 Ważne | 🟡 Optymalizacyjne | Wynik bramki |
|---|:---:|:---:|:---:|:---:|:---:|
| **Stopień 1: Bezpieczeństwo, Płatności & Zero-Trust** | 8 | 2 | 2 | 0 | ❌ Wymaga naprawy |
| **Stopień 2: Synchronizacja 5 Warstw (CMS-API-Front)** | 7 | 1 | 5 | 0 | ❌ Wymaga naprawy |
| **Stopień 3: Produkcja, RODO Zero-Leak & SEO** | 6 | 0 | 1 | 4 | ⚠️ Wymaga naprawy |
| **Stopień 4: Twarda Bramka Terminala** | 2 | 5 błędów w testach | - | - | ❌ 5 failed / 135 passed |

---

## 2. Szczegółowy Wykaz Wykrytych Błędów

### 🔴 Błędy Krytyczne (Blokery transakcyjne / ryzyko oversellingu / utraty spójności)

1. **Brak redukcji stanu magazynowego (`stock_quantity`) przy zamówieniach opłacanych kartą/Stripe/P24:**
   - **Plik:** `backend/app/Domain/Commerce/Checkout/CheckoutOrderService.php` (linie 291–304 i 371–382).
   - **Problem:** Warunek `$selectedPaymentMethod === 'bank_transfer'` ograniczał odejmowanie stanu magazynowego (`stock_quantity`) produktów unikatowych oraz wariantów wyłącznie do tradycyjnego przelewu bankowego. Przy płatnościach Stripe lub Przelewy24 stan magazynowy unikatowych oryginałów w ogóle nie był odejmowany w transakcji złożenia zamówienia. Powodowało to wywrotkę testu `Tests\Feature\CommerceVerificationTest` i ryzyko podwójnej sprzedaży unikatowego obrazu 1/1.
   - **Zestawy (Bundles):** W liniach 347–370 dla zestawów (`isBundle()`) brakowało faktycznej dekrementacji stanów magazynowych składników zestawu (`bundleItems`), co wywracało test `ProductBundlesTest > checkout validation and stock reduction for bundles`.

2. **Brak automatycznego restockingu komponentów zestawu przy anulowaniu zamówienia oraz brak obsługi zestawów przy potwierdzeniu wpłaty:**
   - **Plik:** `backend/app/Models/Order.php` (linie 133–185 i 186–250).
   - **Problem:** W zdarzeniu `Order::updated` przy `status === 'cancelled'`, restock odpalał się wyłącznie, gdy w metadanych zamówienia istniała flaga `metadata['stock_deducted']`. Ponieważ zamówienia Stripe nie ustawiały tej flagi w serwisie checkoutu, anulowanie zamówienia nie przywracało stanów magazynowych (`ProductBundlesTest > restocking bundle components on cancellation`). Dodatkowo w bloku obsługi `payment_status === 'paid'` brakowało pętli dekrementującej stan komponentów zestawów.

3. **Stripe Webhook — brak blokady `lockForUpdate()` i weryfikacja pustego secretu:**
   - **Plik:** `backend/app/Http/Controllers/Api/StripePaymentCallbackController.php`.
   - **Problem:** Brak jawnego sprawdzenia `if (empty($webhookSecret)) { abort(500); }` oraz brak blokady pesymistycznej `lockForUpdate()` na modelu zamówienia/transakcji w trakcie potwierdzania płatności, co w warunkach wielokrotnego równoległego webhooka Stripe stwarza ryzyko race condition.

---

### 🟠 Błędy Ważne (Logika biznesowa, synchronizacja CMS, RODO, integracje kurierskie)

4. **Błąd w `Product::syncVariantsFromData` przy deaktywacji pracy oryginalnej w CMS:**
   - **Plik:** `backend/app/Models/Product.php` (linia 559).
   - **Problem:** Gdy użytkownik w panelu Filament CMS odznacza „Dostępność oryginału” (`has_original == false`), kod aktualizował wariant oryginału `-OR` ze stanem `'is_active' => true` zamiast `'is_active' => false`. Skutkowało to tym, że wyłączony oryginał wciąż figurował w API jako aktywny wariant (`ComprehensiveCmsCrudAndSyncTest > products cms crud syncs with catalog...` fail).

5. **Niezgodność adresu URL API ustawień sklepu między frontendem a backendem:**
   - **Pliki:** `src/components/ProductDetail.tsx` (linia 105), `src/components/Success.tsx` (linia 36).
   - **Problem:** Frontend odpytuje endpoint `${apiBase}/store-settings`, podczas gdy w `backend/routes/api.php` zdefiniowana jest trasa `/store/settings`. W rezultacie zapytania w przeglądarce kończyły się błędem 404, uniemożliwiając wczytanie dynamicznej konfiguracji sklepu (np. etykiety rezerwacji przelewu).

6. **Złamanie reguły czyszczenia danych CMS na frontendzie (`if (items.length > 0)`):**
   - **Pliki:** `src/components/Shop.tsx` (linia 64), `src/components/ProductSlider.tsx` (linia 60), `src/components/Gallery.tsx` (linia 73), `src/components/FaqSection.tsx` (linia 58), `src/components/Testimonials.tsx` (linia 142).
   - **Problem:** Sprawdzenie `if (Array.isArray(items) && items.length > 0)` blokuje ustawienie stanu `setProducts([])`, gdy w CMS administrator wyłączy lub usunie wszystkie wpisy. Na stronie głównej i w sklepie pozostawały wówczas stare, statyczne dane testowe.

7. **Naruszenie wytycznych RODO i EROD w banerze cookies:**
   - **Plik:** `src/components/CookieConsent.astro`.
   - **Problem:** Baner posiada wyłącznie jeden przycisk „Zgadzam się”, bez symetrycznego przycisku „Odrzuć opcjonalne” / „Tylko niezbędne”. Zgodnie z prawem UE i wytycznymi UODO/EROD, użytkownik musi mieć możliwość odrzucenia plików cookie o równej wadze wizualnej.

8. **Brak zadania Cron zwalniającego wygasłe rezerwacje przelewowe:**
   - **Plik:** `backend/routes/console.php`.
   - **Problem:** Zamówienia z metodą `bank_transfer` posiadają metadane `reservation_expires_at`, jednak w harmonogramie zadań brakowało komendy Cron zwalniającej nieopłacone rezerwacje po upływie terminu (z przywróceniem stanów magazynowych dzieł).

9. **Krytyczny błąd mapowania adresu kurierskiego w InPost ShipX i Orlen Paczka:**
   - **Pliki:** `backend/app/Domain/Commerce/Logistics/InPostService.php` (linia 196), `backend/app/Domain/Commerce/Logistics/OrlenPaczkaService.php` (linia 180).
   - **Problem:** Metoda `parseAddressFields` poszukiwała adresu wyłącznie w kluczu `line_1` / `line1`. Formularz zamówienia oraz testy przekazują adres w kluczu `street`. Ponieważ `line_1` było puste, serwis wysyłał do API InPost i Orlen Paczka zahardkodowaną ulicę `Przykładowa` z numerem budynku `1`!

10. **Brak ochrony kolumny `metadata` (JSON) w modelu `Product` przed częściowym nadpisaniem:**
    - **Plik:** `backend/app/Models/Product.php`.
    - **Problem:** Edycja formularza w Filament przesyła wybrane klucze `metadata` (np. `year`, `technique`), co grozi wymazaniem innych kluczy (np. `original_available`, metadane systemowe) przy zapisie. Wymagane: `array_replace_recursive` w `saving`.

11. **Brak `withoutTrashed()` w regułach unikalności formularza Filament:**
    - **Plik:** `backend/app/Filament/Resources/Products/Schemas/ProductForm.php` (linie 101, 106).
    - **Problem:** Pola `slug` i `sku` używają standardowego `unique(ignoreRecord: true)` bez `withoutTrashed()`, co uniemożliwia ponowne utworzenie produktu o identycznym slugu po jego usunięciu do kosza (`SoftDeletes`).

12. **Brak podwójnego Honeypota i zaawansowanej walidacji antyspamowej w formularzu kontaktowym:**
    - **Pliki:** `backend/app/Http/Controllers/Api/ContactInquiryController.php`, `src/components/Contact.tsx`.
    - **Problem:** Formularz kontaktowy nie posiadał ukrytego pola honeypot ani ścisłej walidacji `email:rfc,dns` oraz limitów `max` na wiadomości.

---

### 🟡 Błędy Optymalizacyjne (SEO, Omnibus, Apache, testy)

13. **Brak strony błędu `404.astro` w frontendzie Astro:**
    - Ruch na nieistniejące podstrony w trybie statycznym nie posiada dedykowanej strony 404.
14. **Brak tagu `<meta name="robots" content="noindex, nofollow" />` na stronach transakcyjnych:**
    - Pliki `src/pages/sukces-zakup.astro` oraz `src/pages/sukces-kontakt.astro` nie blokowały indeksowania przez roboty Google.
15. **Brak reguł bezpieczeństwa w `public/.htaccess`:**
    - Brak `Options -Indexes` oraz brak blokady odczytu wrażliwych plików `.env`, `.sqlite`, `.log`, `.git`.
16. **Brak prezentacji najniższej ceny z 30 dni (Omnibus) w karcie produktu:**
    - `src/components/ProductDetail.tsx` nie prezentował pola `lowest_price_last_30_days` zwracanego przez backend przy cenach promocyjnych.
17. **Nieaktualna asercja w teście `StoreSettingsTest`:**
    - `backend/tests/Feature/Api/StoreSettingsTest.php`: endpoint wzbogacono o konfigurację rezerwacji bankowej, co wymaga zaktualizowania asercji `assertExactJson`.

---

## 3. Plan Implementacji Napraw

### Faza 1: Backend — Magazyn, Zestawy, Płatności i Modele
1. W `backend/app/Domain/Commerce/Checkout/CheckoutOrderService.php`:
   - Przebudować blok dekrementacji magazynu: stan wariantów, produktów pojedynczych oraz komponentów zestawów (`bundleItems`) jest redukowany przy `placeOrder === true` z blokadą `lockForUpdate()` w transakcji DB, a w `metadata` zamówienia zapisywana jest flaga `'stock_deducted' => true`.
2. W `backend/app/Models/Order.php`:
   - W zdarzeniu `updated` (`cancelled`): zapewnić pełny restock dla wariantów, produktów oraz komponentów zestawów (`bundleItems`).
   - W zdarzeniu `updated` (`payment_status === 'paid'`): dodać obsługę zestawów oraz sprawdzenie, czy stan nie został już odjęty (`stock_deducted`).
3. W `backend/app/Models/Product.php`:
   - W metodzie `syncVariantsFromData`: poprawić linię 559 z `'is_active' => true` na `'is_active' => false` dla wariantu oryginału, gdy `has_original == false`.
   - W metodzie `booted`: dodać listener `saving` chroniący `metadata` za pomocą `array_replace_recursive`.
4. W `backend/app/Http/Controllers/Api/StripePaymentCallbackController.php`:
   - Dodać jawne sprawdzenie `if (empty($webhookSecret)) { abort(500, 'Stripe webhook secret is not configured.'); }`.
   - Dodać blokadę `lockForUpdate()` i sprawdzenie idempotencji przy zmianie statusu na `paid`.
5. W `backend/app/Domain/Commerce/Logistics/InPostService.php` i `OrlenPaczkaService.php`:
   - Zaktualizować `parseAddressFields`, aby prawidłowo obsługiwało klucze `street`, `line_1`, `address_line_1` oraz bezpośrednie `building_number` i `flat_number`.
6. W `backend/app/Filament/Resources/Products/Schemas/ProductForm.php`:
   - Dodać `modifyRuleUsing: fn ($rule) => $rule->withoutTrashed()` na polach `slug` i `sku`.
7. W `backend/routes/console.php`:
   - Utworzyć komendę `app:expire-unpaid-orders` anulującą zamówienia `bank_transfer` po upływie terminu rezerwacji i zarejestrować ją w `Schedule`.
8. W `backend/routes/api.php` oraz `StoreSettingsTest.php`:
   - Dodać alias `Route::get('/store-settings', StoreSettingsController::class)`.
   - Zaktualizować asercję `assertExactJson` w `StoreSettingsTest.php`.

### Faza 2: Frontend — Komponenty, RODO, SEO i Apache
9. W `src/components/Shop.tsx`, `ProductSlider.tsx`, `Gallery.tsx`, `FaqSection.tsx`, `Testimonials.tsx`:
   - Zastąpić warunek `if (Array.isArray(items) && items.length > 0)` czystym `if (Array.isArray(items))`, umożliwiając renderowanie pustego katalogu CMS.
10. W `src/components/ProductDetail.tsx` i `Success.tsx`:
    - Poprawić ścieżkę fetch z `/store-settings` na `/store/settings`.
    - W `ProductDetail.tsx`: dodać obsługę i wyświetlanie najniższej ceny z 30 dni (Omnibus).
11. W `src/components/CookieConsent.astro`:
    - Dodać przycisk „Tylko niezbędne / Odrzuć” o identycznej klasie wizualnej jak przycisk zgody, z zapisem wyboru w `localStorage`.
12. W `src/components/Contact.tsx` oraz `ContactInquiryController.php`:
    - Dodać ukryte pole Honeypot (`website_url`) z detekcją botów i walidacją `email:rfc,dns`.
13. W `src/layouts/Layout.astro` oraz stronach sukcesu:
    - Dodać obsługę propsa `noindex?: boolean` i wstrzykiwanie `<meta name="robots" content="noindex, nofollow" />`.
    - Dodać `noindex={true}` w `sukces-zakup.astro` oraz `sukces-kontakt.astro`.
14. W `src/pages/404.astro`:
    - Utworzyć dedykowaną, estetyczną stronę błędu 404 w stylistyce Hello Kostek z przyciskiem powrotu do sklepu.
15. W `public/.htaccess`:
    - Dodać `Options -Indexes` oraz regułę blokady dostępu do plików `.env`, `.sqlite`, `.log`, `.git`, `composer.json`.

### Faza 3: Twarda Weryfikacja Końcowa (Bramka Stopnia 4)
16. Uruchomienie pełnego zestawu testów backendu: `cd backend; php artisan test`. Cel: **100% testów zdanych (0 failed)**.
17. Uruchomienie kompilacji frontendu: `npm run build`. Cel: **kod wyjścia 0, poprawna generacja wszystkich 23+ podstron i sitemap**.
