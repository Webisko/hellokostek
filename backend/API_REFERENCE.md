# Dokumentacja Referencyjna REST API – Hello Kostek

Oficjalna specyfikacja techniczna punktów końcowych (endpoints) REST API platformy internetowej i sklepu pracowni malarskiej **Hello Kostek (Maciej Kosteczka)**.

Dokumentacja opisuje integrację frontendu **Astro v7 + React v19** z silnikiem **Laravel 13 REST API**.

---

## 1. Architektura API i Standardy Komunikacji

* **Bazowy adres API:** `http://localhost:8000/api` (środowisko deweloperskie) / `https://admin.hellokostek.pl/api` (produkcja).
* **Format danych:** Wszystkie zapytania wysyłające dane JSON muszą zawierać nagłówek `Content-Type: application/json` oraz `Accept: application/json`. Odpowiedzi są zawsze zwracane w formacie JSON.
* **Jednostki kwot i waluta:** Wszystkie kwoty pieniężne (ceny obrazów, wydruków, koszty dostawy, rabaty) są reprezentowane jako **liczby całkowite w groszach (PLN)**. Przykładowo kwota `30000` oznacza `300,00 PLN`.
* **Autoryzacja (gdzie wymagana):** Laravel Sanctum. Token należy przesyłać w nagłówku:
  ```http
  Authorization: Bearer <twój_token_sanctum>
  ```
* **Ograniczenia przepustowości (Rate Limiting):**
  - Trasy ogólne: 60 zapytań / minutę per IP.
  - Formularze kontaktowe i wyceny (`/api/inquiries`, `/api/quote`, `/api/returns`): 20 zapytań / minutę per IP.
  - Checkout (`/api/checkout/*`): 30 zapytań / minutę per IP.
  - Autoryzacja (`/api/auth/login`, `/api/auth/register`): 5-10 zapytań / minutę per IP.

---

## 2. Stan Aplikacji, Ustawienia & Prawne (RODO)

### 2.1. Health Check
* **Adres:** `GET /api/health`
* **Zastosowanie:** Monitorowanie działania serwera przez systemy statusowe lub frontend.
* **Odpowiedź (200 OK):**
  ```json
  {
    "status": "ok",
    "app": "Hello Kostek"
  }
  ```

### 2.2. Ustawienia Sklepu (Store Settings)
* **Adres:** `GET /api/store/settings`
* **Odpowiedź (200 OK):**
  ```json
  {
    "store_name": "Hello Kostek",
    "currency": "PLN",
    "free_shipping_threshold": 50000,
    "allow_guest_checkout": true,
    "cookie_banner_enabled": true,
    "cookie_banner_title": "Szanujemy Twoją prywatność",
    "cookie_banner_description": "Używamy plików cookie w celach funkcjonalnych, analitycznych i marketingowych...",
    "google_analytics_id": "G-XXXXXXXXX",
    "google_tag_manager_id": "GTM-XXXXXXX",
    "facebook_pixel_id": null,
    "announcement_enabled": true,
    "announcement_text": "Darmowa przesyłka kurierska dla wszystkich oryginalnych obrazów olejnych!",
    "maintenance_mode_enabled": false
  }
  ```

### 2.3. Zapisywanie Zgód Cookies (Consent Log)
* **Adres:** `POST /api/cookie-consents`
* **Zastosowanie:** Spełnienie wymogów dowodowych RODO/PKE oraz obsługa Google Consent Mode v2.
* **Payload JSON:**
  ```json
  {
    "consent_token": "usr_c8f92a10b4...",
    "consent_choices": {
      "necessary": true,
      "analytics": true,
      "functional": true,
      "marketing": false
    },
    "banner_version": "1.0.0"
  }
  ```
* **Odpowiedź (201 Created):** Zwraca zarejestrowany rekord zgody z dokładnym znacznikiem czasu.

---

## 3. Katalog Dzieł Sztuki i Sklep

### 3.1. Lista Produktów (Katalog)
* **Adres:** `GET /api/catalog`
* **Parametry query:**
  - `category` (string, np. `olej`, `akwarela`, `akryl`, `rysunek`)
  - `sort` (string, np. `price_asc`, `price_desc`, `latest`)
  - `search` (string, np. `pejzaz`)
* **Odpowiedź (200 OK):** Zwraca paginowaną listę prac z przypisanymi wariantami (`-OR` dla oryginału, `-PR` dla reprodukcji), miniaturami WebP oraz najniższą ceną z 30 dni.

### 3.2. Podpowiedzi Wyszukiwania na Żywo (Live Suggest)
* **Adres:** `GET /api/catalog/search/suggest?query={tekst}`
* **Odpowiedź (200 OK):** Szybka lista maksymalnie 5-8 pasujących dzieł malarskich do wyświetlenia w wyszukiwarce.

### 3.3. Karta Dzieła Sztuki (Szczegóły Produktu)
* **Adres:** `GET /api/catalog/products/{slug}`
* **Zastosowanie:** Zasilenie widoku `/sklep/[id]` na frontendzie.
* **Odpowiedź (200 OK):**
  ```json
  {
    "data": {
      "id": 1,
      "slug": "obiekt-ii-2022",
      "name": "Obiekt II",
      "description": "Subtelna akwarela z cyklu badającego formę i relacje przestrzenne...",
      "regular_price_amount": 30000,
      "lowest_price_last_30_days": 30000,
      "featured_image_url": "https://admin.hellokostek.pl/storage/products/Wiecej-o-obiekcie-2-2022.webp",
      "categories": [
        { "slug": "akwarela", "name": "Akwarela" }
      ],
      "variants": [
        {
          "id": 10,
          "sku": "OBIEKT-II-OR",
          "name": "Oryginał dzieła (unikat 1 szt.)",
          "regular_price_amount": 30000,
          "stock_quantity": 1
        },
        {
          "id": 11,
          "sku": "OBIEKT-II-PR",
          "name": "Wydruk kolekcjonerski (papier archiwalny)",
          "regular_price_amount": 3000,
          "stock_quantity": 100
        }
      ],
      "metadata": {
        "year": "2022",
        "technique": "Akwarela na papierze bawełnianym",
        "dimensions": "30x40 cm",
        "is_ai_free": true
      }
    }
  }
  ```

### 3.4. Powiadomienia o Dostępności (Back in Stock)
* **Adres:** `POST /api/catalog/products/back-in-stock-subscribe`
* **Payload JSON:**
  ```json
  {
    "email": "klient@example.com",
    "product_id": 1,
    "product_variant_id": 10
  }
  ```

---

## 4. Portrety na Zamówienie & Formularze Kontaktowe

### 4.1. Wysłanie Zapytania / Wyceny Portretu ze Zdjęciem
* **Adres:** `POST /api/inquiries`
* **Format:** `multipart/form-data`
* **Pola formularza:**
  - `name` (wymagane, string): Imię i nazwisko zamawiającego.
  - `email` (wymagane, email): Adres e-mail do kontaktu.
  - `phone` (opcjonalne, string): Numer telefonu.
  - `subject` (opcjonalne, string): Temat zgłoszenia (np. `portrait_commission`).
  - `message` (wymagane, string): Opis wizji artystycznej, okazji (rocznica, prezent) lub wytycznych.
  - `shape` (opcjonalne, string): Rodzaj płótna (`prostokatne` lub `owalne`).
  - `size` (opcjonalne, string): Format podobrazia (np. `30x40`, `40x50`, `50x70`).
  - `files[]` (opcjonalne, pliki graficzne): Zdjęcia referencyjne twarzy lub pupila do namalowania.
* **Odpowiedź (200 OK):**
  ```json
  {
    "success": true,
    "message": "Twoje zapytanie zostało wysłane pomyślnie. Odpowiemy w ciągu 24-48 godzin.",
    "data": {
      "id": 15
    }
  }
  ```

### 4.2. Kalkulator Wyceny Koszyka (Quote)
* **Adres:** `POST /api/quote`
* **Zastosowanie:** Dynamiczne przeliczanie kwoty zlecenia lub koszyka z uwzględnieniem rabatów.
* **Payload JSON:**
  ```json
  {
    "items": [
      { "slug": "obiekt-ii-2022", "quantity": 1 }
    ],
    "shipping_method_code": "inpost",
    "coupon_code": "KOSTEK10"
  }
  ```

---

## 5. Kody Rabatowe (Coupons)

### 5.1. Walidacja Kodu Rabatowego
* **Adres:** `POST /api/coupons/validate`
* **Payload JSON:**
  ```json
  {
    "code": "RABAT10",
    "subtotal": 300.00
  }
  ```
* **Odpowiedź (200 OK):**
  ```json
  {
    "valid": true,
    "code": "RABAT10",
    "discount_amount": 30.00,
    "message": "Kupon został pomyślnie zastosowany!"
  }
  ```
* **Odpowiedź przy błędzie (422 Unprocessable Content):**
  ```json
  {
    "valid": false,
    "message": "Kod rabatowy stracił ważność lub osiągnął limit użyć."
  }
  ```

---

## 6. Proces Zakupowy (Checkout) i Płatności

### 6.1. Kalkulacja Koszyka przed Zakupem (Checkout Draft)
* **Adres:** `POST /api/checkout/draft`
* **Payload JSON:**
  ```json
  {
    "items": [
      { "slug": "obiekt-ii-2022", "quantity": 1, "variant_id": 10 }
    ],
    "shipping_method_code": "inpost",
    "coupon_code": "RABAT10"
  }
  ```

### 6.2. Złożenie Zamówienia (Checkout Place)
* **Adres:** `POST /api/checkout/place`
* **Payload JSON:**
  ```json
  {
    "items": [
      {
        "slug": "obiekt-ii-2022",
        "quantity": 1,
        "variant_id": 10,
        "product_variant_id": 10
      }
    ],
    "payment_method": "przelewy24",
    "shipping_method_code": "inpost",
    "coupon_code": "RABAT10",
    "terms_accepted": true,
    "customer": {
      "email": "jan@kowalski.pl",
      "first_name": "Jan",
      "last_name": "Kowalski",
      "phone": "+48 501 234 567",
      "company_name": null,
      "nip": null,
      "wants_invoice": false
    },
    "shipping_address": {
      "street": "Paczkomat KOS01M",
      "city": "Łódź",
      "postal_code": "90-001",
      "country_code": "PL"
    }
  }
  ```
* **Odpowiedź (201 Created):**
  ```json
  {
    "order_number": "ORD-20260923-9821",
    "status": "placed",
    "payment_status": "awaiting_payment",
    "payment_url": "https://secure.przelewy24.pl/trnRequest/...",
    "total_amount": 28500
  }
  ```

### 6.3. Szczegóły Zamówienia (Thank You Page)
* **Adres:** `GET /api/checkout/orders/{number}`
* **Nagłówek weryfikacyjny (dla gości):** `X-Order-Email: jan@kowalski.pl` lub parametr `?email=jan@kowalski.pl`.

### 6.4. Ponowienie Sesji Płatności
* **Adres:** `POST /api/checkout/orders/{number}/payment-session`

---

## 7. Weryfikacja Danych Firmowych B2B (GUS BIR)

* **Adres:** `GET /api/b2b/gus/{nip}`
* **Opis:** Weryfikuje numer NIP i automatycznie pobiera pełną nazwę rejestrową firmy, numer REGON oraz adres siedziby z rejestru Głównego Urzędu Statystycznego.
* **Odpowiedź (200 OK):**
  ```json
  {
    "success": true,
    "data": {
      "name": "PRZYKŁADOWA PRACOWNIA SP. Z O.O.",
      "nip": "6252363656",
      "regon": "527158196",
      "street": "Rynek",
      "building_number": "33",
      "city": "Siewierz",
      "postal_code": "42-470"
    }
  }
  ```

---

## 8. Elektroniczne Odstąpienie od Umowy i Zwroty (RMA)

* **Adres:** `POST /api/returns`
* **Zastosowanie:** Zgodność z unijną dyrektywą 2023/2673 dotyczącą umów zawieranych na odległość.
* **Payload JSON:**
  ```json
  {
    "order_number": "ORD-20260923-9821",
    "customer_email": "jan@kowalski.pl",
    "items": [
      {
        "order_item_id": 42,
        "quantity": 1
      }
    ]
  }
  ```
* **Odpowiedź (201 Created):**
  ```json
  {
    "success": true,
    "message": "Zgłoszenie zwrotu zostało pomyślnie zarejestrowane. Potwierdzenie wysłano na e-mail.",
    "return_number": "RET-20260923-01"
  }
  ```

---

## 9. Treści CMS, Galeria, FAQ i Opinie

### 9.1. Galeria Dzieł w Portfolio
* **Adres:** `GET /api/gallery`
* **Odpowiedź (200 OK):** Zwraca listę wszystkich dzieł z podziałem na techniki i roczniki (do widoku `/galeria`).

### 9.2. Baza Pytań i Odpowiedzi (FAQ)
* **Adres:** `GET /api/faq` (lub alternatywnie `/api/pytania-i-odpowiedzi`)
* **Odpowiedź (200 OK):** Lista pytań i sformatowanych odpowiedzi HTML wraz ze znacznikami Schema.org `FAQPage`.

### 9.3. Strony Prawne z Blokami Paragrafowymi
* **Adres:** `GET /api/content/pages/{slug}` (np. `/api/content/pages/regulamin` lub `/polityka-prywatnosci`)
* **Odpowiedź (200 OK):**
  ```json
  {
    "data": {
      "page": {
        "title": "Regulamin Sklepu",
        "last_updated_formatted": "23 września 2026",
        "sections": [
          {
            "id": "postanowienia-ogolne",
            "label": "§ 1. Postanowienia ogólne",
            "content": "<p>Niniejszy Regulamin określa zasady korzystania ze sklepu...</p>"
          }
        ]
      }
    }
  }
  ```

### 9.4. Opinie Ogólne o Pracowni (Strona Główna)
* **Adres:** `GET /api/reviews/site`
* **Odpowiedź (200 OK):** Zaakceptowane opinie z ocenami, cytatami, emoji i awatarami.

---

## 10. Webhooki Integracyjne (Płatności i Logistyka)

* `POST /api/integrations/stripe/payment-callback` – webhook potwierdzający transakcje kartowe Stripe.
* `POST /api/integrations/przelewy24/payment-callback` – webhook potwierdzający transakcje Przelewy24 / BLIK.
* `POST /api/integrations/baselinker/shipment-callback` – webhook aktualizacji statusów przesyłek z systemu BaseLinker.
