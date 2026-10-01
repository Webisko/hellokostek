# Plan Implementacji: Porządki w Repozytorium i Usunięcie Zbędnego Kodu

Przeprowadzono audyt repozytorium pod kątem niepotrzebnych plików, pozostałości po szablonach startowych, martwego kodu oraz osieroconych modułów.

---

## 1. Zidentyfikowane Zbędne Elementy i Pozostałości

### A. Pozostałości po usuniętym Blogu i Newsletterze (Backend)
W migracji `2026_07_20_192424_drop_blog_and_newsletter_tables.php` tabele `blog_posts`, `newsletter_campaigns` i `newsletter_subscribers` zostały usunięte z bazy danych, ponieważ sklep Hello Kostek nie prowadzi bloga ani newslettera. W kodzie pozostały jednak martwe odwołania powodujące błędy:
1. **Błąd w `SitemapController.php`:** Linia 71 wykonuje zapytanie `BlogPost::query()->publiclyVisible()->get()`, co wywołuje błąd fatalny `Class "App\Models\BlogPost" not found` przy wywołaniu `/sitemap.xml`.
2. **Błąd w `ContentMapController.php`:** Linia 49 odpytuje `BlogPost::query()->publiclyVisible()->count()`, co wywołuje błąd przy pobieraniu mapy treści API.
3. **Martwe importy:**
   - `app/Filament/Pages/StoreDashboard.php`: nieużywane `use App\Models\BlogPost;` i `use App\Models\NewsletterSubscriber;`.
   - `app/Domain/Imports/JsonImportService.php`: metody `importBlogPost` oraz `importNewsletterSubscriber`.
4. **Martwe seedery z obcego projektu:**
   - `database/seeders/BlogPostRelatedProductsSeeder.php`: produkty ze sklepu z suplementami ("reishi-czerwone", "chaga").
   - `database/seeders/DevCmsReviewSeeder.php`: ponad 1000 linii dummy data z mieszankami ziołowymi i rytuałami.
5. **Osierocone klasy mailowe i zadania kolejek:**
   - `app/Mail/NewsletterMail.php`: nieużywana klasa maila newslettera.
   - `app/Jobs/SyncNewsletterToWebhookJob.php`: nieużywane zadanie synchronizacji newslettera.
6. **Zdezaktualizowane testy automatyczne:**
   - `tests/Feature/Api/NewsletterDoubleOptInTest.php`: testy usuniętych tras i tabeli newslettera.
   - `tests/Feature/Api/SeoTest.php`: testy wpisów bloga (odwołujące się do nieistniejącego modelu `BlogPost`).
   - `tests/Feature/Api/VatOssAndMppTest.php`: linie 201–225 wstawiające rekordy do usuniętej tabeli `newsletter_subscribers`.

### B. Martwe pliki szablonu startowego Astro (Frontend)
1. `src/components/Welcome.astro`: domyślny komponent powitalny instalatora Astro ("What's New in Astro 6.0?", odnośniki do Discorda), nigdzie nieużywany.
2. `src/assets/astro.svg` oraz `src/assets/background.svg`: domyślne grafiki szablonu Astro, importowane wyłącznie w `Welcome.astro`.

### C. Tymczasowe pliki robocze (Katalog `scratch/`)
W katalogu `scratch/` znajduje się 70 plików tymczasowych zajmujących łącznie kilkanaście megabajtów:
- `scratch/storefront.zip` – archiwalna paczka zip o wadze aż **8.5 MB**.
- 69 jednorazowych skryptów pomocniczych Python (testy SSL, stare skrypty wdrożeniowe, tymczasowe testy formularzy).
Wyczyszczenie tego katalogu odciąży środowisko i skróci czas operacji dyskowych.

### D. Dostosowanie asercji testów integracyjnych
- `tests/Feature/Api/SecurityTest.php`: zaktualizować asercję dozwolonych domen CORS, aby uwzględniała frontend Astro (`http://localhost:4321`, `https://hellokostek.pl`).
- `tests/Feature/Api/SeoTest.php`: zaktualizować trasę sprawdzania opinii na faktyczną `/api/reviews/site`.

---

## 2. Plan Działań Krok po Kroku

### Krok 1: Usunięcie martwego kodu i osieroconych plików Bloga i Newslettera
1. Usunąć pliki:
   - `backend/database/seeders/BlogPostRelatedProductsSeeder.php`
   - `backend/database/seeders/DevCmsReviewSeeder.php`
   - `backend/app/Mail/NewsletterMail.php`
   - `backend/app/Jobs/SyncNewsletterToWebhookJob.php`
   - `backend/tests/Feature/Api/NewsletterDoubleOptInTest.php`
2. Zaktualizować kontrolery:
   - [SitemapController.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Controllers/SitemapController.php): usunąć blok generowania URL-i dla postów bloga.
   - [ContentMapController.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Controllers/Api/ContentMapController.php): usunąć sekcję `'blog'`.
3. Zaktualizować [StoreDashboard.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Pages/StoreDashboard.php) i [JsonImportService.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Domain/Imports/JsonImportService.php): usunąć martwe importy i metody.

### Krok 2: Usunięcie zbędnych plików szablonu początkowego Astro
1. Usunąć `src/components/Welcome.astro`.
2. Usunąć `src/assets/astro.svg` oraz `src/assets/background.svg`.

### Krok 3: Oczyszczenie katalogu tymczasowego `scratch/`
1. Opróżnić katalog `scratch/` (usunięcie archiwum `storefront.zip` 8.5MB oraz starych skryptów roboczych `.py`).
2. Zachować pusty katalog `scratch/` z `.gitkeep` (zgodnie z `.gitignore`).

### Krok 4: Dostosowanie testów backendu
1. W [SeoTest.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/tests/Feature/Api/SeoTest.php): usunąć metody testowe postów bloga, zaktualizować trasę testu recenzji na `/api/reviews/site`.
2. W [SecurityTest.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/tests/Feature/Api/SecurityTest.php): dostosować oczekiwane domeny CORS.
3. W [VatOssAndMppTest.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/tests/Feature/Api/VatOssAndMppTest.php): usunąć przestarzały fragment sprawdzający czyszczenie `newsletter_subscribers`.

### Krok 5: Weryfikacja
1. Uruchomić `php artisan test` w backendzie – potwierdzić przejście testów na zielono.
2. Uruchomić `npm run build` we frontendzie – potwierdzić brak błędów kompilacji.
