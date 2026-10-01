# Plan Naprawy: Usunięcie zduplikowanych prac w galerii

## 1. Zdiagnozowana Przyczyna Źródłowa (Root Cause Analysis)

W API produkcyjnym (`https://panel.hellokostek.pl/api/gallery`) oraz w bazie danych znajduje się **40 prac zamiast 33**.
Dokładna analiza wykazała, że **7 prac występuje podwójnie** z identycznymi plikami graficznymi, ale z nieco różniącymi się tytułami:

1. **`tequila-whole-edited-1.webp`**:
   - `id=gallery-9`: *„Portret psa 'Tequila'”* (sort_order: 70)
   - `id=gallery-42`: *„Portret psa Tequili”* (sort_order: 80)
2. **`Portret-Franka-23-scaled-e1689869410746.webp`**:
   - `id=gallery-10`: *„Portret Franka”* (sort_order: 80)
   - `id=gallery-43`: *„Portret małego Franka”* (sort_order: 90)
3. **`20231206_012611.webp`**:
   - `id=gallery-11`: *„Portret uśmiechniętej dziewczynki”* (sort_order: 90)
   - `id=gallery-44`: *„Portret kobiety z uśmiechem”* (sort_order: 100)
4. **`20231216_142134-edited.webp`**:
   - `id=gallery-12`: *„Portret dojrzałej kobiety w profilu”* (sort_order: 100)
   - `id=gallery-45`: *„Portret dziewczyny z profilu”* (sort_order: 110)
5. **`MBOne020-edited.webp`**:
   - `id=gallery-13`: *„Portret kobiety - profil”* (sort_order: 110)
   - `id=gallery-46`: *„Portret dojrzałego mężczyzny z brodą”* (sort_order: 120)
6. **`13a-513W13R2-edited.webp`**:
   - `id=gallery-21`: *„Portret pfsa z kwiatami (profil)”* (literówka „pfsa”, sort_order: 180)
   - `id=gallery-47`: *„Portret psa z kwiatami (profil)”* (sort_order: 190)
7. **`podwojny-02-edited-1.webp`**:
   - `id=gallery-27`: *„Portret podwójny children”* (angielskie „children”, sort_order: 210)
   - `id=gallery-48`: *„Portret podwójny dzieci”* (sort_order: 220)

Ponieważ duplikaty te posiadają zbliżoną kolejność wyświetlania (`sort_order`), na stronie pojawiają się bezpośrednio obok siebie w głównym widoku siatki (np. dwa portrety Franka, dwa portrety psa Tequili itd.), stwarzając wrażenie powtórzenia całej galerii.

Przyczyna: W pliku `HelloKostekSeeder.php` galeria była definiowana dwukrotnie: najpierw w Sekcji 3 (stare tytuły), a potem w Sekcji 7 przez `updateOrCreate(['title' => ...])` z nowymi tytułami. Przy innych tytułach funkcja nie zaktualizowała istniejących rekordów 9..27, lecz dodała nowe o ID 42..48.

---

## 2. Proponowany Plan Naprawy (3 Poziomy)

### Krok 1: Oczyszczenie bazy danych na serwerze produkcyjnym SEOHost
- Usunięcie 7 nadmiarowych rekordów o ID: `42, 43, 44, 45, 46, 47, 48`.
- Zaktualizowanie tytułów w oryginalnych rekordach `9, 10, 11, 12, 13, 21, 27` do kanonicznych, poprawnych wersji polskich (dokładnie takich jak w `src/data/gallery.ts`):
  - `9`: *„Portret psa Tequili”*
  - `10`: *„Portret małego Franka”*
  - `11`: *„Portret kobiety z uśmiechem”*
  - `12`: *„Portret dziewczyny z profilu”*
  - `13`: *„Portret dojrzałego mężczyzny z brodą”*
  - `21`: *„Portret psa z kwiatami (profil)”*
  - `27`: *„Portret podwójny dzieci”*
- Zresetowanie / uporządkowanie wag sortowania `sort_order` w bazie.

### Krok 2: Uporządkowanie seedera w Backendzie (`HelloKostekSeeder.php`)
- Plik: [backend/database/seeders/HelloKostekSeeder.php](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/database/seeders/HelloKostekSeeder.php)
- Usunięcie zduplikowanego bloku galerii, pozostawienie jednej spójnej listy 33 prac identyfikowanych po unikalnym ID/kluczu.

### Krok 3: Defensywna deduplikacja po stronie Frontendu (`Gallery.tsx`)
- Plik: [src/components/Gallery.tsx](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Gallery.tsx)
- W `useEffect` po pobraniu danych z API: dodanie mechanizmu deduplikacji na podstawie nazwy pliku graficznego (`image_url`). Jeśli z jakiegokolwiek powodu API zwróci powtórzoną grafikę, frontend wyświetli ją tylko raz.

---

## 3. Plan Weryfikacji
1. Zapytanie API `GET https://panel.hellokostek.pl/api/gallery` – weryfikacja: dokładnie 33 prace, 0 duplikatów.
2. Weryfikacja strony [https://hellokostek.pl/galeria](https://hellokostek.pl/galeria) – sprawdzenie liczby kart (dokładnie 33) i brak powtórzeń tych samych obrazów obok siebie.

---

## 4. Bramka Akceptacji
Czy zatwierdzasz powyższy plan oczyszczenia duplikatów w bazie danych i kodzie?
Po Twojej akceptacji natychmiast przystąpię do realizacji.
