# Backend & Panel CMS – Hello Kostek

Zaplecze systemowe (Backend REST API & Filament CMS) przygotowane specjalnie dla pracowni artystycznej **Hello Kostek (Maciej Kosteczka)**.

System odpowiada za zarządzanie katalogiem obrazów i rysunków, przyjmowanie zapytań o portrety na zamówienie, moderację opinii klienckich, edycję podstron prawnych z podziałem na paragrafy oraz zarządzanie sekcją FAQ.

---

## 🛠️ Stack Technologiczny

- **Framework Backendowy**: Laravel 13 (PHP 8.3+)
- **Panel Administracyjny**: Filament CMS v5
- **Autentykacja**: Laravel Sanctum (tokeny API & sesje panelu)
- **Baza Danych**: SQLite (`database/database.sqlite` lokalnie) / MySQL (produkcja)
- **Płatności & Integracje**: Przelewy24, Stripe, InPost ShipX, Google Reviews API
- **Główny Seeder Danych**: `HelloKostekSeeder.php`

---

## 📋 Kluczowe Moduły Panelu CMS Filament

Panel CMS został uporządkowany pod kątem wygody artysty i obsługi klienta:

### 1. Oferta & Galeria
* **Galeria Prac (`GalleryArtworkResource`)**:
  - Zarządzanie dziełami sztuki prezentowanymi w portfolio na podstronie `/galeria`.
  - Przypisywanie technik (olej, akryl, akwarela, rysunek), roku powstania oraz kategoryzacja.
* **Katalog Sklepowy (`ProductResource`, `ProductCategoryResource`)**:
  - Dzieła dostępne w sprzedaży bezpośredniej w sklepie internetowym.
  - Wybór wariantów (oryginał fizyczny vs wydruk kolekcjonerski).

### 2. Strony & Wygląd
* **Edytor Paragrafowy Stron Treści (`ContentPageResource`)**:
  - Moduł zarządzania treścią podstron *Regulamin Sklepu* oraz *Polityka Prywatności i Cookies*.
  - Wyposażony w dedykowany edytor paragrafów (`Repeater::make('metadata.sections')`) z osobnym identyfikatorem kotwicy (`id`), tytułem sekcji oraz wizualnym edytorem WYSIWYG (`RichEditor`).
  - Automatycznie wylicza i zwraca w API sformatowaną datę ostatniej modyfikacji (`last_updated_formatted`).
* **Sekcja Często Zadawanych Pytań (`FaqItemResource`)**:
  - Zarządzanie pytaniami i odpowiedziami w sekcji FAQ (`/api/faq`).
  - Wyposażone w edytor WYSIWYG (`RichEditor`) umożliwiający dodawanie pogrubień, kursywy, list punktowanych i odnośników.
  - Automatyczne generowanie mikrodanych strukturalnych Google Schema.org (`FAQPage`).
* **Pliki & Media Pomocnicze (`MediaResource`)**:
  - Biblioteka pomocniczych plików graficznych, banerów oraz materiałów do pobrania.

### 3. Klienci & Kontakt
* **Zapytania o Wycenę Portretów (`ContactInquiryResource`)**:
  - Rejestr zgłoszeń z formularza kontaktowego na stronie głównej i podstronie kontaktowej.
  - Zbieranie wytycznych, wybranych wymiarów, formatów oraz załączonych plików referencyjnych w kolumnie JSON `payload`.
* **Opinie i Recenzje Klienckie (`ProductReviewResource`)**:
  - Moderacja i akceptacja opinii od klientów wyświetlanych na stronie głównej (`/api/reviews/site`).

---

## 🔑 Domyślne Konto Administratora (Lokalnie)

- **URL panelu CMS**: `http://localhost:8000/admin`
- **E-mail**: `admin@hellokostek.pl`
- **Hasło**: `Admin1234!`

*(Formularz logowania na środowisku deweloperskim automatycznie uzupełnia powyższe dane).*

---

## 💻 Komendy Artisan

### 1. Odświeżenie bazy danych i zasilenie czystymi danymi Hello Kostek
```bash
php artisan migrate:fresh --seed --force
```
*(Zasila bazę danych produktami, obrazami galerii, opiniami klienckimi, podstronami prawnymi i pytaniami FAQ za pomocą `HelloKostekSeeder.php`).*

### 2. Uruchomienie lokalnego serwera API
```bash
php artisan serve --port=8000
```

### 3. Uruchomienie testów automatycznych
```bash
php artisan test
```

---

## 🌐 Konfiguracja Serwera Produkcyjnego (LH.pl)

- **Host**: `serwer69908.lh.pl` (Port FTP: 21, Port SSH: 22)
- **Użytkownik**: `serwer69908`
- **Ścieżka panelu CMS**: `/public_html/admin.hellokostek.pl/`
- **Katalog publiczny CMS**: `/public_html/admin.hellokostek.pl/public/`
- **Katalog główny sklepu**: `/public_html/hellokostek.pl/`
