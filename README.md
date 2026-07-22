# Pracownia Artystyczna & Sklep – Hello Kostek

Oficjalna platforma internetowa oraz e-sklep autorskiej pracowni malarskiej **Kostka Macieja Kosteczki** z siedzibą w Siewierzu (woj. śląskie). 

Platforma łączy prezentację bogatego dorobku artystycznego (obrazy olejne, akrylowe, akwarele, rysunki ołówkiem) z zaawansowanym systemem e-commerce do sprzedaży oryginalnych dzieł sztuki, reprodukcji artystycznych, zestawów produktowych oraz składania spersonalizowanych zamówień (np. portretów ze zdjęcia).

Głównym mottem artysty jest: **„Człowiek dla człowieka – sztuka prawdziwa bez AI”**, co podkreśla w pełni ręczne techniki malarskie i autentyczność każdego dzieła.

> [!NOTE]
> **Jednolitość językowa:** Cały serwis (frontend sklepu, backend API REST oraz panel CMS Filament) działa **w 100% w języku polskim**. Usunięto wszelkie mechanizmy wielojęzyczności i zagnieżdżonych słowników na rzecz prostej, bezawaryjnej architektury.

---

## 🚀 Główne Funkcjonalności

Aplikacja składa się z dedykowanych modułów wspierających prezentację sztuki, sprzedaż online, automatyzację księgowo-magazynową oraz pełną obsługę klienta:

### 1. Strona Główna (`/`)
* **Sekcja Hero**: Przyciągający uwagę nagłówek z manifestem artystycznym oraz prezentacją autorskiego dzieła.
* **O Mnie & Biografia**: Prezentacja sylwetki artysty, jego pasji do sztuki tradycyjnej oraz powiązania pracy teatralnej z malarstwem.
* **Kalkulator & Formularz Zleceń Indywidualnych**: Interaktywny formularz wyceny i zamawiania portretów na zamówienie ze zdjęcia (załączanie plików referencyjnych, wybór techniki, formatu, liczby osób i opisu wizji).
* **Karuzela Produktów**: Dynamiczny slajder wyznaczonych i polecanych prac z autorskiej kolekcji.
* **Sekcja Opinii Klienckich (`Testimonials.tsx`)**: Opinie ze zdjęciami, oceną gwiazdkową, emoji i metadanymi zarządzane w panelu CMS (`GET /api/reviews/site`).
* **Sekcja Często Zadawanych Pytań FAQ (`FaqSection.tsx`)**: Interaktywny akordeon z pytaniami i odpowiedziami dotyczącymi zamawiania prac, pakowania, oprawy oraz dostawy (`GET /api/faq`).

### 2. Sklep z Pracami i Reprodukcjami (`/sklep`)
* **Katalog Produktów**: Przejrzysta prezentacja gotowych obrazów, akwareli, rysunków oraz wydruków.
* **Filtrowanie i Wyszukiwanie**: Wygodne filtrowanie wg kategorii (Olej, Akryl, Akwarela, Rysunek), atrybutów oraz wyszukiwarka z podpowiedziami na żywo.
* **Karta Produktu (`/sklep/[slug]`)**: 
  - Warianty zakupu: Oryginał dzieła (`-OR`) vs Artystyczna reprodukcja / wydruk (`-PR`).
  - Zestawy produktowe (Bundles) z dynamicznym przeliczaniem dostępności komponentów.
  - Wyświetlanie najniższej ceny z ostatnich 30 dni (zgodność z dyrektywą Omnibus).
  - Wyliczanie cen indywidualnych oraz B2B.
  - Formularz zapisów na powiadomienia o ponownej dostępności (Back in Stock).

### 3. Galeria Prac & Portfolio (`/galeria`)
* **Archiwum Dzieł Sztuki**: Bogata kolekcja dotychczas stworzonych obrazów i rysunków.
* **Filtrowanie Techniczne**: Sortowanie według techniki (olej, akryl, akwarela, rysunek) oraz powiązanego roku powstania.
* **Pełnoekranowy Lightbox**: Podgląd dzieła w wysokiej rozdzielczości ze szczegółami i sterowaniem nawigacją.

### 4. Koszyk i Proces Zamówienia (`/koszyk` & `/checkout`)
* **Koszyk w Nanostores**: Szybki koszyk z natychmiastową synchronizacją lokalną (`localStorage`).
* **Sposoby Płatności**: Integracja ze Stripe, Przelewy24 oraz BLIK Direct.
* **Metody Dostawy**: Elastyczne strefy dostaw i opcje wysyłki.
* **Konto Klienta & Zakupy Gościnne**: Obsługa zamówień bez rejestracji (Guest Checkout) oraz z zalogowanym kontem klienta.
* **Kody Rabatowe**: System kuponów kwotowych i procentowych.

### 5. Faktury, Księgowość i Zgodność Prawna
* **Dedykowany Moduł Faktur**: Generowanie faktur w formacie PDF (Wbudowany silnik + integracje z Fakturownia, iFirma, inFakt, wFirma).
* **Zgodność z RODO / AI Act / Dyrektywą Omnibus**: Wbudowana obsługa zgód na ciasteczka (Cookie Banner), deklaracji tworzenia bez AI oraz historii cen.
* **Podstrony Prawne (`/regulamin` & `/polityka-prywatnosci`)**: Zarządzanie paragrafami z poziomu CMS z automatycznym generowaniem spisu treści i anchors.

---

## 🛠️ Stack Technologiczny

### Frontend
- **Framework**: [Astro v5](https://astro.build/) (Static Site Generation - SSG + Hydratacja komponentów).
- **Komponenty UI**: [React v19](https://react.dev/) (interaktywne formularze, koszyk, akordeon FAQ, lightbox).
- **Stylizowanie**: [Tailwind CSS v4](https://tailwindcss.com/) + animacje w Vanilla CSS.
- **Ikony i Animacje**: Lucide React & Framer Motion.
- **Stan Globalny**: Nanostores (`cartStore.ts`).

### Backend & CMS
- **Framework Backendowy**: [Laravel 13](https://laravel.com/) (bezstanowe REST API).
- **Panel Administracyjny**: [Filament CMS v5](https://filamentphp.com/) (zarządzanie produktami, zamówieniami, galeriami, fakturami, podstronami treści i ustawieniami).
- **Baza Danych**: SQLite (środowisko deweloperskie) / MySQL (środowisko produkcyjne).
- **Generowanie Dokumentów PDF**: Barryvdh DomPDF.

---

## 📁 Struktura Repozytorium

```text
hello-kostek-dev/
├── backend/                        # Backend Laravel + Filament CMS
│   ├── app/
│   │   ├── Domain/Commerce/        # Enumy i logika biznesowa e-commerce
│   │   ├── Filament/Resources/     # Moduły CMS (Products, Orders, GalleryArtworks, MediaResource, ContentPages, itp.)
│   │   ├── Http/Controllers/Api/   # Endpointy REST API (/api/v1/products, /api/v1/checkout, /api/v1/content-pages, itp.)
│   │   ├── Models/                 # Modele danych Eloquent (Product, Order, GalleryArtwork, ContentPage, itp.)
│   │   └── Support/                # Klasy pomocnicze (PublicMediaUrl, JsonLdHelper, StoreSettings)
│   ├── database/
│   │   ├── migrations/             # Migracje struktury bazy danych
│   │   └── seeders/
│   │       └── HelloKostekSeeder.php # Główny seeder produkcyjny
│   └── routes/api.php              # Trasy REST API
├── src/                            # Kod źródłowy frontendu Astro / React
│   ├── assets/                     # Zdjęcia i grafiki dzieł sztuki
│   ├── components/                 # Komponenty React (ProductCard, Cart, FaqSection, OrderForm, Lightbox, itp.)
│   ├── layouts/                    # Szablony układu Astro (Layout.astro)
│   └── pages/                      # Strony i podstrony Astro (index.astro, galeria.astro, sklep/, regulamin.astro)
├── package.json                    # Skrypty i zależności frontendu Astro
├── AGENTS.md                       # Wytyczne deweloperskie i dane dostępowe produkcyjne
└── README.md                       # Główna dokumentacja projektu
```

---

## 💻 Komendy Deweloperskie

### 1. Uruchomienie Backend API & CMS (Laravel)
```bash
cd backend
php artisan migrate --force
php artisan db:seed --class=HelloKostekSeeder
php artisan serve --port=8000
```
* **Panel CMS Filament**: `http://localhost:8000/admin`
* **Dane logowania dev**: `admin@hellokostek.pl` / `Admin1234!`

### 2. Uruchomienie Frontendu (Astro)
```bash
# z głównego katalogu repozytorium:
npm install
npm run dev
```
Domyślny adres sklepu: **`http://localhost:4321`**

### 3. Testy Automatyczne
```bash
cd backend
php artisan test
```

---

## 🌐 Serwer Produkcyjny (LH.pl)

* **Host**: `serwer69908.lh.pl` (Port SSH: 22, Port FTP: 21)
* **Użytkownik**: `serwer69908`
* **Katalog sklepu/frontendu**: `/public_html/hellokostek.pl/`
* **Katalog backendu/CMS**: `/public_html/admin.hellokostek.pl/`
* **Publiczny katalog CMS**: `/public_html/admin.hellokostek.pl/public/`
