# Backend & Panel CMS – Hello Kostek

Zaplecze systemowe (Backend REST API & Filament CMS) przygotowane specjalnie dla autorskiej pracowni malarskiej **Hello Kostek (Maciej Kosteczka)**.

System odpowiada za kompleksowe zarządzanie katalogiem obrazów i rysunków, realizację zamówień, obsługę płatności online i logistyki kurierskiej, przyjmowanie zapytań o portrety na zamówienie ze zdjęciem, moderację opinii, edycję podstron prawnych z podziałem na bloki paragrafowe, bazę FAQ oraz zgodność prawną e-commerce (dyrektywa Omnibus, dyrektywa zwrotów 2023/2673, RODO, EAA, GPSR).

Całość działa w **100% w języku polskim** (zarówno panel CMS, jak i komunikaty API oraz powiadomienia transakcyjne).

---

## 🛠️ Stack Technologiczny Backendu

- **Framework**: Laravel 13 (PHP 8.3+)
- **Architektura Domenowa**: 9 wyspecjalizowanych modułów w katalogu `app/Domain/`
- **Panel Administracyjny**: Filament CMS v5.6 + Filament Breezy v3.2
- **Autentykacja**: Laravel Sanctum (tokeny API Bearer dla klientów & sesje panelu CMS dla administratorów)
- **Baza Danych**: SQLite (`database/database.sqlite` lokalnie) / MySQL MariaDB 11.4 (środowisko produkcyjne)
- **Księgowość & PDF**: Barryvdh Laravel DomPDF v3.1 (wbudowane generowanie faktur PDF) + sterowniki do platform Fakturownia, iFirma, inFakt, wFirma
- **Płatności**: Stripe PHP SDK v21, Przelewy24 API, obsługa BLIK z kodem 6-cyfrowym
- **Logistyka**: InPost ShipX (Paczkomaty 24/7 i kurier ubezpieczony), Orlen Paczka, webhook BaseLinker
- **Weryfikacja B2B**: Wyszukiwarka REGON/NIP w rejestrze BIR Głównego Urzędu Statystycznego (GUS)
- **Opinie**: Synchronizacja z Google Places API (`GooglePlaceReviewsService.php`)
- **Generator Grafik Social Media**: Dynamiczne, podpisane obrazy OpenGraph (`GET /og-image`)
- **Kolejki i Bezpieczeństwo**: Laravel Queue, Spatie Laravel Backup v10, Laravel Reverb

---

## 🏛️ Architektura Domenowa (`app/Domain/`)

Logika biznesowa została zorganizowana w modularne domeny domenowe:
1. **Admin**: Personalizacja panelu CMS, sortowanie i konfiguracja pulpitów.
2. **Analytics**: Zbieranie i agregacja zdarzeń frontendu, statystyki odsłon i konwersji.
3. **Commerce**: Logika koszyka, zamówień, wyliczania cen Omnibus, kalkulatora portretów, fakturowania i procedury zwrotów RMA.
4. **Communication**: Szablony maili transakcyjnych HTML, wysyłka powiadomień i logowanie korespondencji.
5. **Customers**: Profile klientów B2C i firm B2B, integracja z GUS BIR, książki adresowe.
6. **Imports**: Narzędzia importu i migracji produktów oraz zasobów mediów.
7. **Integrations**: Sterowniki bramek płatności (Stripe, Przelewy24) oraz operatorów logistycznych (InPost, Orlen Paczka, BaseLinker).
8. **Operations**: Audyt działań administratorów, reguły przekierowań URL 301/302, automatyczne kopie zapasowe.
9. **Storefront**: Serwisy prezentacji oferty, FAQ z danymi Schema.org, opinie Google Places i generator sitemap.

---

## 🎛️ Moduły Panelu CMS Filament (25 Zasobów)

Wszystkie zasoby znajdują się w katalogu `app/Filament/Resources/` i zostały podzielone na 4 grupy:

### 1. Sprzedaż i Magazyn
* **Dzieła Sztuki i Produkty (`ProductResource`)**:
  - Obsługa wariantów: Oryginał fizyczny (`-OR`) z unikatowym stanem magazynowym 1 szt. vs Kolekcjonerska reprodukcja (`-PR`).
  - Zestawy dzieł (Bundles) z automatycznym wyliczaniem dostępności komponentów.
  - Historia cen Omnibus z automatyczną rejestracją najniższej ceny z ostatnich 30 dni.
  - Ceny indywidualne oraz kalkulacje stawek B2B.
* **Kategorie Prac (`ProductCategoryResource`)**:
  - Główne techniki malarskie: *Olej*, *Akryl*, *Akwarela*, *Rysunek*.
* **Atrybuty Malarskie (`ProductAttributeResource`)**:
  - Wymiary, formaty, rodzaj podobrazia (płótno, papier bawełniany), oprawa.
* **Zamówienia (`OrderResource`)**:
  - Zarządzanie statusem realizacji, adresem dostawy, kodem Paczkomatu i historią transakcji.
  - Akcje bezpośredniego pobierania etykiet kurierskich InPost i Orlen Paczka.
* **Faktury (`InvoiceResource`)**:
  - Generowanie faktur PDF dla klientów (z poziomu wbudowanego silnika lub integracji zewnętrznych).
* **Kody Rabatowe (`CouponResource`)**:
  - Definiowanie kuponów kwotowych i procentowych, daty ważności, minimalnej wartości koszyka oraz limitów użyć.
* **Porzucone Koszyki (`AbandonedCartResource`)**:
  - Podgląd nieukończonych transakcji z historią prób kontaktu i statusem odzyskania.
* **Zapisy na Dostępność (`BackInStockSubscriptionResource`)**:
  - Rejestr adresów e-mail klientów oczekujących na wznowienie nakładu reprodukcji.
* **Zwroty i Odstąpienia (`OrderReturnResource`)**:
  - Obsługa procedury RMA zgodnej z dyrektywą 2023/2673 – walidacja pozycji, ilości i terminów zwrotu.

### 2. Klienci i Komunikacja
* **Klienci (`CustomerResource`)**:
  - Kartoteki klientów indywidualnych oraz firmowych (wraz z danymi NIP pobranymi z GUS).
* **Zapytania o Wycenę Portretów (`ContactInquiryResource`)**:
  - Rejestr zapytań z formularza ze strony głównej i podstrony `/kontakt`.
  - Przechowywanie w kolumnie JSON `payload` wytycznych artystycznych (format, rodzaj płótna: prostokątne lub owalne) oraz załączonych zdjęć referencyjnych.
* **Opinie i Recenzje (`ProductReviewResource`)**:
  - Moderacja recenzji klientów wyświetlanych na stronie głównej oraz kartach dzieł.
* **Szablony E-mail (`EmailTemplateResource`)**:
  - Konfiguracja treści powiadomień transakcyjnych (potwierdzenie zamówienia, zmiana statusu, odzyskanie koszyka).
* **Dziennik E-maili (`TransactionalEmailLogResource`)**:
  - Pełna historia i podgląd wysłanych wiadomości mailowych do kupujących.

### 3. Treść i Portfolio
* **Galeria Prac (`GalleryArtworkResource`)**:
  - Zarządzanie dziełami prezentowanymi w portfolio na podstronie `/galeria`.
  - Przypisywanie technik, roku powstania (2026, 2024, 2023, 2022, starsze) oraz relacji do sklepu.
* **Edytor Stron Treści (`ContentPageResource`)**:
  - Zarządzanie podstronami prawnymi (*Regulamin Sklepu*, *Polityka Prywatności i Cookies*).
  - Edytor bloków paragrafowych (`Repeater::make('metadata.sections')`) z unikalnymi identyfikatorami kotwic (`id`), tytułami sekcji i edytorem WYSIWYG (`RichEditor`).
  - Automatyczne wyliczanie daty modyfikacji (`last_updated_formatted`).
* **Sekcja FAQ (`FaqItemResource`)**:
  - Baza pytań i odpowiedzi z automatycznym generowaniem mikrodanych Schema.org `FAQPage`.
* **Biblioteka Mediów (`MediaResource`)**:
  - Centralny menedżer plików graficznych z podglądem i automatyczną kompresją do formatu WebP.

### 4. Ustawienia, Prawne i Techniczne
* **Ustawienia Sklepu (`StoreSettingResource`)**:
  - Globalna konfiguracja: dane rejestrowe firmy, stawki podatkowe, progi darmowej dostawy, kody marketingowe (GA4, GTM, Meta Pixel), tryb prac serwisowych.
* **Rejestr Zgód Cookies (`CookieConsentResource`)**:
  - Logowanie decyzji użytkowników z banera cookies zgodnie z wymogami dowodowymi RODO i PKE.
* **Reguły Przekierowań (`RedirectRuleResource`)**:
  - Konfiguracja przekierowań 301 i 302 dla starych adresów URL.
* **Audyt Aktywności (`AdminActivityLogResource`)**:
  - Rejestr operacji tworzenia, edycji i usuwania zasobów przez administratorów panelu.
* **Logi Integracji (`IntegrationLogResource`)**:
  - Dziennik komunikacji z zewnętrznymi API (Przelewy24, Stripe, InPost, GUS).
* **Błędy Zadań (`FailedJobResource`)**:
  - Podgląd i ponawianie nieudanych zadań w kolejkach asynchronicznych.
* **Użytkownicy Panelu (`UserResource`)**:
  - Konta administratorów, zarządzanie hasłami i profilami (Filament Breezy).

---

## 🌐 Trasy Webowe (`routes/web.php`)

Poza punktami końcowymi REST API, system udostępnia kluczowe trasy HTTP:
* `GET /` – automatyczne przekierowanie do panelu administracyjnego (`filament.admin.home`).
* `GET /sitemap.xml` – dynamiczna mapa witryny w formacie XML uwzględniająca produkty, galerie i strony CMS.
* `GET /robots.txt` – dynamiczny plik reguł dla robotów indeksujących i agentów AI.
* `GET /og-image` – podpisany cyfrowo generator dynamicznych miniatur OpenGraph dla mediów społecznościowych.
* `GET /admin/orders/{number}/inpost-label` – bezpośrednie pobranie wygenerowanej etykiety wysyłkowej InPost w formacie PDF.
* `GET /admin/orders/{number}/orlen-label` – bezpośrednie pobranie etykiety wysyłkowej Orlen Paczka w formacie PDF.
* `GET /admin/exports/customers` – eksport bazy klientów do celów analitycznych (CSV).
* `GET /admin/exports/orders` – eksport zestawienia zamówień do celów księgowych (CSV/Excel).

---

## 🚚 Logistyka i Księgowość

### 1. InPost Paczkomaty & Orlen Paczka
Pobieranie etykiet PDF bezpośrednio z poziomu panelu zamówienia:
- `GET /admin/orders/{number}/inpost-label`
- `GET /admin/orders/{number}/orlen-label`

### 2. Sterowniki Fakturowania (Accounting Drivers)
W katalogu `app/Domain/Commerce/Accounting/Drivers/` zaimplementowano 5 wymiennych sterowników:
1. `BuiltInInvoiceDriver` – wbudowany generator faktur PDF oparty o silnik DomPDF (brak zewnętrznych kosztów).
2. `FakturowniaDriver` – integracja z API Fakturownia.pl.
3. `IFirmaDriver` – integracja z API iFirma.
4. `InFaktDriver` – integracja z API inFakt.
5. `WFirmaDriver` – integracja z API wFirma.

Faktury są generowane asynchronicznie przez zadanie kolejkowe `SendOrderToAccountingJob`.

---

## ⏰ Harmonogram i Komendy Konsolowe (Artisan)

W projekcie przygotowano dedykowane komendy operacyjne i serwisowe:

```bash
# 1. Automatyczne odzyskiwanie porzuconych koszyków
# Wysyła spersonalizowane wiadomości e-mail do klientów, którzy przerwali proces zakupu
php artisan app:recover-abandoned-carts

# 2. Czyszczenie starych szkiców porzuconych koszyków
php artisan app:cleanup-abandoned-carts --days=30

# 3. Zarządzanie użytkownikami administratora Filament CMS
# Tworzy nowe konto lub promuje istniejące konto do roli administratora
php artisan app:make-admin-user admin@hellokostek.pl --name="Konstanty Kostek" --password="TajneHaslo123!" --promote-existing

# 4. Generowanie mapy witryny sitemap.xml
php artisan app:generate-sitemap

# 5. Czyszczenie archiwalnej historii cen dyrektywy Omnibus (starszej niż 90 dni)
php artisan app:cleanup-price-history

# 6. Agregacja dziennych metryk analityki first-party do lekkich raportów
php artisan app:aggregate-analytics-daily

# 7. Import katalogu dzieł sztuki i konfiguracji z plików JSON
php artisan app:import-shop-json

# 8. Import snapshotu 5-gwiazdkowych opinii Google do ustawień sklepu
php artisan app:import-google-reviews-snapshot

# 9. Masowa konwersja obrazów do formatu WebP
# Skanuje dysk publiczny i optymalizuje zdjęcia dzieł malarskich
php artisan media:convert-webp

# 10. Skanowanie i synchronizacja bazy mediów
php artisan media:scan
```

---

## 🔑 Bezpieczeństwo i Dostęp do Panelu

- **Adres panelu**: `http://localhost:8000/admin`
- Szczegółowe dane kont deweloperskich oraz parametry środowiska produkcyjnego znajdują się w lokalnym pliku **`credentials.local.md`** (wykluczonym ze śledzenia w Git).
- Wszystkie hasła i klucze API produkcyjne muszą być konfigurowane wyłącznie za pośrednictwem zmiennych środowiskowych w pliku `.env`.

---

## 💻 Podstawowe Komendy Deweloperskie

```bash
# Instalacja zależności
composer install

# Uruchomienie migracji i zasilenie autorskimi danymi Hello Kostek
php artisan migrate:fresh --seed --class=HelloKostekSeeder --force

# Start serwera lokalnego
php artisan serve --port=8000

# Uruchomienie testów automatycznych
php artisan test
```

---

## 📚 Powiązana Dokumentacja

* [API_REFERENCE.md](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/API_REFERENCE.md) – pełna specyfikacja techniczna wszystkich endpointów REST API.
* [raport-wymagan.md](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/raport-wymagan.md) – raport zgodności prawnej e-commerce 2026 (Omnibus, EAA, dyrektywa zwrotów 2023/2673, DSA).
* [wymagania-cookies.md](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/wymagania-cookies.md) – wymogi techniczne i prawne dla banera cookies (Consent Mode v2, prior blocking, rejestracja zgód).
