# Dokumentacja Referencyjna REST API – Hello Kostek

Oficjalna specyfikacja techniczna punktów końcowych (endpoints) REST API platformy internetowej i sklepu pracowni malarskiej **Hello Kostek (Maciej Kosteczka)**.

Dokumentacja opisuje integrację frontendu **Astro v7 + React v19** z silnikiem **Laravel 13 REST API**.

---

## 1. Architektura API i Standardy Komunikacji

* **Bazowy adres API:** `http://localhost:8000/api` (środowisko deweloperskie) / `https://panel.hellokostek.pl/api` (produkcja).
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
  - Autoryzacja (`/api/auth/login`, `/api/auth/register`): 5–10 zapytań / minutę per IP.
  - Zbieranie zdarzeń (`/api/analytics/events`): 60 zapytań / minutę per IP.

---

## 2. Stan Aplikacji, Ustawienia, Prawne i Diagnostyka

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

### 2.4. Rozwiązywanie Przekierowań (Redirects Resolver)
* **Adres:** `GET /api/redirects/resolve?path={sciezka}`
* **Zastosowanie:** Sprawdzenie przez routing Astro, czy dany adres URL posiada aktywne przekierowanie 301 lub 302 zarejestrowane w CMS.
* **Odpowiedź (200 OK):**
  ```json
  {
    "has_redirect": true,
    "destination": "/sklep/obiekt-ii-2022",
    "status_code": 301
  }
  ```

### 2.5. Zbieranie Zdarzeń Analitycznych
* **Adres:** `POST /api/analytics/events`
* **Rate limit:** 60 / minutę per IP.
* **Payload JSON:**
  ```json
  {
    "event_name": "view_item",
    "page_url": "/sklep/obiekt-ii-2022",
    "metadata": {
      "product_id": 1,
      "category": "akwarela"
    }
  }
  ```
* **Odpowiedź (202 Accepted):** `{"status": "queued"}`

### 2.6. Diagnostyka Formatów WebP
* **Adres:** `GET /api/debug/webp-status`
* **Zastosowanie:** Weryfikacja integralności i stopnia konwersji plików graficznych na dysku publicznym.

---

## 3. Katalog Dzieł Sztuki, Asortyment i Recenzje

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
      "featured_image_url": "https://panel.hellokostek.pl/storage/products/Wiecej-o-obiekcie-2-2022.webp",
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

### 3.4. Rekomendacje Produktowe
* **Adres:** `GET /api/catalog/products/{slug}/recommendations`
* **Odpowiedź (200 OK):** Zwraca listę powiązanych dzieł z tej samej kategorii lub okresu twórczego.

### 3.5. Sprawdzanie Stanu Magazynowego SKU
* **Adres:** `GET /api/inventory/{sku}`
* **Odpowiedź (200 OK):**
  ```json
  {
    "data": {
      "sku": "OBIEKT-II-OR",
      "slug": "obiekt-ii-2022",
      "name": "Obiekt II",
      "quantity": 1,
      "is_available": true
    }
  }
  ```

### 3.6. Opinie i Recenzje Produktu
* **Pobranie opinii:** `GET /api/catalog/products/{slug}/reviews`
* **Dodanie opinii:** `POST /api/catalog/products/{slug}/reviews`
* **Payload dodawania recenzji:**
  ```json
  {
    "customer_name": "Anna Nowak",
    "customer_email": "anna@example.pl",
    "rating": 5,
    "comment": "Obraz na żywo prezentuje się zjawiskowo, pociągnięcia pędzla i faktura robią ogromne wrażenie!"
  }
  ```
* **Odpowiedź (201 Created):** Opinia zostaje zapisana i oczekuje na moderację w panelu Filament CMS.

### 3.7. Powiadomienia o Dostępności (Back in Stock)
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

## 4. Serwerowy Koszyk (Server-side Cart)

Dla zachowania spójności koszyka między urządzeniami lub po zalogowaniu, system udostępnia pełne API koszyka z obsługą nagłówka `X-Cart-Session-Token`:

* **Pobranie koszyka:** `GET /api/cart`
* **Dodanie elementu:** `POST /api/cart/items`
  ```json
  {
    "product_id": 1,
    "product_variant_id": 10,
    "quantity": 1
  }
  ```
* **Aktualizacja ilości:** `PUT /api/cart/items/{itemId}`
  ```json
  {
    "quantity": 2
  }
  ```
* **Usunięcie pozycji:** `DELETE /api/cart/items/{itemId}`

---

## 5. Portrety na Zamówienie & Formularze Kontaktowe

### 5.1. Wysłanie Zapytania / Wyceny Portretu ze Zdjęciem
* **Adres:** `POST /api/inquiries`
* **Format:** `multipart/form-data`
* **Rate limit:** 20 / minutę per IP.
* **Pola formularza:**
  - `name` (wymagane, string): Imię i nazwisko zamawiającego.
  - `email` (wymagane, email): Adres e-mail do kontaktu.
  - `phone` (opcjonalne, string): Numer telefonu.
  - `subject` (opcjonalne, string): Temat zgłoszenia (np. `portrait_commission`).
  - `message` (wymagane, string): Opis wizji artystycznej, okazji lub wytycznych.
  - `shape` (opcjonalne, string): Rodzaj płótna (`prostokatne` lub unikalne `owalne`).
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

### 5.2. Kalkulator Wyceny Koszyka (Quote)
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

## 6. Kody Rabatowe (Coupons)

### 6.1. Walidacja Kodu Rabatowego
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

---

## 7. Proces Zakupowy (Checkout) i Płatności

### 7.1. Kalkulacja Koszyka przed Zakupem (Checkout Draft)
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

### 7.2. Złożenie Zamówienia (Checkout Place)
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

### 7.3. Szczegóły Zamówienia (Thank You Page)
* **Adres:** `GET /api/checkout/orders/{number}`
* **Nagłówek weryfikacyjny (dla gości):** `X-Order-Email: jan@kowalski.pl` lub parametr `?email=jan@kowalski.pl`.

### 7.4. Ponowienie Sesji Płatności
* **Adres:** `POST /api/checkout/orders/{number}/payment-session`

---

## 8. Weryfikacja Danych Firmowych B2B (GUS BIR)

* **Adres:** `GET /api/b2b/gus/{nip}`
* **Opis:** Weryfikuje numer NIP i automatycznie pobiera pełną nazwę rejestrową firmy, numer REGON oraz adres siedziby z bazy Głównego Urzędu Statystycznego.

---

## 9. Elektroniczne Odstąpienie od Umowy i Zwroty (RMA)

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
* **Odpowiedź (201 Created):** Zwraca numer zwrotu `return_number` oraz generuje powiadomienie e-mail z instrukcją bezpiecznego odesłania dzieła.

---

## 10. Treści CMS, Galeria, FAQ i Opinie

### 10.1. Mapa Struktury Treści
* **Adres:** `GET /api/content/map`
* **Odpowiedź (200 OK):** Zwraca drzewo podstron, kategorie, grupy FAQ oraz powiązane zasoby dla nawigacji frontendu.

### 10.2. Lista Podstron CMS
* **Adres:** `GET /api/content/pages`

### 10.3. Strony Prawne z Blokami Paragrafowymi
* **Adres:** `GET /api/content/pages/{slug}` (np. `/api/content/pages/regulamin` lub `/polityka-prywatnosci`)
* **Odpowiedź (200 OK):** Zwraca tytuł, sformatowaną datę modyfikacji oraz tablicę bloków sekcji `sections` z unikalnymi identyfikatorami kotwic `id` i sformatowanym HTML.

### 10.4. Galeria Dzieł w Portfolio
* **Adres:** `GET /api/gallery`
* **Odpowiedź (200 OK):** Pełna lista dzieł z podziałem na techniki i roczniki.

### 10.5. Baza Pytań i Odpowiedzi (FAQ)
* **Adres:** `GET /api/faq` (lub alternatywnie `/api/pytania-i-odpowiedzi`)

### 10.6. Opinie Ogólne o Pracowni (Strona Główna)
* **Adres:** `GET /api/reviews/site`

---

## 11. Autentykacja Klientów (Sanctum)

Dla zarejestrowanych klientów sklep udostępnia bezpieczne logowanie oparte o tokeny Bearer:

* **Rejestracja:** `POST /api/auth/register`
  - Pola: `name`, `email`, `password`, `password_confirmation`.
* **Logowanie:** `POST /api/auth/login`
  - Pola: `email`, `password`.
  - Odpowiedź zwraca token: `{"data": {"token": "1|xyz...", "user": {...}}}`.
* **Wylogowanie:** `POST /api/auth/logout` (wymaga `Authorization: Bearer <token>`).
* **Przypomnienie hasła:** `POST /api/auth/forgot-password` (`email`).
* **Reset hasła:** `POST /api/auth/reset-password` (`token`, `email`, `password`, `password_confirmation`).
* **Weryfikacja e-mail:** `GET /api/auth/email/verify/{id}/{hash}` (podpisany link).
* **Ponowna wysyłka linku:** `POST /api/auth/email/resend`.

---

## 12. Strefa Klienta (Konto Zalogowanego Użytkownika)

Wszystkie poniższe trasy wymagają nagłówka `Authorization: Bearer <token>`:

* **Profil użytkownika:** `GET /api/account/me`
* **Historia zamówień:** `GET /api/account/orders`
* **Książka adresowa:**
  - `GET /api/account/addresses` – lista zapisanych adresów dostawy i faktury.
  - `POST /api/account/addresses` – dodanie nowego adresu.
  - `PUT /api/account/addresses/{id}` – aktualizacja adresu.
  - `DELETE /api/account/addresses/{id}` – usunięcie adresu.
* **Lista życzeń (Wishlist):**
  - `GET /api/account/wishlist` – ulubione dzieła sztuki.
  - `POST /api/account/wishlist` – dodanie do ulubionych (`product_id`).
  - `DELETE /api/account/wishlist/{productId}` – usunięcie z ulubionych.
* **Historia zwrotów RMA:**
  - `GET /api/account/returns` – lista zgłoszeń odstąpienia od umowy.
  - `GET /api/account/returns/{id}` – szczegóły i status danego zwrotu.

---

## 13. Webhooki Integracyjne (Płatności i Logistyka)

* `POST /api/integrations/stripe/payment-callback` – webhook potwierdzający transakcje kartowe Stripe.
* `POST /api/integrations/przelewy24/payment-callback` – webhook potwierdzający transakcje Przelewy24 / BLIK.
* `POST /api/integrations/baselinker/shipment-callback` – webhook aktualizacji statusów przesyłek z systemu BaseLinker.
