# Plan Implementacji: Audyt i Naprawa (Laravel 13 + Filament 5 + Astro 7)

Niniejszy plan powstał w oparciu o procedurę audytu opisaną w dokumencie `AUDIT_AND_FIX.md`. Zawiera zidentyfikowane niezgodności oraz proponowane, precyzyjne poprawki w 5 sekwencyjnych krokach.

---

## 1. Analiza stanu obecnego i zidentyfikowane problemy

1. **Krok 1 (Modele i Eloquent):**
   - Wszystkie 41 modeli w `app/Models/` posiadają poprawnie zdefiniowane tablice `$fillable`.
   - Zdecydowana większość modeli używa metody `casts(): array` (standard Laravel 13). Jednak w 6 modelach pomocniczych (`Media`, `CartItem`, `OrderReturnItem`, `ProductBundleItem`, `ProductAttributeAssignment`, `ProductRelation`) brakuje jawnej metody `casts(): array`, co może prowadzić do niejawnego typowania kolumn całkowitych (`quantity`, `sort_order`, `file_size`).
   - Wszystkie relacje Eloquent mają jawne typy zwracane (`BelongsTo`, `HasMany`, `BelongsToMany`, `HasOne`).
   - Migracje w `database/migrations/` poprawnie stosują `constrained()->cascadeOnDelete()` lub `nullOnDelete()`.

2. **Krok 2 (Formularze i Tabele Filament 5):**
   - **Unikalność:** Wszystkie pola unikalne w Schemas posiadają `->unique(ignoreRecord: true)`.
   - **Pola relacyjne (Select):**
     - W `OrderForm.php`: pola relacji `user_id` oraz `product_id` (w repeaterze pozycji) mają `searchable()`, ale brakuje `->preload()`.
     - W `OrderReturnForm.php`: pola relacji `order_id` oraz `user_id` mają `searchable()`, ale brakuje `->preload()`.
   - **Akcje tabeli:**
     - W `UsersTable.php`: zarejestrowano `EditAction::make()`, brakuje `DeleteAction::make()` w `recordActions`.
     - W `EmailTemplatesTable.php`: zarejestrowano podwójnie `EditAction::make()`, brakuje `DeleteAction::make()`.
   - **Strony zasobów:** Metody `mutateFormDataBeforeSave` i `mutateFormDataBeforeCreate` we wszystkich stronach poprawnie zwracają tablicę `$data`.

3. **Krok 3 (Pamięć Masowa i Uploady):**
   - **Konfiguracja dysku `public` w `config/filesystems.php`:** Ścieżka wskazuje sztywno na `storage_path('app/public')`. Na hostingu współdzielonym (np. struktura `public_html`), gdy brak obsługi symlinka `storage:link`, dysk powinien umożliwiać elastyczne zdefiniowanie katalogu docelowego przez zmienne środowiskowe (`FILESYSTEM_PUBLIC_ROOT` i `FILESYSTEM_PUBLIC_URL`).
   - **Komponenty `FileUpload::make()`:**
     - W `ProductForm.php`: uploady (`featured_image_path`, `hover_image_path`, `gallery_image_paths`, `gpsr_document_path`, `metadata.og_image_path`) posiadają `disk('public')`, ale brakuje jawnego `->visibility('public')`.
     - W `ContentPageForm.php`: `hero_image_path` oraz `metadata.og_image_path` nie mają `->visibility('public')`.
     - W `GalleryArtworkForm.php`: `image_path` nie ma `->visibility('public')`.
     - W `StoreSettingForm.php`: `admin_logo_path`, `admin_favicon_path`, `admin_login_background_path` mają `visibility('public')`, ale brakuje `->disk('public')`.

4. **Krok 4 (Warstwa API Laravel -> Astro):**
   - Kontrolery API (np. `CustomerAddressController`, `GalleryArtworkController`, `FaqIndexController`, `ContentPageShowController`, `ContentPageIndexController`, `ProductReviewController`, `OrderReturnController`) zwracają surowe tablice lub modele Eloquent bez transformatorów.
   - Wdrożenie klas `JsonResource` z Laravel 13 w katalogu `app/Http/Resources/` ustandaryzuje zwracane struktury.
   - **CORS (`config/cors.php`):** Domyślne `allowed_origins` to `http://localhost:3000`. Należy ustawić domyślnie `http://localhost:4321,http://localhost:3000,https://hellokostek.pl` oraz zaktualizować `.env` i `.env.example`, a także upewnić się, że `supports_credentials` jest włączone (`true`).

5. **Krok 5 (Pobieranie Danych i Typy w Astro 7):**
   - W dynamicznej ścieżce `src/pages/sklep/[id].astro` zapytanie do API jest już objęte blokiem `try...catch` z bezpiecznym fallbackiem do `SHOP_PRODUCTS`.
   - Należy utworzyć strukturę `src/types/api.ts` (w tym ogólne typy `ApiResponse<T>`, `PaginatedResponse<T>`, definicje payloadów API), zachowując kompatybilność z istniejącym `src/types.ts`.

---

## 2. Proponowane Zmiany i Nowe Pliki

### Krok 1: Modele Eloquent
- Zmodyfikować modele:
  - [Media.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Models/Media.php): dodać `casts(): array` dla `file_size`, `width`, `height`.
  - [CartItem.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Models/CartItem.php): dodać `casts(): array` dla `quantity`.
  - [OrderReturnItem.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Models/OrderReturnItem.php): dodać `casts(): array` dla `quantity`.
  - [ProductBundleItem.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Models/ProductBundleItem.php): dodać `casts(): array` dla `quantity`, `sort_order`.
  - [ProductAttributeAssignment.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Models/ProductAttributeAssignment.php): dodać `casts(): array` dla `sort_order`.
  - [ProductRelation.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Models/ProductRelation.php): dodać `casts(): array` dla `sort_order`.

### Krok 2: Formularze i Tabele Filament 5
- [OrderForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Orders/Schemas/OrderForm.php):
  - Dodać `->preload()` do `Select::make('user_id')` oraz `Select::make('product_id')`.
- [OrderReturnForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/OrderReturns/Schemas/OrderReturnForm.php):
  - Dodać `->preload()` do `Select::make('order_id')` oraz `Select::make('user_id')`.
- [UsersTable.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Users/Tables/UsersTable.php):
  - Dodać `DeleteAction::make()->iconButton()->tooltip('Usuń')` w `recordActions`.
- [EmailTemplatesTable.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/EmailTemplates/Tables/EmailTemplatesTable.php):
  - Dodać `DeleteAction::make()->iconButton()->tooltip('Usuń')` w `recordActions`, usunąć zdublowany blok `actions()`.

### Krok 3: Pamięć Masowa i Pliki
- [config/filesystems.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/config/filesystems.php):
  - Zaktualizować definicję dysku `public`, aby wspierała zmienne `FILESYSTEM_PUBLIC_ROOT` i `FILESYSTEM_PUBLIC_URL` z domyślnym zachowaniem dla środowiska lokalnego i produkcyjnego.
- [ProductForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/Products/Schemas/ProductForm.php):
  - Dodać `->visibility('public')` do `featured_image_path`, `hover_image_path`, `gallery_image_paths`, `gpsr_document_path`, `metadata.og_image_path`.
- [ContentPageForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/ContentPages/Schemas/ContentPageForm.php):
  - Dodać `->visibility('public')` do `hero_image_path`, `metadata.og_image_path`.
- [GalleryArtworkForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/Schemas/GalleryArtworkForm.php):
  - Dodać `->visibility('public')` do `image_path`.
- [StoreSettingForm.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/StoreSettings/Schemas/StoreSettingForm.php):
  - Dodać `->disk('public')` do `metadata.admin_logo_path`, `metadata.admin_favicon_path`, `metadata.admin_login_background_path`.

### Krok 4: Refaktoryzacja API i CORS
- Utworzyć klasy JsonResource w `backend/app/Http/Resources/`:
  - `CustomerAddressResource.php`
  - `GalleryArtworkResource.php`
  - `FaqItemResource.php`
  - `ContentPageResource.php`
  - `ProductReviewResource.php`
  - `OrderReturnResource.php`
- Podpiąć JsonResource w kontrolerach:
  - [CustomerAddressController.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Controllers/Api/CustomerAddressController.php)
  - [GalleryArtworkController.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Controllers/Api/GalleryArtworkController.php)
  - [FaqIndexController.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Controllers/Api/FaqIndexController.php)
  - [ContentPageIndexController.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Controllers/Api/ContentPageIndexController.php)
  - [ContentPageShowController.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Controllers/Api/ContentPageShowController.php)
  - [ProductReviewController.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Controllers/Api/ProductReviewController.php)
- Konfiguracja CORS i .env:
  - [config/cors.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/config/cors.php): zaktualizować domyślne dozwolone domeny: `http://localhost:4321,http://localhost:3000,https://hellokostek.pl`.
  - [backend/.env](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/.env) oraz [backend/.env.example](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/.env.example): zaktualizować `ALLOWED_ORIGINS` oraz `FRONTEND_URL` na `http://localhost:4321,https://hellokostek.pl`.

### Krok 5: Typy i Pobieranie Danych w Astro 7
- Utworzyć [src/types/api.ts](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/types/api.ts) z interfejsami:
  - `ApiResponse<T>`, `PaginatedResponse<T>`, `PaginationMeta`
  - `ApiProduct`, `ApiGalleryArtwork`, `ApiContentPage`, `ApiFaqItem`, `ApiCustomerAddress`
- Zaktualizować [src/types.ts](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/types.ts), aby re-eksportował typy z `src/types/api.ts` z zachowaniem pełnej zgodności wstecznej.

### Protokół Zakończenia
- Wyczyszczenie pamięci podręcznej:
  `php artisan config:clear && php artisan route:clear && php artisan view:clear`
- Weryfikacja budowania frontendu:
  `npm run build`
- Weryfikacja endpointów backendu.

---

## 3. Plan weryfikacji

1. **Weryfikacja backendu (Laravel 13):**
   - Uruchomienie komend czyszczenia cache.
   - Uruchomienie `php artisan route:list --path=api` – potwierdzenie braku błędów routingu.
   - Weryfikacja syntaktyczna i działanie endpointów API (`/api/health`, `/api/gallery`, `/api/faq`, `/api/content/pages`).
2. **Weryfikacja panelu Filament 5:**
   - Weryfikacja renderowania formularzy edycji i tworzenia produktów, kategorii, stron, opinii, zamówień.
   - Sprawdzenie akcji usuwania i edycji w tabelach użytkowników i szablonów e-mail.
3. **Weryfikacja frontendu (Astro 7):**
   - Uruchomienie `npm run build` i potwierdzenie 100% sukcesu generowania 22 podstron.
