# Lista Kontrolna: 6-Stopniowy Audyt Projektu (project-audit-master)

## Profil Projektu i Inwentaryzacja Modułów (KROK 0)
- [x] **Architektura Bazowa:** Typ A (Pełny Stack: Laravel 13 + Filament v5.6 + Astro v7 + React Islands v19)
- **Aktywne Moduły Biznesowe w Projekcie:**
  - [x] Formularz kontaktowy / Wycena portretu na zamówienie (ze zdjęciem)
  - [x] Sklep: Produkty fizyczne (Oryginały dzieł 1/1, Reprodukcje kolekcjonerskie na papierze)
  - [x] Sklep: Koszyk i Checkout (Stripe, Przelewy24, BLIK, Przelew tradycyjny)
  - [x] Magazyn i rezerwacje dzieł unikatowych
  - [x] Dostawa: InPost Paczkomaty (interaktywna mapa Leaflet), Kurier ubezpieczony, Orlen Paczka
  - [x] B2B GUS BIR / Biała Lista VAT + Faktury DomPDF
  - [x] Newsletter / Zgody marketingowe
  - [x] Opinie klientów (Google Places snapshot + opinie lokalne)
  - [x] Zadania Cron w `routes/console.php` (zwalnianie rezerwacji, porzucone koszyki, sitemap)
  - [-] Platforma Szkoleniowa (LMS) — *nie dotyczy* (zgodnie z zasadą if-present)
  - [-] Wielojęzyczność (i18n) — *nie dotyczy celowo* (zgodnie z AGENTS.md serwis jest 100% jednojęzyczny: PL)
  - [-] Program Afiliacyjny / Lojalnościowy — *nie dotyczy*
  - [-] Agenci AI na froncie — *nie dotyczy* (motto artysty: „Sztuka prawdziwa bez AI”)

---

## 6 Stopni Audytu Inżynieryjnego
- [x] **STOPIEŃ 1: Cyberbezpieczeństwo Rdzenia, Anti-IDOR, Antyspam i Konfiguracja Serwera** <!-- id: 1 -->
    - Zweryfikowano brak IDOR, dysk prywatny faktur PDF, rate limiting, podwójny Honeypot oraz ochronę `.htaccess`.
- [x] **STOPIEŃ 2: Audyt Ścieżki Zakupowej: Koszyk Zero-Trust, Płatności, Magazyn, Rezerwacje i Dostawa InPost** <!-- id: 2 -->
    - Zweryfikowano zerowy trust cenowy frontendu, atomową rezerwację magazynu `lockForUpdate()`, integrację InPost Paczkomaty Leaflet z `formatCityName()` oraz poprawne parsowanie ulicy/budynku/lokalu dla kuriera.
- [x] **STOPIEŃ 3: Audyt Synchronizacji 5 Warstw (Baza ⇄ Model ⇄ Filament ⇄ API ⇄ Astro)** <!-- id: 3 -->
    - Zweryfikowano `nullOnDelete()` dla pozycji zamówień, spójność `$fillable` i `$casts`, ochronę `metadata` JSON, `reorderable('sort_order')` oraz odporność na pusty katalog w CMS bez warunków `items.length > 0`.
- [x] **STOPIEŃ 4: Audyt Modułów Dodatkowych (Newsletter, B2B GUS, Opinie, Zadania Cron)** <!-- id: 4 -->
    - Zweryfikowano dowody zgód marketingowych, integrację BIR GUS / MF Whitelist, snapshot opinii Google oraz `->withoutOverlapping()` w harmonogramie Cron.
- [x] **STOPIEŃ 5: Audyt Prawny PL/UE (Cookieless, RODO, Omnibus, GPSR), Mediów (.webp), SEO i UX/WCAG** <!-- id: 5 -->
    - Zweryfikowano lokalne fonty (brak Google Fonts CDN), symetryczny baner cookies, etykiety „Zamawiam i płacę”, najniższą cenę z 30 dni (Omnibus), stronę błędu `404.astro`, `noindex` oraz tagi OpenGraph i Schema.org.
- [x] **STOPIEŃ 6: Twarda Bramka Wykonawcza w Terminalu (`php artisan test` + `npm run build`)** <!-- id: 6 -->
    - `php artisan test`: 140 passed, 0 failed, 782 assertions.
    - `npm run build`: kod wyjścia 0, 23 strony wygenerowane w 3.40s.
