# Pierwszy prompt dla Agenta Antigravity

Skopiuj poniższą treść i wklej ją jako pierwszą wiadomość do agenta po utworzeniu i otwarciu nowego projektu w IDE:

```markdown
Cześć! Skopiowałem ten boilerplate jako bazę pod nowy projekt.

Przejdź do pliku README.md i wykonaj dla mnie pełne przygotowanie techniczne projektu (Quick Start / Szybki Start) krok po kroku:
1. Skopiuj plik .env.example jako .env.
2. Zainstaluj zależności PHP za pomocą Composer i JavaScript za pomocą NPM (najlepiej uruchom `composer run setup`, jeśli jest dostępny, lub wykonaj kroki instalacyjne po kolei).
3. Wygeneruj klucz aplikacji (php artisan key:generate).
4. Skonfiguruj bazę danych SQLite (utwórz pusty plik database/database.sqlite na Windowsie).
5. Uruchom migracje i seedery (php artisan migrate --seed --seeder=DevCmsReviewSeeder).
6. Uruchom budowanie assetów frontendu (npm run build).
7. Po pomyślnym zakończeniu setupu, wygeneruj i zaktualizuj plik `.antigravity/schema.md` z aktualną strukturą tabel bazy danych, abym miał go jako referencję w tym nowym projekcie.

Wszystkie te komendy uruchom na moim systemie lokalnym (będę zatwierdzał uruchamiane polecenia). Daj znać, gdy skończysz, i podaj mi dane logowania do panelu administratora.
```
