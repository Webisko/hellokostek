# Pracownia Artystyczna & Sklep – Hello Kostek

Oficjalna platforma internetowa oraz sklep autorskiej pracowni malarskiej **Kostka Macieja Kosteczki**. 

Platforma łączy prezentację bogatego dorobku artystycznego (obrazy olejne, akrylowe, akwarele, rysunki ołówkiem) z dedykowanym systemem e-commerce do bezpośredniej sprzedaży oryginalnych dzieł sztuki (unikatów 1/1), kolekcjonerskich reprodukcji na papierze archiwalnym, zestawów oraz składania spersonalizowanych zamówień na ręcznie malowane portrety ze zdjęcia.

Głównym mottem twórczym artysty jest: **„Człowiek dla człowieka – sztuka prawdziwa bez AI”**, co podkreśla w 100% tradycyjny warsztat malarski, mistrzowskie rzemiosło sztalugowe, naturalne pigmenty i brak generatywnych grafik AI.

> [!NOTE]
> **Tożsamość geograficzna, prawna i kontakt z pracownią:**
> * **Siedziba formalno-prawna (CEIDG / dane do faktur):** Rynek 33, 42-470 Siewierz, woj. śląskie, NIP: `6252363656`, REGON: `527158196`.
> * **Pracownia artystyczna i lokalizacja twórcza (odbiory osobiste, SEO / Schema.org):** Łódź, Śródmieście, woj. łódzkie.
> * **Bezpośredni kontakt:** e-mail: [kontakt@hellokostek.pl](mailto:kontakt@hellokostek.pl) | telefon: [+48 662 707 153](tel:+48662707153).
> * **Social Media:** [Instagram (@hellokostek)](https://www.instagram.com/hellokostek/) | [Facebook (/hellokostek/)](https://www.facebook.com/hellokostek/) | [TikTok (@hellokostek)](https://www.tiktok.com/@hellokostek) | [YouTube (@hellokostek)](https://www.youtube.com/@hellokostek).
> * **Jednolitość językowa:** Cały serwis (frontend sklepu, panel CMS Filament oraz komunikaty REST API) działa **w 100% w języku polskim**.

---

## 🚀 Architektura i Wykaz Podstron Frontendu

Warstwa wizualna i interfejs użytkownika zostały zrealizowane w technologii **Astro v7** w architekturze *Islands Architecture* z komponentami **React v19** oraz biblioteką mapową **Leaflet**.

### 1. Strona Główna (`/`)
* **Sekcja Hero**: Przyciągający nagłówek z manifestem artystycznym oraz prezentacją autorskiego dzieła (*Portret Franka*).
* **O Mnie & Biografia**: Skrócona prezentacja sylwetki artysty, jego pasji do sztuki tradycyjnej oraz powiązania pracy teatralnej z malarstwem.
* **Kalkulator & Formularz Portretów**: Interaktywny konfigurator portretu na zamówienie ze zdjęcia:
  - Wybór krosna: **płótno prostokątne** lub unikalne **autorskie płótno owalne**.
  - Formaty: `30x40 cm`, `40x50 cm`, `50x70 cm` oraz wymiary niestandardowe.
  - Wybór liczby postaci (portret pojedynczy, podwójny, wieloosobowy, portret pupila).
  - Automatyczne wyliczanie zaliczki początkowej (50% bezzwrotnego zadatku rezerwującego termin w kalendarzu sztalugowym).
* **Karuzela Dzieł (`ProductSlider.tsx`)**: Dynamiczny slajder polecanych prac z autorskiej kolekcji.
* **Sekcja Opinii Klienckich (`Testimonials.tsx`)**: Opinie ze zdjęciami, ocenami gwiazdkowymi, emoji i recenzjami pobierane dynamicznie z API (`GET /api/reviews/site`) oraz zsynchronizowane z wizytówką Google Places.
* **Baza Często Zadawanych Pytań (`FaqSection.tsx`)**: Akordeon z odpowiedziami dotyczącymi zamawiania prac, pakowania, bezpiecznej oprawy i wysyłki (`GET /api/faq`) zintegrowany ze znacznikami Google Schema.org `FAQPage`.

### 2. Sklep z Pracami i Reprodukcjami (`/sklep`)
* **Katalog Produktów**: Przejrzysta siatka dostępnych obrazów, akwareli i rysunków.
* **Filtrowanie i Wyszukiwanie**: Filtry głównych kategorii artystycznych (*Olej*, *Akryl*, *Akwarela*, *Rysunek*), atrybutów fizycznych oraz wyszukiwarka z podpowiedziami na żywo (`GET /api/catalog/search/suggest`).

### 3. Karta Dzieła Sztuki (`/sklep/[id]`)
* **Wybór Wariantu**:
  - **Oryginał dzieła** (`-OR`) – unikatowy, pojedynczy egzemplarz fizyczny malowany na sztaludze przez artystę (stan magazynowy: 1 szt.).
  - **Artystyczna reprodukcja / wydruk** (`-PR`) – wysokiej jakości wydruk cyfrowy na certyfikowanym papierze archiwalnym.
* **Zgodność z Dyrektywą Omnibus**: Prezentacja najniższej ceny z ostatnich 30 dni przed obniżką.
* **Powiadomienia o Dostępności**: Formularz zapisu na powiadomienie mailowe przy wyprzedanym nakładzie (`POST /api/catalog/products/back-in-stock-subscribe`).
* **Pełnoekranowy Lightbox**: Podgląd pociągnięć pędzla, impastów i faktury płótna w wysokiej rozdzielczości.
* **1-Click Modal Checkout**: Szybki, 2-etapowy proces zakupowy otwierany bezpośrednio z karty dzieła:
  - *Krok 1 (Dostawa & Rabaty):* Dane zamawiającego, wybór metody dostawy (InPost Paczkomat 24/7, Orlen Paczka, kurier ubezpieczony z opcją ostrożnie), walidacja kodów rabatowych (`POST /api/coupons/validate`).
  - *Interaktywna Mapa Punktów Odbioru (`ParcelMapModal.tsx`):* Wybór automatu Paczkomat lub punktu Orlen Paczka bezpośrednio na pełnoekranowej, interaktywnej mapie **Leaflet**, z geolokalizacją, wyszukiwarką miast i podglądem adresów.
  - *Krok 2 (Płatność):* Płatność BLIK z kodem 6-cyfrowym, karta płatnicza Stripe (Visa/Mastercard z 3D Secure), szybki przelew online Przelewy24, tradycyjny przelew bankowy oraz finalizacja zamówienia (`POST /api/checkout/place`).

### 4. Galeria Prac & Portfolio (`/galeria`)
* **Archiwum Dzieł Malarskich**: Pełna kolekcja dotychczasowych prac artysty zasilana dynamicznie z API (`GET /api/gallery`).
* **Filtrowanie Wielokryterialne**: Sortowanie według techniki malarskiej (Olej, Akryl, Akwarela, Rysunek) oraz rocznika powstania (2026, 2024, 2023, 2022, starsze).
* **Podgląd Lightbox**: Pełnoekranowy pokaz dzieł z obsługą nawigacji strzałkami i klawiszem Escape.

### 5. O Artyście (`/o-mnie`)
* Dedykowana podstrona biograficzna Macieja Kosteczki – opis drogi twórczej, warsztatu rzemieślniczego, stosowanych werniksów i pigmentów oraz manifestu sztuki bez udziału sztucznej inteligencji.

### 6. Kontakt & Zamówienia Indywidualne (`/kontakt`)
* Formularz wyceny portretu na zamówienie ze zdjęcia z uploadem plików referencyjnych (`multipart/form-data`) bezpośrednio do bazy CMS (`POST /api/inquiries`).
* Dane kontaktowe, godziny pracy pracowni, bezpośredni adres e-mail oraz odnośniki do mediów społecznościowych.

### 7. Podstrony Prawne & Zgodność (`/regulamin` & `/polityka-prywatnosci`)
* Dynamicznie pobierane z CMS (`GET /api/content/pages/regulamin`, `GET /api/content/pages/polityka-prywatnosci`).
* Edytor paragrafowy z automatycznym generowaniem spisu treści (TOC), śledzeniem aktywnej sekcji podczas przewijania (ScrollSpy), mobilnym wysuwanym panelem spisu treści (Drawer) i obsługą kotwic w URL.
* Szczegółowy audyt zgodności prawnej opisano w dedykowanych dokumentach:
  - [Raport wymogów prawnych e-commerce 2026](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/raport-wymagan.md) (dyrektywa 2023/2673 RMA, EAA, Omnibus, GPSR).
  - [Wymagania i specyfikacja Cookies / Consent Mode v2](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/wymagania-cookies.md).

### 8. Strony Sukcesu i Mikrointerakcje
* `/sukces-zakup`: Podsumowanie opłaconego zamówienia z czyszczeniem koszyka w Nanostores (`clearCart()`).
* `/sukces-kontakt`: Potwierdzenie przyjęcia formularza wyceny portretu ze zdjęciem.
* **Mikrointerakcje:** Animowane logo SVG w stopce (`Footer.astro`) uruchamiane po dotknięciu lub ruchu myszy, filtr SVG Gooey (`Layout.astro`), przycisk powrotu na górę strony (`BackToTop.astro`).

---

## 🛠️ Stack Technologiczny

### Frontend
- **Framework**: [Astro v7.3+](https://astro.build/) (architektura wyspowa SSG/Islands).
- **Interaktywne Komponenty UI**: [React v19](https://react.dev/).
- **Stylizowanie**: [Tailwind CSS v4](https://tailwindcss.com/) zintegrowany przez `@tailwindcss/vite`.
- **Mapy i Punkty Odbioru**: [Leaflet v1.9](https://leafletjs.com/) (`leaflet`, `@types/leaflet`) do interaktywnego wyboru Paczkomatów i punktów Orlen Paczka.
- **Animacje**: [Motion v12/13](https://motion.dev/) (nowa generacja Framer Motion).
- **Ikony**: Lucide React (`lucide-react`).
- **Typografia**: `@fontsource/poppins`, `@fontsource/montserrat`, `@fontsource/fira-code`.
- **Zarządzanie Stanem**: Nanostores (`cartStore.ts` z persistent atom w `localStorage`).
- **SEO & AI Search**: `@astrojs/sitemap`, mikrodane Schema.org (`LocalBusiness`, `Person`, `WebSite`, `Service`, `Product`, `FAQPage`), pliki `/llms.txt` i `/llms-full.txt`.

### Backend & CMS
- **Framework**: [Laravel 13](https://laravel.com/) (REST API, PHP 8.3+).
- **Architektura Domenowa**: 9 dedykowanych modułów w `app/Domain/` (Admin, Analytics, Commerce, Communication, Customers, Imports, Integrations, Operations, Storefront).
- **Modele i Baza**: 41 modeli Eloquent, SQLite (środowisko deweloperskie) / MySQL MariaDB 11.4 (środowisko produkcyjne).
- **Panel Administracyjny**: [Filament CMS v5.6](https://filamentphp.com/) + Filament Breezy v3.2.
- **Autentykacja API**: Laravel Sanctum (tokeny Bearer dla klientów i strefy konta, sesje panelu dla administratorów).
- **Płatności i Finanse**: Stripe PHP SDK v21, Przelewy24, BLIK.
- **Logistyka**: Integracja z InPost ShipX (generowanie etykiet PDF), Orlen Paczka, webhooki BaseLinker.
- **Księgowość**: Barryvdh Laravel DomPDF v3.1 (wbudowane faktury PDF) + sterowniki do Fakturownia, iFirma, inFakt, wFirma.
- **Weryfikacja Kontrahentów**: Bezpośrednia integracja z rejestrem BIR Głównego Urzędu Statystycznego (GUS REGON/NIP).
- **Zadania w Tle i Backup**: Laravel Queue, Spatie Laravel Backup v10, Laravel Reverb.
- **Generator Grafik Social Media**: Dynamiczne, podpisane obrazy OpenGraph (`GET /og-image`).

---

## 🎛️ Panel Administracyjny Filament CMS (25 Zasobów)

Panel administracyjny pod adresem `/admin` zawiera 25 wyspecjalizowanych modułów pogrupowanych tematycznie:

| Grupa Modułów | Zasób Filament | Opis i Rola w Systemie |
| :--- | :--- | :--- |
| **Sprzedaż & Magazyn** | `ProductResource` | Zarządzanie dziełami, warianty `-OR` (oryginał) vs `-PR` (wydruk), historia cen Omnibus, ceny B2B. |
| | `ProductCategoryResource` | Kategorie dzieł sztuki (Olej, Akryl, Akwarela, Rysunek). |
| | `ProductAttributeResource` | Definicje parametrów fizycznych (podłoże, format, technika oprawy). |
| | `OrderResource` | Obsługa zamówień, zmiana statusów, generowanie etykiet InPost/Orlen i faktur PDF. |
| | `InvoiceResource` | Generowanie i wysyłka faktur VAT/bez VAT (wbudowany DomPDF lub API księgowe). |
| | `CouponResource` | Kampanie promocyjne, kody rabatowe kwotowe i procentowe, limity użyć. |
| | `AbandonedCartResource` | Monitorowanie nieukończonych koszyków i moduł ich odzyskiwania. |
| | `BackInStockSubscriptionResource` | Rejestr klientów oczekujących na powrót nakładu reprodukcji. |
| | `OrderReturnResource` | Elektroniczne zgłoszenia odstąpienia od umowy (zwroty RMA) wg dyrektywy 2023/2673. |
| **Klienci & Komunikacja** | `CustomerResource` | Kartoteki klientów, profile B2C i firmy B2B, historia transakcji. |
| | `ContactInquiryResource` | Zapytania o wycenę portretów ze zdjęciami referencyjnymi w formacie JSON. |
| | `ProductReviewResource` | Moderacja recenzji produktów i opinii o pracowni. |
| | `EmailTemplateResource` | Szablony powiadomień transakcyjnych HTML. |
| | `TransactionalEmailLogResource` | Dziennik i podgląd techniczny wysłanych wiadomości e-mail. |
| **Treść & Portfolio** | `GalleryArtworkResource` | Zarządzanie dziełami w portfolio na podstronie `/galeria`. |
| | `ContentPageResource` | Edytor podstron prawnych z blokami sekcji paragrafowych i kotwicami. |
| | `FaqItemResource` | Baza pytań i odpowiedzi FAQ z edytorem WYSIWYG i Schema.org. |
| | `MediaResource` | Biblioteka multimediów z automatyczną kompresją do formatu WebP. |
| **Ustawienia & Prawne** | `StoreSettingResource` | Dane firmy, progi darmowej dostawy, stawki VAT, kody GA4/GTM/Pixel, tryb konserwacji. |
| | `CookieConsentResource` | Rejestr zgód użytkowników na cookies (wymóg dowodowy UODO/RODO). |
| | `RedirectRuleResource` | Zarządzanie przekierowaniami URL 301 i 302. |
| | `AdminActivityLogResource` | Dziennik audytowy operacji wykonywanych w CMS przez administratorów. |
| | `IntegrationLogResource` | Rejestr zapytań i odpowiedzi bramek płatności oraz API kurierskich. |
| | `FailedJobResource` | Podgląd i ponawianie błędów asynchronicznych kolejek zadań. |
| | `UserResource` | Konta administratorów panelu i uprawnienia dostępowe. |

---

## 🔌 Integracje i Usługi Zewnętrzne

1. **Bramki Płatności Online**:
   - **Przelewy24**: Szybkie przelewy bankowe online oraz płatności BLIK.
   - **Stripe**: Obsługa kart płatniczych Visa / Mastercard z zabezpieczeniem 3D Secure.
   - **Przelew tradycyjny**: Dane rachunku bankowego generowane na stronie podsumowania zamówienia.
2. **Operatorzy Logistyczni**:
   - **InPost Paczkomaty 24/7 & Kurier**: Integracja z InPost ShipX, generowanie etykiet wysyłkowych PDF bezpośrednio z panelu zamówienia (`/admin/orders/{number}/inpost-label`).
   - **Orlen Paczka**: Obsługa punktów odbioru z generowaniem etykiet PDF (`/admin/orders/{number}/orlen-label`).
   - **BaseLinker**: Odbiór statusów paczek przez dedykowany webhook (`/api/integrations/baselinker/shipment-callback`).
3. **Weryfikacja Kontrahentów B2B (GUS BIR)**:
   - Bezpośrednie wyszukiwanie danych firmy w rejestrze REGON Głównego Urzędu Statystycznego po numerze NIP: `GET /api/b2b/gus/{nip}`.
4. **Opinie z Google Places**:
   - Dedykowany serwis `GooglePlaceReviewsService.php` synchronizujący autentyczne recenzje z wizytówki Google.
5. **Księgowość i Fakturowanie**:
   - 5 wymiennych sterowników: wbudowany PDF (`BuiltInInvoiceDriver`), Fakturownia, iFirma, inFakt, wFirma.

---

## 📁 Struktura Repozytorium

```text
hello-kostek-dev/
├── backend/                             # Backend Laravel 13 + Filament CMS
│   ├── app/
│   │   ├── Console/Commands/            # Komendy crona (odzyskiwanie koszyków, konwersja WebP)
│   │   ├── Domain/                      # 9 domen biznesowych (Commerce, Storefront, Integrations, itp.)
│   │   ├── Filament/Resources/          # 25 modułów panelu CMS
│   │   ├── Http/Controllers/Api/        # Kontrolery REST API (/api/catalog, /api/checkout, itp.)
│   │   ├── Jobs/                        # Asynchroniczne zadania kolejek (analityka, księgowość)
│   │   ├── Models/                      # 41 modeli Eloquent (Product, Order, GalleryArtwork, itp.)
│   │   └── Support/                     # Klasy pomocnicze (Invoicing, WebP converter, StoreSettings)
│   ├── database/
│   │   ├── migrations/                  # Migracje struktury bazy danych
│   │   └── seeders/
│   │       └── HelloKostekSeeder.php    # Główny seeder produkcyjny Hello Kostek
│   ├── routes/
│   │   ├── api.php                      # Trasy REST API (/api/...)
│   │   └── web.php                      # Trasy panelu, etykiet, eksportów i sitemap
│   ├── API_REFERENCE.md                 # Kompletna dokumentacja techniczna endpointów API
│   ├── raport-wymagan.md                # Raport zgodności z prawem e-commerce 2026
│   └── wymagania-cookies.md             # Specyfikacja techniczna wdrożenia cookies i RODO
├── src/                                 # Kod źródłowy frontendu Astro v7 / React v19
│   ├── assets/                          # Zdjęcia dzieł malarskich i grafiki
│   ├── components/                      # Komponenty React, wyspy i modale mapy (ParcelMapModal)
│   ├── layouts/                         # Główny layout strony (Layout.astro ze Schema.org i filtrem SVG)
│   ├── pages/                           # Routing podstron Astro (index, sklep, galeria, o-mnie, kontakt)
│   ├── stores/                          # Stan globalny Nanostores (cartStore.ts)
│   └── styles/                          # Style CSS i konfiguracja Tailwind CSS v4
├── public/                              # Zasoby statyczne, ikony, favicony
│   ├── llms.txt                         # Skrócona specyfikacja dla modeli językowych i agentów AI
│   └── llms-full.txt                    # Pełna dokumentacja pracowni dla agentów AI
├── astro.config.mjs                     # Konfiguracja Astro (integracje React, Sitemap, Vite Tailwind)
├── package.json                         # Zależności i skrypty frontendu
├── AGENTS.md                            # Wytyczne deweloperskie dla asystentów AI
├── credentials.local.md                 # Poufne dane dostępowe i serwerowe (plik lokalny, git-ignored)
└── README.md                            # Główna dokumentacja projektu
```

---

## 💻 Komendy Deweloperskie & CLI

### 1. Backend Laravel & CMS
```bash
cd backend

# Instalacja zależności
composer install

# Migracje i zasilenie bazy danymi Hello Kostek
php artisan migrate --force
php artisan db:seed --class=HelloKostekSeeder

# Uruchomienie lokalnego serwera API
php artisan serve --port=8000
```
* **Panel CMS Filament**: `http://localhost:8000/admin`
* Szczegółowe dane kont deweloperskich znajdują się w pliku `credentials.local.md` (lub `.env.example`).

### 2. Frontend Astro
```bash
# Z głównego katalogu repozytorium:
npm install
npm run dev
```
Domyślny adres sklepu: **`http://localhost:4321`**

### 3. Komendy Cykliczne i Harmonogram (Cron)
```bash
cd backend

# Odzyskiwanie porzuconych koszyków (wysyłka sekwencji maili ratunkowych)
php artisan commerce:recover-abandoned-carts

# Czyszczenie przeterminowanych koszyków tymczasowych
php artisan commerce:cleanup-abandoned-carts

# Masowa optymalizacja i konwersja grafik do formatu WebP
php artisan media:convert-webp

# Indeksowanie i weryfikacja integralności biblioteki mediów
php artisan media:scan
```

### 4. Testy Automatyczne
```bash
cd backend
php artisan test
```

---

## 🔒 Konfiguracja Środowiska Produkcyjnego i Bezpieczeństwo

Wszelkie parametry serwerowe (host, porty SSH/FTP, baza danych, użytkownicy systemowi oraz dane logowania do panelu hostingowego SEOHost) zostały **odseparowane ze względów bezpieczeństwa** od repozytorium kodu.

* Kompletne poświadczenia serwerowe znajdują się w lokalnym pliku **`credentials.local.md`** (plik objęty regułą `.gitignore`).
* Na serwerze produkcyjnym zmienne wrażliwe należy konfigurować wyłącznie poprzez plik `.env`.
