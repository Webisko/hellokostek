# Plan Wdrożenia na Serwer Produkcyjny SEOHost (h93.seohost.pl)

## Kontekst i Stan Obecny
- **Commit i GitHub Push:** Zmiany zostały już pomyślnie zakommitowane (`6c87841`) i wypchnięte do repozytorium GitHub na branch `main`.
- **Zweryfikowane połączenie SSH:** Nawiązano bezpieczne połączenie SSH z serwerem `h93.seohost.pl` (użytkownik `srv124983`, port `57185`) przy użyciu lokalnego klucza `id_rsa_seohost`.
- **Rzeczywista struktura na serwerze:**
  - Frontend (Astro Storefront): `/home/srv124983/domains/hellokostek.pl/public_html/`
  - Backend (Laravel API & Filament): `/home/srv124983/domains/hellokostek.pl/backend/`
  - Subdomena panelu (`panel.hellokostek.pl/public_html`): symlink do `backend/public`

---

## Proponowany Zakres Wdrożenia

### Krok 1: Wdrożenie Frontendu (Astro Storefront)
- Skopiowanie zawartości lokalnego katalogu `dist/` (świeżo zbudowanego i zweryfikowanego) do `/home/srv124983/domains/hellokostek.pl/public_html/`.
- **Ochrona zasobów serwera:** Wykluczenie ze zmian plików i symlinków produkcyjnych:
  - `.htaccess`
  - `panel` (symlink do panelu CMS)
  - `cgi-bin`
  - `images/` (statyczne zasoby mediów)

### Krok 2: Wdrożenie Zmian w Backendzie (Laravel API)
- Skopiowanie zmodyfikowanych i oczyszczonych plików z `backend/` do `/home/srv124983/domains/hellokostek.pl/backend/`:
  - `app/` (oczyszczone kontrolery, usunięte martwe klasy mail/job)
  - `bootstrap/app.php` (rejestracja komend)
  - `config/session.php` (bezpieczeństwo sesji)
  - `database/seeders/` (usunięte obce seedery)
  - `routes/console.php` (rejestracja komend)
  - Dokumentacja (`API_REFERENCE.md`, `README.md`)
- **Ochrona środowiska serwera:** Bezwzględne zachowanie produkcyjnego `.env`, katalogu `storage/`, `vendor/` i `public/storage`.

### Krok 3: Wyczyszczenie i Przeładowanie Cache na Serwerze
Wykonanie przez SSH w katalogu `/home/srv124983/domains/hellokostek.pl/backend/`:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

### Krok 4: Weryfikacja Działania (Smoke Test)
- Test HTTP GET `https://hellokostek.pl/` (Storefront 200 OK)
- Test HTTP GET `https://hellokostek.pl/sitemap-index.xml` oraz `/sitemap.xml` (Brak błędu 500)
- Test API Health `https://panel.hellokostek.pl/api/health`

---

## Pytanie do Użytkownika / Bramka Decyzyjna
Czy zatwierdzasz powyższy plan wdrożenia na serwer produkcyjny SEOHost?
Po Twojej zgodzie przystąpię do synchronizacji plików i optymalizacji serwera.
