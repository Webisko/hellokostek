# Backend & Panel CMS – Hello Kostek

Zaplecze systemowe (Backend REST API & Filament CMS) przygotowane specjalnie dla autorskiej pracowni malarskiej **Hello Kostek (Maciej Kosteczka)**.

System odpowiada za kompleksowe zarządzanie katalogiem obrazów i rysunków, realizację zamówień, obsługę płatności online i logistyki kurierskiej, przyjmowanie zapytań o portrety na zamówienie ze zdjęciem, moderację opinii, edycję podstron prawnych z podziałem na paragrafy, bazę FAQ oraz zgodność prawną e-commerce (dyrektywa Omnibus, dyrektywa zwrotów 2023/2673, RODO, EAA).

Całość działa w **100% w języku polskim** (zarówno panel CMS, jak i komunikaty API).

---

## 🛠️ Stack Technologiczny Backendu

- **Framework**: Laravel 13 (PHP 8.3+)
- **Panel Administracyjny**: Filament CMS v5.6 + Filament Breezy
- **Autentykacja**: Laravel Sanctum (tokeny API Bearer dla klientów & sesje panelu CMS dla administratorów)
- **Baza Danych**: SQLite (`database/database.sqlite` lokalnie) / MySQL (produkcja)
- **Księgowość & PDF**: Barryvdh Laravel DomPDF (wbudowane generowanie faktur PDF) + sterowniki do platform Fakturownia, iFirma, inFakt, wFirma
- **Płatności**: Stripe PHP SDK, Przelewy24 API, obsługa BLIK
- **Logistyka**: InPost ShipX (Paczkomaty 24/7 i kurier), Orlen Paczka, webhook BaseLinker
- **Weryfikacja B2B**: Wyszukiwarka REGON/NIP w rejestrze BIR Głównego Urzędu Statystycznego
- **Opinie**: Synchronizacja z Google Places API
- **Kolejki i Bezpieczeństwo**: Laravel Queue, Spatie Laravel Backup, Laravel Reverb

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
  - Przypisywanie technik, roku powstania (2024, 2023, 2022, starsze) oraz relacji do sklepu.
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

## 🚚 Logistyka i Księgowość

### 1. InPost Paczkomaty & Orlen Paczka
- Kontrolery w `routes/web.php` umożliwiają natychmiastowe pobranie etykiety PDF z poziomu widoku zamówienia w CMS:
  - `GET /admin/orders/{number}/inpost-label`
  - `GET /admin/orders/{number}/orlen-label`

### 2. Sterowniki Fakturowania (Accounting Drivers)
W katalogu `app/Domain/Commerce/Accounting/Drivers/` zaimplementowano 5 wymiennych sterowników:
1. `BuiltInInvoiceDriver` – wbudowany generator faktur PDF oparty o silnik DomPDF (brak zewnętrznych kosztów).
2. `FakturowniaDriver` – integracja z API Fakturownia.pl.
3. `IFirmaDriver` – integracja z API iFirma.
4. `InFaktDriver` – integracja z API inFakt.
5. `WFirmaDriver` – integracja z API wFirma.

Faktury są generowane asynchronicznie przez job `SendOrderToAccountingJob`.

### 3. Eksport Danych
- `GET /admin/exports/customers` – eksport bazy klientów do celów analitycznych.
- `GET /admin/exports/orders` – eksport zamówień do zestawień księgowych.

---

## ⏰ Harmonogram i Komendy Konsolowe (Artisan)

W katalogu `app/Console/Commands/` przygotowano dedykowane komendy:

```bash
# 1. Automatyczne odzyskiwanie porzuconych koszyków
# Wysyła spersonalizowane wiadomości e-mail do klientów, którzy przerwali proces zakupu
php artisan commerce:recover-abandoned-carts

# 2. Czyszczenie starych sesji koszyków
php artisan commerce:cleanup-abandoned-carts

# 3. Masowa konwersja obrazów do formatu WebP
# Skanuje dysk publiczny i optymalizuje zdjęcia dzieł malarskich
php artisan media:convert-webp

# 4. Skanowanie i synchronizacja bazy mediów
php artisan media:scan
```

---

## 🔑 Domyślne Konto Administratora

- **Adres panelu**: `http://localhost:8000/admin`
- **E-mail**: `admin@hellokostek.pl`
- **Hasło**: `Admin1234!`

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
