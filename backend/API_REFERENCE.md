# Dokumentacja Referencyjna API REST: Szablony Laravel Filament CMS

Niniejszy dokument stanowi referencję techniczną punktów końcowych (endpoints) API REST wystawianych przez szablony backendu w tym workspace. Dokumentacja ta ułatwia integrację frontendu (np. zbudowanego na bazie **Astro + React Islands**) z silnikiem CMS.

---

## 1. Architektura API i Konwencje

* **Format danych:** Wszystkie zapytania wysyłające dane w formacie JSON muszą posiadać nagłówek `Content-Type: application/json`. Odpowiedzi są zawsze zwracane jako JSON.
* **Autoryzacja (gdzie wymagana):** Wykorzystuje mechanizm **Laravel Sanctum**. Token należy przekazywać w nagłówku jako Bearer Token:
  ```http
  Authorization: Bearer <twój_token_sanctum>
  ```
* **Waluta i kwoty:** Wszystkie ceny i kwoty w API (np. w koszyku, zamówieniach, dostawie) są reprezentowane jako **liczby całkowite w najmniejszej jednostce walutowej** (np. grosze dla PLN, centy dla EUR/USD). Kwota `10000` oznacza `100,00 PLN`.

---

## 2. Wspólne Punkty Końcowe (Wszystkie Szablony)

Te punkty końcowe są dostępne w szablonach **Ecommerce, Ecommerce Premium, LMS oraz Services**.

### 2.1. Stan aplikacji (Health Check)
Służy do szybkiej weryfikacji zdrowia systemu przez frontend lub systemy monitorujące.
* **Adres:** `GET /api/health`
* **Autoryzacja:** Brak (dla gości zwraca minimalny status; dla zalogowanego admina zwraca pełne parametry systemowe).
* **Odpowiedź (Gość / 200 OK):**
  ```json
  {
    "status": "ok",
    "app": "Nazwa Aplikacji"
  }
  ```

### 2.2. Ustawienia globalne (Store Settings)
Pobieranie konfiguracji wyświetlania, wbudowanych skryptów marketingowych (Pixel, GA) oraz ustawień banera RODO.
* **Adres:** `GET /api/store/settings`
* **Odpowiedź (200 OK):**
  ```json
  {
    "store_name": "Moja Firma",
    "currency": "PLN",
    "free_shipping_threshold": 25000,
    "allow_guest_checkout": true,
    "cookie_banner_enabled": true,
    "google_tag_manager_id": "GTM-XXXXXXX",
    "google_analytics_id": "G-XXXXXXXXX",
    "facebook_pixel_id": "1234567890",
    "cookie_banner_title": "Szanujemy Twoją prywatność",
    "cookie_banner_description": "Używamy plików cookie w celach statystycznych i marketingowych...",
    "custom_head_scripts": null,
    "announcement_enabled": true,
    "announcement_text": "Darmowa wysyłka od 250 PLN!",
    "global_noindex": false,
    "maintenance_mode_enabled": false,
    "maintenance_mode_message": "Strona w budowie."
  }
  ```

### 2.3. Zapisywanie zgód RODO (Cookie Consent)
Rejestrowanie zgód użytkownika na pliki cookies w bazie danych (wymóg prawny UODO/RODO).
* **Adres:** `POST /api/cookie-consents`
* **Zapytanie (Payload JSON):**
  ```json
  {
    "consent_token": "usr_7x8y9z...",
    "consent_choices": {
      "necessary": true,
      "analytics": true,
      "functional": false,
      "marketing": false
    },
    "banner_version": "1.0.0"
  }
  ```
* **Odpowiedź (201 Created):** Zwraca zapisany rekord zgody wraz ze znacznikiem czasu.

### 2.4. Zapis do Newslettera (Double Opt-In)
Rejestruje subskrybenta o statusie `pending` i automatycznie wysyła na jego e-mail link potwierdzający.
* **Adres:** `POST /api/newsletter/subscribe`
* **Zapytanie (Payload JSON):**
  ```json
  {
    "email": "klient@example.com",
    "first_name": "Jan",
    "last_name": "Kowalski",
    "source": "footer"
  }
  ```
* **Odpowiedź (201 Created):**
  ```json
  {
    "data": {
      "subscriber": {
        "id": 1,
        "email": "klient@example.com",
        "status": "pending",
        "is_active": false,
        "consented_at": null
      }
    }
  }
  ```

### 2.5. Podstrony i Blog (CMS)
Pobieranie stron statycznych i artykułów blogowych zdefiniowanych w panelu Filament.
* **Lista stron:** `GET /api/content/pages`
* **Pojedyncza strona:** `GET /api/content/pages/{slug}`
* **Lista wpisów blogowych:** `GET /api/blog/posts`
* **Pojedynczy wpis:** `GET /api/blog/posts/{slug}`
* **Lista FAQ:** `GET /api/faq`
* **Mapa linków (struktura):** `GET /api/content/map`

---

## 3. Punkty Końcowe specyficzne dla Ecommerce / LMS

Dostępne tylko w szablonach **Ecommerce, Ecommerce Premium** oraz **LMS**.

### 3.1. Katalog produktów
* **Lista produktów z filtrami:** `GET /api/catalog`
  * Parametry query: `?category={slug}&sort={field}&search={query}`
* **Szczegóły produktu:** `GET /api/catalog/products/{slug}`
  * Zwraca dane produktu (w tym wagę, oznaczenie AI Act, kod HS, flagę dropshippingu, pełne dane GPSR i kompatybilność cyfrową), zdjęcia z galerii, atrybuty, opinie oraz najniższą cenę z ostatnich 30 dni (Omnibus).

### 3.2. Wyliczenie cen koszyka (Checkout Draft)
Kalkulator koszyka. Uruchamia silnik cenowy, nalicza rabaty z kuponów, wylicza stawkę VAT OSS dla kraju dostawy w UE oraz koszty transportu. **Nie modyfikuje stanu bazy danych.**
* **Adres:** `POST /api/checkout/draft`
* **Zapytanie (Payload JSON):**
  ```json
  {
    "items": [
      { "slug": "t-shirt-red", "quantity": 2 }
    ],
    "shipping_method_code": "inpost_courier",
    "shipping_country_code": "DE",
    "coupon_code": "SUMMER10"
  }
  ```
* **Odpowiedź (200 OK):**
  ```json
  {
    "subtotal_amount": 20000,
    "coupon_discount_amount": 2000,
    "shipping_amount": 1900,
    "import_duty_amount": 0,
    "total_amount": 19900,
    "free_shipping_applied": false,
    "available_payment_methods": ["stripe", "przelewy24"]
  }
  ```

### 3.3. Złożenie zamówienia i Płatność
* **Adres:** `POST /api/checkout/place`
* **Zapytanie (Payload JSON):**
  ```json
  {
    "items": [
      { "slug": "t-shirt-red", "quantity": 2 }
    ],
    "payment_method": "stripe",
    "customer": {
      "email": "jan@kowalski.pl",
      "first_name": "Jan",
      "last_name": "Kowalski",
      "company_name": null,
      "nip": null,
      "wants_invoice": false
    },
    "shipping_address": {
      "street": "Wiejska 10",
      "city": "Warszawa",
      "postal_code": "00-001",
      "country_code": "PL"
    },
    "terms_accepted": true
  }
  ```
* **Odpowiedź (201 Created):**
  ```json
  {
    "order_number": "ORD-20260629-ABCDE",
    "status": "placed",
    "payment_status": "awaiting_payment",
    "payment_url": "https://checkout.stripe.com/c/pay/cs_test_...",
    "total_amount": 19900
  }
  ```
  *Uwaga: Otrzymany `payment_url` należy otworzyć w przeglądarce klienta, aby dokończyć transakcję.*

### 3.4. Szczegóły zamówienia (Checkout Order Show)
Pobieranie szczegółowych informacji o wybranym zamówieniu na potrzeby strony podziękowania (Thank You Page) lub historii zakupów.
* **Adres:** `GET /api/checkout/orders/{number}`
* **Weryfikacja tożsamości:** 
  * Dla użytkowników zalogowanych: Użytkownik musi być właścicielem zamówienia (zgodność `user_id`) lub administratorem.
  * Dla gości (niezalogowanych): Wymagane jest przesłanie adresu e-mail powiązanego z zamówieniem w nagłówku `X-Order-Email` lub w parametrze zapytania `?email={customer_email}`.
* **Odpowiedź (200 OK):**
  ```json
  {
    "data": {
      "order": {
        "id": 4,
        "number": "ORD-20260629-ABCDE",
        "status": "placed",
        "payment_status": "paid",
        "fulfillment_status": "pending",
        "currency": "PLN",
        "total_amount": 19900,
        "customer_email": "jan@kowalski.pl",
        "items": [
          {
            "id": 12,
            "sku": "t-shirt-red",
            "name": "T-Shirt Red",
            "quantity": 2,
            "total_amount": 18000
          }
        ]
      }
    }
  }
  ```

### 3.5. Elektroniczne odstąpienie od umowy i zwrot (Order Return / RMA)
Umożliwia konsumentom zgłoszenie zwrotu zakupionych towarów drogą elektroniczną (zgodnie z dyrektywą Omnibus oraz polską Ustawą o prawach konsumenta). Zwrot można zgłosić dla zamówień w statusie `placed`, `shipped` lub `completed`, które zostały opłacone (`payment_status = paid`) lub wybrano dla nich płatność za pobraniem (COD).

**Zabezpieczenie RMA (Double Refund):** System automatycznie weryfikuje dostępną do zwrotu ilość dla każdej pozycji zamówienia. Suma dotychczas zgłoszonych (nieodrzuconych) zwrotów jest odejmowana od zakupionej ilości. Próba zgłoszenia ilości przekraczającej ten bilans skutkuje błędem `422 Unprocessable Content`.
* **Adres:** `POST /api/returns`
* **Zapytanie (Payload JSON):**
  ```json
  {
    "order_number": "ORD-20260629-ABCDE",
    "customer_email": "jan@kowalski.pl",
    "items": [
      {
        "order_item_id": 12,
        "quantity": 1
      }
    ]
  }
  ```
* **Odpowiedź (201 Created):**
  ```json
  {
    "success": true,
    "message": "Zgłoszenie zwrotu zostało pomyślnie zarejestrowane.",
    "return_number": "RET-20260629-XYZ"
  }
  ```

### 3.6. Pobieranie danych firmy z GUS (B2B)
Służy do szybkiej weryfikacji i autouzupełniania danych do faktury w checkoucie.
* **Adres:** `GET /api/checkout/b2b/gus/{nip}`
* **Odpowiedź (200 OK):** Zwraca pełną nazwę rejestrową firmy, REGON oraz adres pobrany bezpośrednio z rejestru REGON Głównego Urzędu Statystycznego.

---

## 4. Punkty Końcowe specyficzne dla Services

Dostępne tylko w szablonie **Services** (`laravel-filament-services-boilerplate`).

### 4.1. Wysłanie formularza kontaktowego
Wysyła zapytanie ofertowe lub wiadomość do administratora witryny. Zapisuje zapytanie w bazie danych CMS, rejestruje log i wysyła e-mail powiadomienia do admina.
* **Adres:** `POST /api/inquiries`
* **Zapytanie (Payload JSON):**
  ```json
  {
    "name": "Jan Kowalski",
    "email": "jan@kowalski.pl",
    "phone": "+48 500 600 700",
    "subject": "Zapytanie o wycenę projektu",
    "message": "Dzień dobry, chciałbym zapytać o możliwość realizacji..."
  }
  ```
* **Odpowiedź (201 Created):**
  ```json
  {
    "success": true,
    "message": "Twoje zapytanie zostało wysłane pomyślnie.",
    "data": {
      "id": 12
    }
  }
  ```

---

## 5. Różnice w strukturze danych API (Zestawienie)

| Funkcjonalność | Ecommerce / LMS | Services |
| :--- | :--- | :--- |
| **Katalog & Ceny** | Dostępne (`/api/catalog`) | Brak |
| **Koszyk & Zamówienia** | Dostępne (`/api/checkout/*`) | Brak |
| **Konta Klientów (Auth)** | Zaimplementowane (Sanctum) | Brak (występuje tylko panel Admina) |
| **Formularz kontaktowy** | Opcjonalny (API wycen) | Dedykowany (`/api/inquiries`) |
| **Płatności online** | Stripe, Przelewy24, BLIK | Brak |
