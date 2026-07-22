# Pracownia Artystyczna & Sklep – Hello Kostek

Oficjalna platforma internetowa oraz e-sklep autorskiej pracowni malarskiej **Kostka Macieja Kosteczki** z siedzibą w Siewierzu (woj. śląskie). 

Platforma łączy w sobie prezentację bogatego dorobku artystycznego (obrazy olejne, akrylowe, akwarele, rysunki ołówkiem) z interaktywnymi narzędziami do zamawiania i zakupu oryginalnych dzieł sztuki oraz składania spersonalizowanych zamówień (np. portretów ze zdjęcia).

Głównym mottem artysty jest: **„Człowiek dla człowieka – sztuka prawdziwa bez AI”**, co podkreśla w pełni ręczne techniki malarskie i autentyczność każdego dzieła.

---

## 🚀 Główne Funkcjonalności

Aplikacja składa się z dedykowanych modułów wspierających zarówno prezentację sztuki, jak i pełną obsługę klienta oraz sprzedaż online:

### 1. Strona Główna (`/`)
* **Sekcja Hero**: Przyciągający uwagę nagłówek z manifestem artystycznym oraz prezentacją flagowego portretu olejnego namalowanego ze zdjęcia.
* **O Mnie & Biografia**: Sekcja prezentująca sylwetkę artysty, jego pasję do sztuki tradycyjnej oraz powiązanie pracy teatralnej z malarstwem.
* **Kalkulator & Formularz Zleceń Indywidualnych**: Interaktywny formularz zamawiania portretów na zamówienie ze zdjęcia (możliwość załączenia plików referencyjnych, wyboru formatu, techniki i opisu wizji).
* **Karuzela Produktów**: dynamiczne slajdy z gotowymi pracami z autorskiej kolekcji.
* **Sekcja Opinii Klienckich (`Testimonials.tsx`)**: opinie ze zdjęciami, gwiazdkami i opisami dociągane bezpośrednio z panelu CMS (`GET /api/reviews/site`).
* **Sekcja Często Zadawanych Pytań FAQ (`FaqSection.tsx`)**: interaktywny akordeon z pytaniami i odpowiedziami dotyczącymi zamawiania prac, pakowania, oprawy oraz dostawy, zarządzany w pełni z panelu CMS z obsługą formatowania tekstu WYSIWYG (`GET /api/faq`).

### 2. Sklep z Pracami Gotowymi (`/sklep`)
* **Katalog Produktów**: przejrzysta prezentacja gotowych obrazów, akwareli oraz rysunków.
* **Filtrowanie**: wygodne filtrowanie prac według kategorii (Wszystkie, Akwarele, Rysunki, Wydruki).
* **Szczegóły Produktu (`/sklep/[slug]`)**: karta dzieła ze szczegółowym opisem technicznym, wymiarami, galerią zdjęć oraz wariantami zakupu.

### 3. Galeria Prac & Portfolio (`/galeria`)
* **Archiwum Dzieł Sztuki**: bogata kolekcja dotychczas stworzonych prac.
* **Filtrowanie Techniczne**: sortowanie według techniki (olej, akryl, akwarela, rysunek) oraz roku powstania.
* **Pełnoekranowy Lightbox**: intuicyjny podgląd wybranego obrazu ze szczegółami i sterowaniem z klawiatury.

### 4. Podstrony Prawne (`/regulamin` & `/polityka-prywatnosci`)
* **Dynamiczny Edytor Paragrafowy**: treść każdej sekcji i paragrafu jest zarządzana z poziomu panelu CMS (Filament `Repeater` z wizualnym edytorem WYSIWYG).
* **Automatyczna Data Aktualizacji**: data ostatniej modyfikacji w nagłówku strony odświeża się automatycznie po zmianie treści w CMS (`last_updated_formatted`).
* **Dynamiczny Spis Treści**: spis treści i kotwice nawigacyjne na stronie dostosowują się na żywo do sekcji zdefiniowanych w CMS.

---

## 🛠️ Stack Technologiczny

### Frontend
- **Framework**: [Astro v7](https://astro.build/) (Static Site Generation - SSG, ultraszybkie ładowanie stron).
- **Komponenty UI**: [React v19](https://react.dev/) (interaktywne formularze, akordeon FAQ, koszyk, lightbox).
- **Stylizowanie**: [Tailwind CSS v4](https://tailwindcss.com/) + customowe animacje w Vanilla CSS.
- **Ikony i Animacje**: Lucide React & Motion (Framer Motion).
- **Stan Globalny**: Nanostores (`cartStore.ts` z zapisem w `localStorage`).

### Backend & CMS
- **Framework Backendowy**: [Laravel 13](https://laravel.com/) (bezstanowe REST API).
- **Panel Administracyjny**: [Filament CMS v5](https://filamentphp.com/) (zarządzanie sklepem, obrazami, opiniami, pytaniami FAQ, plikami i podstronami prawnymi).
- **Baza Danych**: SQLite (lokalnie) / MySQL (produkcja).

---

## 📁 Struktura Repozytorium

```text
hello-kostek-dev/
├── backend/                        # Kod backendu Laravel + Filament CMS
│   ├── app/
│   │   ├── Filament/Resources/     # Moduły CMS (ContentPages, FaqItems, GalleryArtwork, Media, ProductReview)
│   │   ├── Http/Controllers/Api/   # Endpointy REST API (/api/faq, /api/reviews, /api/content, /api/inquiries)
│   │   └── Models/                 # Modele danych Eloquent
│   ├── database/
│   │   ├── migrations/             # Migracje bazy danych
│   │   └── seeders/
│   │       └── HelloKostekSeeder.php # Główny seeder produkcyjny danych Hello Kostek
│   └── routes/api.php              # Definicje tras API
├── src/                            # Kod źródłowy frontendu Astro / React
│   ├── assets/                     # Grafiki i zdjęcia prac
│   ├── components/                 # Komponenty React (FaqSection.tsx, Terms.tsx, PrivacyPolicy.tsx, Home.tsx, etc.)
│   ├── layouts/                    # Szablony stron (Layout.astro)
│   └── pages/                      # Trasy i podstrony Astro (/index.astro, /galeria.astro, /sklep, /regulamin)
├── package.json                    # Skrypty i zależności frontendu Astro
└── README.md                       # Główna dokumentacja repozytorium
```

---

## 💻 Komendy i Uruchamianie Lokalnie

### 1. Uruchomienie Backend API & CMS (Laravel)
```bash
cd backend
php artisan migrate:fresh --seed --force
php artisan serve --port=8000
```
- **Panel CMS Filament**: `http://localhost:8000/admin`
- **Dane logowania dev**: `admin@hellokostek.pl` / `Admin1234!`

### 2. Uruchomienie Frontendu (Astro)
```bash
# z głównego katalogu:
npm install
npm run dev
```
Domyślny adres lokalny: **`http://127.0.0.1:4321`** (lub `http://localhost:4321`).

---

## 🌐 Serwer Produkcyjny (LH.pl)

- **Host**: `serwer69908.lh.pl` (Port SSH: 22, Port FTP: 21)
- **Katalog podstrony panelu CMS**: `/public_html/admin.hellokostek.pl/`
- **Katalog publiczny CMS**: `/public_html/admin.hellokostek.pl/public/`
- **Katalog główny sklepu/frontendu**: `/public_html/hellokostek.pl/`
