# Plan Wdrożenia Poprawek – Feedback 04 (`2026.09 feedback 04.pdf`)

Dokument zawiera szczegółową analizę wszystkich punktów z pliku `2026.09 feedback 04.pdf` (wraz z odniesieniem do `2026 06 feedback 02.pdf`) oraz plan zmian w kodzie frontendu (Astro/React), backendu (Laravel/Filament CMS) i bazie danych.

---

## 1. Strona „O mnie / Bio” (`/o-mnie`) – edycja w CMS oraz docelowe treści z Feedback 02

### Analiza zgłoszenia klienta:
1. Klient poprosił o możliwość **samodzielnej edycji tekstu i zdjęcia** na podstronie `/o-mnie` w panelu administracyjnym.
2. Na zrzucie ekranu klient przekreślił obecny cytat (*„Teatr daje możliwość...”*), oba akapity tekstu oraz boks pod zdjęciem (*„Dlaczego malarstwo tradycyjne?”*) i dopisał: **„jest w pliku Feedback 02”**.
3. W pliku `2026 06 feedback 02.pdf` (str. 11, pkt 13 „O mnie”) znajdują się docelowe treści:
   - **Nagłówek (zostaje):** `Artysta • Malarz • Łódź` oraz `Kostek Maciej Kosteczka`
   - **Cytat:**
     > „Wydawać się może, że klasyczne malarstwo i sztuka figuratywna bardzo ograniczają twórcę, ale stosując zasady z improwizacji w teatrze, że: „gdzie są ograniczenia, tam jest najwięcej wolności”, szukam wolności w malarstwie realistycznym.”
   - **Treść biogramu:**
     > Aktor, tancerz, malarz  
     > Rocznik ‘88.  
     > Ukończył Państwową Wyższą Szkołę Teatralną w Krakowie na Wydziale Teatru Tańca w Bytomiu. Kulturoznawca po UŚ w Katowicach. Uczestnik International Summer Program – stypendium Roberta Wilsona w Watermill Center w Nowym Jorku. Pracował w Teatrze Tańca i Ruchu Rozbark, a w ramach Festiwalu Maltaw przy projekcie „Ksenofonia Symfonia dla Innego” oraz współtworzył spektakl „A co gdyby” na rezydencji Gdańskiego Festiwalu Tańca. Od lat współpracuje z Teatrem w Ruchu grając spektakle dla dzieci w całym kraju. Ex-pracownik korporacji.  
     > Równolegle: malarz, grafik i twórca rękodzieła – od 2023 roku prowadzi własną działalność: hellokostek tworząc obrazy olejne na zamówienie i ucząc aktorstwa, i rysunku. Od 2020 roku szkoli się pod okiem Pauliny Kwietniewskiej (malarstwo) i Macieja Kusa (rzeźba).  
     > Wystawy indywidualne: „SIEWIERZ-513W13R2”, 2017, Hotel Natura Residence, Siewierz; „Rogue Planet”, 2022, Klubokawiarnia KIPISZ, Łódź.  
     > Wystawy zbiorowe: „Spotkanie na czerwonej kanapie - jak rzeźba wrocławska czuje się w Łodzi”, 2025, OFF Piotrkowska, Łódź.  
     > Prace artysty dostępne są w galerii Two Of Art w Amsterdamie oraz na aukcjach organizowanych przez galerię Pragaleria w Warszawie.
   - **Elementy do usunięcia:** Karta *„Dlaczego malarstwo tradycyjne?”* pod zdjęciem (dolny pasek CTA *„Podoba Ci się moja twórczość?”* zostaje).

### Plan wdrożenia:
- **Backend / Filament CMS ([`ContentPageForm.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/ContentPages/Schemas/ContentPageForm.php), [`HelloKostekSeeder.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/database/seeders/HelloKostekSeeder.php)):**
  - Utworzenie w tabeli `content_pages` strony o slugu `o-mnie` i szablonie `about` (`Strona & wygląd -> Strony -> O mnie`).
  - Dostosowanie formularza [`ContentPageForm.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/ContentPages/Schemas/ContentPageForm.php), aby dla szablonu `about` wyświetlał przejrzyste pola:
    - **Nadtytuł** (`metadata.subtitle` – np. `Artysta • Malarz • Łódź`),
    - **Tytuł / Imię i nazwisko** (`title` – `Kostek Maciej Kosteczka`),
    - **Cytat wyróżniony** (`excerpt`),
    - **Treść biogramu** (`content` – edytor tekstu / akapity),
    - **Zdjęcie portretowe** (`hero_image_path` – upload zdjęcia z edytorem kadrowania),
    - Ukrycie sekcji paragrafów prawnych (`metadata.sections`), gdy wybrany jest szablon `about`, aby formularz był prosty i czytelny dla klienta.
- **Frontend ([`AboutMe.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/AboutMe.tsx)):**
  - Wpisanie docelowego tekstu z `Feedback 02` jako domyślnej treści statycznej (brak efektu mignięcia przy ładowaniu).
  - Usunięcie boksu *„Dlaczego malarstwo tradycyjne?”*.
  - Podpięcie pobierania danych z endpointu `GET /api/content/pages/o-mnie`, dzięki czemu każda zmiana tekstu, cytatu lub zdjęcia w panelu CMS będzie od razu widoczna na stronie.

---

## 2. Strona „Kontakt” (`/kontakt`) – zamiana ikonek social media

### Analiza zgłoszenia klienta:
- Klient poprosił o zamianę prostokątnych przycisków z podpisami (`Instagram`, `Facebook`, `TikTok`, `YouTube`) w sekcji `MEDIA SPOŁECZNOŚCIOWE` na podstronie `/kontakt` na okrągłe ikonki bez tekstu (identyczne jak te w stopce strony).

### Plan wdrożenia:
- **Frontend ([`Contact.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Contact.tsx#L207-L244)):**
  - Zastąpienie prostokątnych przycisków czterema okrągłymi ikonkami (`w-10 h-10 rounded-full bg-neutral-100 flex items-center justify-center text-stone-650`) z kolorowym efektem najechania (`hover:bg-[#E0115F]`, `hover:bg-[#1877F2]`, `hover:bg-black`, `hover:bg-[#FF0000]` i `hover:text-white`), dokładnie tak jak w [`Footer.astro`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Footer.astro#L96-L133).

---

## 3. Wyjaśnienie błędu `{"PL":"OLEJ","EN":"OIL"}` w Galerii (`/galeria`)

### Przyczyna problemu (odpowiedź dla klienta):
- Problem **nie wynikał z niedoczytania strony w przeglądarce**, lecz z wcześniejszej struktury bazy danych. Przed ujednoliceniem serwisu wyłącznie do języka polskiego, nazwy kategorii i tytuły prac były zapisane w bazie danych jako surowe obiekty JSON: `{"pl":"Olej","en":"Oil"}` oraz `{"pl":"Portret Kobiety","en":""}`.
- Komponent [`Gallery.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Gallery.tsx#L16-L43) oczekiwał zdekodowanego obiektu lub zwykłego tekstu, a otrzymując z API tekst zawierający JSON, wyświetlał go dosłownie na przyciskach kategorii i w lightboxie.
- Baza produkcyjna została już przeczyszczona (obecnie API zwraca czyste polskie nazwy `Olej`, `Akwarela`, `Akryl`, `Rysunek`).

### Plan wdrożenia (dodatkowe zabezpieczenie):
- Dodanie funkcji obronnej `parseLocalizedString()` w [`Gallery.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Gallery.tsx), [`Shop.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Shop.tsx), [`ProductDetail.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/ProductDetail.tsx) oraz w [`GalleryArtworkResource.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Resources/GalleryArtworkResource.php), która automatycznie wyciąga wartość `.pl`, jeśli kiedykolwiek natrafi na ciąg znaków w formacie `{"pl":...}`.

---

## 4. Edycja danych w pokazywarce obrazów w Galerii (`/galeria`) + korekta obrazu „Portret siedmiolatki”

### Analiza zgłoszenia klienta:
1. Klient chce móc edytować dane w pokazywarce (lightboxie) galerii: **rok powstania**, **tytuł** oraz **bullet points (wypunktowanie)**.
2. Dlaczego dotychczas nie mógł edytować wypunktowania (bullet points)?
   - W panelu CMS (`Strona & wygląd -> Galeria`) znajdują się pola `Tytuł`, `Rok powstania`, `Kategoria`, `Technika wykonania` i `Format`, natomiast 4 punkty wypunktowania (np. *„• Tradycyjne malarstwo olejne na płótnie bawełnianym”*, *„• Naciąg na krosna sosnowe”*, *„• Zabezpieczony werniksem końcowym”*, *„• Wykonany ręcznie na zamówienie ze zdjęcia”*) były **zaszyte na sztywno w kodzie [`Gallery.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Gallery.tsx#L412-L445)**.
3. Na zrzucie ekranu klient wskazał również konkretny obraz (`01-portret-edited.webp` – dziewczynka z białym kwiatkiem we włosach):
   - Na prawej krawędzi obecnego pliku `01-portret-edited.webp` widać ucięty fragment monitora ze zdjęciem referencyjnym (adnotacja klienta: **„złe wykadrowanie”**).
   - Prawidłowe dane tego obrazu wskazane przez klienta:
     - Kategoria: **Akryl**
     - Tytuł: **Portret siedmiolatki** (obecnie w bazie: *„Portret dziewczynki w kapeluszu”*)
     - Rok powstania: **2013**

### Plan wdrożenia:
1. **Edycja bullet points w panelu CMS (`Strona & wygląd -> Galeria`):**
   - Rozszerzenie [`GalleryArtworkForm.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Filament/Resources/GalleryArtworks/Schemas/GalleryArtworkForm.php) o pole **„Punkty opisu w pokazywarce (bullet points)”** (`metadata.bullet_points` – pole wieloliniowe `Textarea`, gdzie każda linia to osobny punkt w pokazywarce).
   - Przekazanie pola `bullet_points` w [`GalleryArtworkResource.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/app/Http/Resources/GalleryArtworkResource.php) do API `/api/gallery`.
   - Modyfikacja [`Gallery.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Gallery.tsx#L405-L446): wyświetlanie w pokazywarce punktów zdefiniowanych w CMS (oraz techniki/formatu, jeśli podano), z pominięciem sztywnych tekstów z kodu, gdy praca pochodzi z CMS.
2. **Poprawa kadrowania i danych obrazu `01-portret` (`gallery-26`):**
   - Wykadrowanie na czysto oryginalnego pliku [`01-portret.jpg`](file:///D:/Projekty/_KLIENCI/Hello%20Kostek/kopia_zapasowa/nowa/hellokostek.pl_wpvivid-uploads/uploads/2022/03/01-portret.jpg) z kopii zapasowej (sam obraz na płótnie w kwadracie, bez monitora po prawej stronie i bez sztalugi) i zapisanie jako `01-portret-edited.webp`.
   - Zmiana tytułu na `Portret siedmiolatki`, kategorii na `Akryl` i roku na `2013` w [`src/data/gallery.ts`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/data/gallery.ts), [`HelloKostekSeeder.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/database/seeders/HelloKostekSeeder.php) oraz w lokalnej bazie danych.

---

## 5 i 5a. Sklep (`/sklep`) – prezentacja cen (Oryginał vs Wydruk) oraz edycja nazw i opisów

### Uzgodniony wariant wdrożenia (wyłącznie lokalnie):
1. **Czytelna prezentacja cen i wariantów na karcie produktu w [`Shop.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Shop.tsx):**
   - Plakietka w lewym górnym rogu zdjęcia: **`AKWARELA / WYDRUK`** (lub **`RYSUNEK / WYDRUK`**, a gdy oryginał jest sprzedany – samo **`WYDRUK`**).
   - Sekcja cenowa pod zdjęciem: zamiast mylącego `od 30 zł` wyświetlamy przejrzyste rozbicie:
     - Gdy oryginał jest dostępny: **`Oryginał: 300 zł • Wydruk: 30 zł`** (dla rysunków: `Oryginał: 200 zł • Wydruk: 20 zł`).
     - Gdy oryginał został sprzedany: **`Wydruk: 30 zł`** (z oznaczeniem `Oryginał sprzedany`).
   - Dodanie czytelnej informacji na dole sekcji sklepu o cenach startowych (Wydruki: 20–30 zł, Rysunki: 200 zł, Akwarele: 300 zł).
2. **Aktualizacja nazw i opisów cyklu akwareli ([`src/data.ts`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/data.ts), [`HelloKostekSeeder.php`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/backend/database/seeders/HelloKostekSeeder.php), lokalna baza danych):**
   - Zmiana rzymskich numerów na arabskie: `Obiekt 2`, `Obiekt 7`, `Obiekt 8`, `Obiekt 9`, `Obiekt 13 (Sygnowany)`.
   - Zastąpienie wygenerowanych przez AI opisów nazwą cyklu: `Rogue planet`.
3. **Pełna obsługa edycji opisów w CMS ([`ProductDetail.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/ProductDetail.tsx), [`Shop.tsx`](file:///d:/Projekty/_KLIENCI/Hello%20Kostek/hello-kostek-dev/src/components/Shop.tsx)):**
   - Usunięcie błędnego fallbacku do statycznego `product.description` w `ProductDetail.tsx` (linia 142), dzięki czemu każda zmiana lub wyczyszczenie opisu w panelu CMS (`/admin/products` – `Produkty & sklep -> Produkty`) będzie od razu widoczna zarówno na liście sklepu, jak i na karcie produktu.

> [!IMPORTANT]
> **Zakres prac:** Wszystkie zmiany (kod Astro/React, panel Filament CMS, kadrowanie grafik oraz migracje/seedowanie bazy SQLite) zostaną wykonane **wyłącznie lokalnie** w środowisku deweloperskim, bez wypychania na serwer produkcyjny.
