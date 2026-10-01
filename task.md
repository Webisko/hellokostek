# Lista Zadań: Usunięcie Duplikatów Prac w Galerii

- [x] Krok 1: Oczyszczenie bazy danych na serwerze produkcyjnym SEOHost <!-- id: 0 -->
    - [x] Usunięcie 7 nadmiarowych rekordów o ID: 42, 43, 44, 45, 46, 47, 48 <!-- id: 1 -->
    - [x] Aktualizacja kanonicznych tytułów w rekordach 9, 10, 11, 12, 13, 21, 27 <!-- id: 2 -->
    - [x] Weryfikacja API (dokładnie 33 rekordy, brak duplikatów) <!-- id: 3 -->
- [x] Krok 2: Uporządkowanie seedera w Backendzie <!-- id: 4 -->
    - [x] Usunięcie podwójnej definicji galerii w `HelloKostekSeeder.php` <!-- id: 5 -->
    - [x] Weryfikacja testów backendu (`php artisan test`) <!-- id: 6 -->
- [x] Krok 3: Defensywna deduplikacja we frontendzie <!-- id: 7 -->
    - [x] Dodanie unikalności po pliku w `Gallery.tsx` <!-- id: 8 -->
    - [x] Build i wdrożenie frontendu na SEOHost <!-- id: 9 -->
- [x] Krok 4: Weryfikacja końcowa <!-- id: 10 -->
    - [x] Sprawdzenie w przeglądarce [https://hellokostek.pl/galeria](https://hellokostek.pl/galeria) <!-- id: 11 -->
