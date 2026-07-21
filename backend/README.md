# Szablon 3: Standard E-commerce

Standardowy szablon startowy (boilerplate) dla sklepĂłw internetowych z zaawansowanym panelem administracyjnym Filament CMS, przystosowany do integracji z nowoczesnym frontendem w technologii Astro. Posiada wszystkie podstawowe funkcje e-commerce (katalog produktĂłw, warianty, koszyk, zamĂłwienia, pĹ‚atnoĹ›ci, kupony).

## đź› ď¸Ź Stack Technologiczny

- **Backend / CMS**: Laravel 13 + Filament CMS v5 (zarzÄ…dzanie sklepem, stronami, blogiem i ustawieniami)
- **Komunikacja**: Bezstanowe API (JSON) zintegrowane pod kÄ…tem frontu w **Astro** (routing API gotowy w `routes/api.php`)
- **Autentykacja**: Laravel Sanctum (tokeny API dla kont klientĂłw i koszyka)
- **Baza Danych**: SQLite / MySQL (gotowe migracje dla katalogu, zamĂłwieĹ„ i klientĂłw)
- **Integracje**: Przelewy24 & Stripe (pĹ‚atnoĹ›ci online), Google Reviews (pobieranie opinii Google Places)

## đź“‹ Wymagania Systemowe

Przed uruchomieniem projektu upewnij siÄ™, ĹĽe Twoje Ĺ›rodowisko speĹ‚nia poniĹĽsze wymagania:

* **PHP**: `>= 8.3`
* **Rozszerzenia PHP**: `pdo_sqlite` / `sqlite3` (dla domyĹ›lnej bazy danych), `bcmath` (do kalkulacji cen i walut), `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `session`, `xml`, `zip`
* **Node.js**: `>= 20.x` oraz **npm** (wymagane przez Vite 7 i Tailwind v4)

---

## đźŚź GĹ‚Ăłwne Funkcje CMS & Backend (Filament CMS)

Wbudowany panel administracyjny Filament CMS udostÄ™pnia gotowe, zoptymalizowane pod kÄ…tem SEO i UX moduĹ‚y zarzÄ…dzania sklepem:

### đź›’ ObsĹ‚uga SprzedaĹĽy & Katalogu

* **ZarzÄ…dzanie Katalogiem ProduktĂłw (`Products`, `ProductCategories`, `ProductAttributes`, `ProductVariants`)**:
  * PeĹ‚na obsĹ‚uga produktĂłw fizycznych, cyfrowych (pliki do pobrania) oraz usĹ‚ug.
  * Drzewiasta struktura kategorii oraz elastyczny system wariantĂłw/cech (rozmiary, kolory itp.).
  * **Sortowanie i kolejnoĹ›Ä‡ kategorii (Sort Order)**: Wbudowane zarzÄ…dzanie kolejnoĹ›ciÄ… kategorii w tabeli Filament metodÄ… "przeciÄ…gnij i upuĹ›Ä‡" (drag-and-drop) na podstawie pola `sort_order`, automatycznie sortujÄ…ce kategorie w menu.
  * **System wariantĂłw (Product Options & Variants)**: ZarzÄ…dzanie opcjami produktu (np. Rozmiar, Kolor) i wariantami (unikalne SKU, regular/sale price, VAT rate, manages stock).
  * **Dynamiczna cena katalogowa**: JeĹ›li produkt posiada aktywne warianty, cena wyjĹ›ciowa i najniĹĽsza cena w 30 dniach sÄ… automatycznie wyliczane z najtaĹ„szego dostÄ™pnego wariantu.
  * ZarzÄ…dzanie stanami magazynowymi oraz cenami regularnymi i promocyjnymi.
  * **Galeria zdjÄ™Ä‡**: ObsĹ‚uga wielu zdjÄ™Ä‡ (galerii graficznej) na karcie kaĹĽdego produktu, konfigurowana w panelu administratora.
  * **Dyrektywa Omnibus**: Automatyczna historia zmian cen regularnych i promocyjnych dla produktĂłw oraz ich poszczegĂłlnych wariantĂłw, wyliczajÄ…ca najniĹĽszÄ… cenÄ™ produktu z ostatnich 30 dni przed wprowadzeniem obniĹĽki (zwracana w API katalogu dla wariantĂłw i produktĂłw).
  * **WyrĂłĹĽniki i Filtrowanie (NowoĹ›Ä‡ / Bestseller)**: RÄ™czne przeĹ‚Ä…czniki (toggles) w panelu Filament do nadawania produktom statusĂłw "NowoĹ›Ä‡" (`is_new`) lub "Bestseller" (`is_bestseller`). Endpoint `/api/catalog` obsĹ‚uguje filtrowanie wedĹ‚ug tych wyrĂłĹĽnikĂłw (np. `?is_new=1` lub `?is_bestseller=1`), uĹ‚atwiajÄ…c implementacjÄ™ sekcji marketingowych na froncie Astro.
* **Procesy ZamĂłwieĹ„, WysyĹ‚ek & PĹ‚atnoĹ›ci (`Orders`, `Coupons`)**:
  * Rejestr zamĂłwieĹ„ z historiÄ… zmian statusĂłw, szczegĂłĹ‚ami dostawy oraz podglÄ…dem pĹ‚atnoĹ›ci.
  * **Zezwalaj na zakupy jako goĹ›Ä‡ (Guest Checkout)**: Wbudowana opcja `allow_guest_checkout` w konfiguracji i panelu UstawieĹ„ Sklepu pozwala administratorowi na wĹ‚Ä…czenie lub wyĹ‚Ä…czenie moĹĽliwoĹ›ci skĹ‚adania zamĂłwieĹ„ bez rejestracji konta.
  * **Zakupy B2B & Autocomplete & OdpornoĹ›Ä‡ API**: Opcjonalne zbieranie danych do faktury (Nazwa firmy, NIP) z dynamicznÄ… walidacjÄ… NIP. Endpoint `/api/b2b/gus/{nip}` odpytuje API BiaĹ‚ej Listy MF oraz GUS BIR z 3-sekundowym limitem czasu (timeout). W przypadku awarii zewnÄ™trznych baz (timeout/error), endpoint nie blokuje koszyka ani nie zwraca bĹ‚Ä™du 500 â€“ zwraca status `timeout_fallback`, umoĹĽliwiajÄ…c klientowi rÄ™czne uzupeĹ‚nienie danych firmy, co jest obsĹ‚ugiwane przez snippet `GusAutocomplete.js` poprzez zdarzenie DOM `gus-autocomplete-fallback`.
  * **Integracje KsiÄ™gowe (Fakturownia, iFirma, inFakt, wFirma)**: Wbudowany asynchroniczny system zdarzeĹ„ (`OrderPaid` -> `SendOrderToAccountingJob`) automatycznie wysyĹ‚ajÄ…cy dane opĹ‚aconych zamĂłwieĹ„ do zewnÄ™trznych API najpopularniejszych polskich serwisĂłw ksiÄ™gowych (peĹ‚ne logowanie w logach integracji).
  * **Logistyka InPost ShipX**: Integracja z API InPost ShipX bezpoĹ›rednio w widoku zamĂłwienia w panelu Filament. DostÄ™pne sÄ… akcje "Generuj etykietÄ™ InPost" (obsĹ‚ugujÄ…ca Paczkomaty i Kuriera z wyborem gabarytu A/B/C) oraz "Pobierz etykietÄ™" (pobieranie PDF z lokalnej pamiÄ™ci). Wygenerowanie paczki automatycznie aktualizuje tracking i zmienia status zamĂłwienia na wysĹ‚ane.
  * **Logistyka ORLEN Paczka**: Integracja z API ORLEN Paczka (protokĂłĹ‚ SOAP) bezpoĹ›rednio w widoku szczegĂłĹ‚Ăłw zamĂłwienia w panelu Filament. Pozwala na generowanie etykiet (wybĂłr gabarytu S/M/L) za pomocÄ… metody `GenerateLabelBusinessPack`, pobieranie ich w formacie PDF oraz automatyczne przypisywanie numeru Ĺ›ledzenia i aktualizowanie statusu zamĂłwienia.
  * **PĹ‚atnoĹ›ci Direct BLIK (0-click)**: API obsĹ‚uguje bezpoĹ›rednie przetwarzanie 6-cyfrowego kodu BLIK na froncie (bez przekierowania na bramkÄ™ Przelewy24) przy uĹĽyciu endpointu `/api/checkout/orders/{number}/payment-session`, co wyzwala push potwierdzenia w aplikacji bankowej klienta.
  * **Numer BDO**: Konfiguracja NAP/GEO w ustawieniach sklepu zawiera pole `bdo_number`, ktĂłre jest automatycznie nanoszone na dokumenty sprzedaĹĽowe (faktury).
  * **Polska matryca stawek VAT**: ObsĹ‚uga specyficznych stawek VAT (23%, 8%, 5%, 0% oraz zwolniony - zw). Stawka "zw." jest mapowana w bazie jako `99`, poprawnie drukowana na fakturach PDF, a w kalkulacjach koszyka traktowana jako 0%.
  * **Strefy wysyĹ‚kowe (Shipping Zones)**: MoĹĽliwoĹ›Ä‡ definiowania stref (grup krajĂłw) w ustawieniach sklepu wraz z przypisywaniem dedykowanych cennikĂłw wysyĹ‚ki (nadpisujÄ…cych stawki standardowe). Wbudowana walidacja kraju dostawy podczas checkoutu (brak konfiguracji stref blokuje wysyĹ‚kÄ™ za granicÄ™, dopuszczajÄ…c tylko PolskÄ™).
  * **WielowalutowoĹ›Ä‡ (Multi-currency)**: Wsparcie dla wielu walut (np. PLN, EUR) na bazie kursĂłw wymiany zdefiniowanych w Ustawieniach Sklepu. Pricing Engine automatycznie przelicza ceny pozycji, zniĹĽki, wysyĹ‚ki oraz prĂłg darmowej dostawy do waluty docelowej z zaokrÄ…glaniem do groszy/centĂłw.
  * **Procedura VAT OSS (One Stop Shop) dla konsumentĂłw w UE**: Dla transakcji B2C (bez numeru NIP) z wysyĹ‚kÄ… do innego kraju Unii Europejskiej, system automatycznie nadpisuje stawkÄ™ VAT towarĂłw i wysyĹ‚ki stawkÄ… kraju przeznaczenia (np. 19% dla Niemiec, 20% dla Francji zamiast standardowych 23% dla Polski), a zastosowanÄ… stawkÄ™ zapisuje w metadanych pozycji zamĂłwienia.
  * **Generowanie Faktur PDF & PrzepĹ‚yw za Pobraniem (COD)**: Klasa `MinimalPdfGenerator` w czystym PHP bez zewnÄ™trznych bibliotek generuje faktury VAT po oznaczeniu jako opĹ‚acone (`paid`) lub proformy dla statusu `pending`. W przypadku pĹ‚atnoĹ›ci za pobraniem (COD), zmiana statusu zamĂłwienia na wysĹ‚ane (`shipped`) automatycznie generuje peĹ‚nÄ… fakturÄ™ VAT (z oznaczeniem pĹ‚atnoĹ›ci przy odbiorze) oraz wysyĹ‚a jÄ… jako zaĹ‚Ä…cznik do wiadomoĹ›ci o nadaniu paczki, a takĹĽe synchronizuje dane z wybranÄ… integracjÄ… ksiÄ™gowÄ….
    * **Mechanizm Podzielonej PĹ‚atnoĹ›ci (MPP / Split Payment)**: JeĹ›li zamĂłwienie jest transakcjÄ… B2B (podany NIP), walutÄ… jest `PLN`, a wartoĹ›Ä‡ brutto zamĂłwienia wynosi 15 000 PLN lub wiÄ™cej, generator automatycznie dopisuje na fakturze prawnie wymaganÄ… adnotacjÄ™ *"Mechanizm podzielonej platnosci"*.
    * **Historyczna dokĹ‚adnoĹ›Ä‡ stawek VAT**: Generator odczytuje stawkÄ™ VAT bezpoĹ›rednio z metadanych pozycji zamĂłwienia (`metadata->vat_rate`), co zapobiega rozbieĹĽnoĹ›ciom w przypadku pĂłĹşniejszych zmian stawek w bazie.
  * **Ĺšledzenie PrzesyĹ‚ek (Tracking)**: Formularz zamĂłwienia pozwala na uzupeĹ‚nienie przewoĹşnika i numeru Ĺ›ledzenia paczki. Zmiana statusu na `shipped` wyzwala automatyczny e-mail potwierdzajÄ…cy wysyĹ‚kÄ™ z danymi Ĺ›ledzenia.
  * **Automatyczne Zwroty (Refunds) & OstrzeĹĽenie o Fakturze KorygujÄ…cej**: Po anulowaniu opĹ‚aconego zamĂłwienia system automatycznie wywoĹ‚uje API Stripe (SDK) lub Przelewy24 (REST API z podpisem SHA384) w celu zwrotu Ĺ›rodkĂłw klientowi. PoniewaĹĽ system automatycznie generuje imiennÄ… fakturÄ™ VAT/proformÄ™ przy opĹ‚aceniu zamĂłwienia, przy anulowaniu lub zwrocie system wyĹ›wietla warunkowy komunikat w panelu szczegĂłĹ‚Ăłw i edycji zamĂłwienia, przypominajÄ…cy o koniecznoĹ›ci wystawienia Faktury KorygujÄ…cej w zewnÄ™trznym zintegrowanym systemie ksiÄ™gowym (Fakturownia, iFirma, inFakt, wFirma).
  * **Eksport ZamĂłwieĹ„ do CSV**: Wygodny przycisk "Eksport CSV" w nagĹ‚Ăłwku listy zamĂłwieĹ„ generuje strumieniowany plik optymalizowany dla polskich biur rachunkowych i programĂłw takich jak Excel (UTF-8 BOM oraz separator Ĺ›rednik `;`). Plik zawiera szczegĂłĹ‚owe dane finansowe (brutto, netto, VAT, rabaty, wysyĹ‚ka), dane nabywcy (NIP, nazwa firmy), adresy oraz informacje logistyczne.
  * **Eksport KlientĂłw i SubskrybentĂłw do CSV**: Dedykowane akcje administracyjne eksportujÄ…ce dane klientĂłw (`admin/exports/customers`) oraz subskrybentĂłw newslettera (`admin/exports/newsletter-subscribers`) bezpoĹ›rednio do plikĂłw CSV w spĂłjnym formacie (UTF-8 BOM, separator Ĺ›rednik), co uĹ‚atwia zarzÄ…dzanie i import do zewnÄ™trznych narzÄ™dzi marketingowych.
  * **Punkty Odbioru / Paczkomaty**: PeĹ‚ne wsparcie dla metod dostawy wymagajÄ…cych punktu odbioru (np. InPost Paczkomaty, DPD Pickup, ORLEN Paczka). System waliduje obecnoĹ›Ä‡ identyfikatora punktu odbioru (`delivery_point.id`) w ĹĽÄ…daniach checkoutu, a szczegĂłĹ‚owe dane punktu (id, nazwa, adres) zapisuje w metadanych zamĂłwienia i prezentuje w panelu administratora.
  * **Automatyczne Planowanie Realizacji (Fulfillment Actions)**: Przy zĹ‚oĹĽeniu zamĂłwienia system automatycznie generuje i harmonogramuje zadania realizacji dla poszczegĂłlnych pozycji w zaleĹĽnoĹ›ci od ich typu: `physical_shipping` (wysyĹ‚ka fizyczna), `digital_delivery` (dostawa cyfrowa) lub `service_followup` (obsĹ‚uga usĹ‚ugi). Statusy zadaĹ„ sÄ… moderowane w panelu.
  * **Zgody RODO i Regulaminu (DowĂłd Audytowy)**: Podczas checkoutu system bezwzglÄ™dnie waliduje akceptacjÄ™ regulaminu (`terms_accepted`). Zgodnie z zasadÄ… rozdzielnoĹ›ci zgĂłd (Unbundled Consents) oraz zakazem domyĹ›lnego zaznaczania (Pre-ticked boxes), zgoda marketingowa (`marketing_accepted`) is opcjonalna i nie wpĹ‚ywa na zĹ‚oĹĽenie zamĂłwienia. JeĹ›li klient wyrazi zgodÄ™ marketingowÄ…, zostaje automatycznie zapisany do newslettera w bezpiecznym modelu Double Opt-In. JeĹ›li w koszyku znajduje siÄ™ produkt cyfrowy, system wymaga dodatkowo potwierdzenia zgody na natychmiastowe dostarczenie treĹ›ci i utratÄ™ prawa do odstÄ…pienia od umowy (`digital_consent`). Wszystkie akceptacje sÄ… zapisywane w metadanych zamĂłwienia w bazie wraz z dokĹ‚adnÄ… wersjÄ… regulaminu (ustawianÄ… w panelu Filament), datÄ… akceptacji, adresem IP oraz User Agentem jako niepodwaĹĽalny dowĂłd audytowy w sporach konsumenckich.
  * **Kalkulator Cen / Wycena Koszyka (Quote API)**: Bezstanowy endpoint `/api/quote` pozwalajÄ…cy frontendowi (np. Astro) na natychmiastowe wyliczenie wartoĹ›ci koszyka (ceny, zniĹĽki z kuponĂłw, podatki, koszty wysyĹ‚ki w oparciu o strefy i segmenty) w jednym zapytaniu bez tworzenia szkicu zamĂłwienia.
  * Zintegrowane bramki pĹ‚atnicze: **Przelewy24** oraz **Stripe** z peĹ‚nÄ… obsĹ‚ugÄ… webhookĂłw potwierdzajÄ…cych pĹ‚atnoĹ›ci.
  * System kuponĂłw rabatowych (kwotowych i procentowych) z ograniczeniami czasowymi lub progami kwotowymi. Logowanie bĹ‚Ä™dnych/wygasĹ‚ych prĂłb uĹĽycia kuponĂłw w logach aplikacji do celĂłw analitycznych.
  * **KsiÄ…ĹĽka adresowa (`Customer Addresses`)**: Przechowywanie wielu adresĂłw dostawy i rozliczeĹ„ dla zalogowanych klientĂłw. Zabezpieczony CRUD w API pod `/api/account/addresses`.
  * **Lista ĹĽyczeĹ„ (`Wishlist`)**: Funkcja dodawania i usuwania produktĂłw z listy ulubionych dla zalogowanych uĹĽytkownikĂłw za pomocÄ… endpointu `/api/account/wishlist`.
  * **Zwroty i Reklamacje (RMA)**: Pełny moduł zgłaszania zwrotów (RMA) w API dla klientów zarejestrowanych oraz gości (`POST /api/returns`) z zabezpieczeniem przed wielokrotnym zwrotem (Double Refund Protection – weryfikacja sumy dotychczas zgłoszonych zwrotów w stosunku do zakupionej ilości) wraz z wysyłką potwierdzeń na trwałym nośniku, a także rejestr, zarządzanie i moderacja zwrotów w panelu Filament (`OrderReturnResource`).
  * **Powiadomienia o dostÄ™pnoĹ›ci towarĂłw (Back-in-stock)**: Funkcja zapisu na powiadomienia e-mail o powrocie wyprzedanego produktu lub wariantu do magazynu przez endpoint `POST /api/catalog/products/back-in-stock-subscribe`. W momencie uzupeĹ‚nienia stanu magazynowego (zmiana z 0 na wiÄ™kszy), system automatycznie w tle wysyĹ‚a spersonalizowane maile (`BackInStockMail`), loguje wysyĹ‚kÄ™ w bazie logĂłw maili transakcyjnych oraz oznacza subskrypcje jako powiadomione. ZarzÄ…dzanie subskrypcjami i ich historiÄ… odbywa siÄ™ w panelu Filament (`BackInStockSubscriptionResource`).
  * **Rekomendacje i powiÄ…zania produktĂłw (Up-selling, Cross-selling, Similar)**: Wygodne definiowanie produktĂłw powiÄ…zanych bezpoĹ›rednio w panelu edycji produktu z podziaĹ‚em na typy relacji (podobny, up-sell, cross-sell). Relacje sÄ… zwracane w payloadzie szczegĂłĹ‚Ăłw produktu w API.
  * **Rozbudowane API filtrowania i paginacji katalogu**: Endpoint `/api/catalog` wspiera wyszukiwanie tekstowe (`query`), filtrowanie po kategoriach (`category`), zakresie cen (`price_min`/`price_max` uwzglÄ™dniajÄ…ce ceny wariantĂłw), atrybutach dynamicznych, sortowanie (cena, popularnoĹ›Ä‡, nowoĹ›ci) oraz paginacjÄ™ JSON.
* **Segmentacja KlientĂłw (`Customers`)**:
  * ZarzÄ…dzanie bazÄ… klientĂłw oraz automatyczne przypisywanie do segmentĂłw lojalnoĹ›ciowych i hurtowych (np. na podstawie liczby opĹ‚aconych zamĂłwieĹ„) z automatycznymi zniĹĽkami.
* **R�czne Tworzenie Rekord�w w Panelu CMS (Zarz�dzanie Operacyjne)**:
  * **Tworzenie klient�w r�cznie**: Przycisk *�Utw�rz klienta"* na li�cie klient�w (`/admin/klienci`) pozwala doda� nowe konto klienta bezpo�rednio w CMS � bez konieczno�ci rejestracji przez API frontendu. Formularz zawiera imi� i nazwisko, adres e-mail, segment (np. hurtowy) oraz has�o hashowane automatycznie przed zapisem. Przydatne np. do dodania partnera handlowego z indywidualnym rabatem segmentowym.
  * **R�czne tworzenie i edycja zam�wie�**: Przycisk *�Nowe zam�wienie"* na li�cie zam�wie� (`/admin/zamowienia`) umo�liwia rejestracj� zam�wienia bezpo�rednio przez administratora � np. sprzeda�y telefonicznej lub transakcji handlowej. Formularz obejmuje: wyb�r klienta z bazy (autouzupe�nianie imienia, nazwiska, e-maila i telefonu) lub r�czne wpisanie danych, repeater pozycji z wyszukiwaniem produktu i auto-pobieraniem nazwy/SKU/ceny, dynamiczne przeliczanie kwoty po zmianie ilo�ci, statusy zam�wienia/p�atno�ci/realizacji oraz koszty wysy�ki i rabatu. Numer zam�wienia generowany automatycznie w formacie `ORD-YYYYMMDD-XXXXXX`.
  * **R�czne tworzenie zwrot�w**: Przycisk *�Utw�rz zwrot"* na li�cie zwrot�w (`/admin/zwroty`) pozwala zarejestrowa� zwrot bezpo�rednio w panelu � np. gdy klient zwraca towar osobi�cie. Przy tworzeniu dost�pne s� pola wyboru zam�wienia i klienta (auto-uzupe�nienie), edytowalny repeater zwracanych pozycji oraz pole powodu zwrotu. W trybie edycji pola powi�zania wy�wietlane s� tylko do odczytu.

### đź“ť CMS, SEO, AEO & GEO (ZarzÄ…dzanie TreĹ›ciÄ… i Pozycjonowanie)

* **ZarzÄ…dzanie Stronami Statycznymi (`ContentPages`)**:
  * Tworzenie i edycja podstron (np. regulaminy, polityki, strony lÄ…dowania) z wyborem dedykowanego szablonu, pĂłl SEO i automatycznym udostÄ™pnianiem przez API.
  * **KolejnoĹ›Ä‡ stron (Sort Order)**: Wbudowana obsĹ‚uga ukĹ‚adania linkĂłw (np. w stopce strony) metodÄ… "przeciÄ…gnij i upuĹ›Ä‡" w tabeli Filament na podstawie pola `sort_order`.
* **ZarzÄ…dzanie Blogiem (`BlogPosts`)**:
  * Publikowanie artykuĹ‚Ăłw blogowych ze zdjÄ™ciem okĹ‚adki, metadanymi pozycjonujÄ…cymi, profilem autora oraz bibliografiÄ…/linkami ĹşrĂłdĹ‚owymi wspierajÄ…cymi E-E-A-T.
* **ZarzÄ…dzanie FAQ (`FaqItems`)**:
  * ModuĹ‚ pytaĹ„ i odpowiedzi (FAQ) pogrupowanych tematycznie z obsĹ‚ugÄ… kolejnoĹ›ci sortowania metodÄ… "przeciÄ…gnij i upuĹ›Ä‡" (drag-and-drop), automatycznie generujÄ…cy schemat JSON-LD `FAQPage`.
* **Rejestr ZapytaĹ„ Kontaktowych (`ContactInquiries`)**:
  * PeĹ‚ne wsparcie dla formularzy kontaktowych oraz zaawansowanych formularzy wielokrokowych, briefĂłw projektowych czy konfiguratorĂłw wycen.
  * Endpoint API `POST /api/inquiries` waliduje podstawowe pola (imiÄ™, e-mail, temat, treĹ›Ä‡), a wszystkie pozostaĹ‚e niestandardowe parametry automatycznie zapisuje w bazie jako JSON w kolumnie `payload`.
  * Prezentacja danych w panelu administracyjnym Filament CMS (`ContactInquiryResource`) za pomocÄ… dynamicznej tabeli klucz-wartoĹ›Ä‡ w zaleĹĽnoĹ›ci od zawartoĹ›ci `payload`.
  * Przechowywanie metadanych poĹ‚Ä…czenia (adres IP, User Agent) do celĂłw bezpieczeĹ„stwa i zgodnoĹ›ci z RODO.
* **Mapa TreĹ›ci i Nawigacji (ContentMap API)**:
  * Bezstanowy endpoint `/api/content/map` zwracajÄ…cy kompletnÄ… strukturÄ™ aktywnych stron CMS, grup FAQ, kategorii produktĂłw i statusu bloga w celu Ĺ‚atwego budowania menu i routingu we frontendzie Astro.
* **Dynamiczna Sitemap XML (`sitemap.xml`) & robots.txt**:
  * Automatycznie generowana mapa witryny pod adresem `/sitemap.xml` bazujÄ…ca na adresie URL witryny sklepowej (`storefront.url`).
  * Dynamiczny plik `/robots.txt` automatycznie wskazujÄ…cy Ĺ›cieĹĽkÄ™ do `sitemap.xml` oraz wprost zakazujÄ…cy indeksowania wraĹĽliwych lub czysto technicznych adresĂłw URL (unikalnego URL panelu administratora `FILAMENT_PATH`, koszyka `/cart`, `/api/cart` oraz checkoutu `/checkout`, `/api/checkout`).
* **Ustawienia noindex (Globalne i Jednostkowe)**:
  * Wygodna flaga `is_noindex` w panelu edycji produktĂłw, kategorii, wpisĂłw blogowych i stron statycznych, a takĹĽe globalny przeĹ‚Ä…cznik w ustawieniach sklepu.
  * System automatycznie wyklucza zablokowane elementy z dynamicznej `sitemap.xml`, wstrzykuje nagĹ‚Ăłwek HTTP `X-Robots-Tag: noindex` (poprzez middleware) oraz zwraca dyrektywÄ™ noindex w metadanych API dla frontendu Astro.
  * **Ĺšrodowiskowa blokada indeksowania (`APP_INDEXABLE`)**: Flaga `.env` `APP_INDEXABLE=false` (domyĹ›lna w `.env.example`) automatycznie wymusza nagĹ‚Ăłwek `X-Robots-Tag: noindex` dla wszystkich odpowiedzi, zapobiegajÄ…c indeksowaniu Ĺ›rodowisk deweloperskich i testowych przez wyszukiwarki. Na produkcji naleĹĽy ustawiÄ‡ `APP_INDEXABLE=true`.
* **Tagi Open Graph (OG), Twitter Cards & Linki Kanoniczne**:
  * Endpointy API dla produktĂłw, bloga i stron statycznych obok standardowych tagĂłw SEO zwracajÄ… dedykowanÄ… strukturÄ™ `social_meta` (`og:title`, `og:description`, `og:image`, `twitter:card` itp.).
  * Zaimplementowano automatyczny fallback: w przypadku braku rÄ™cznie wgranego obrazka w CMS, system automatycznie podstawia pierwsze zdjÄ™cie z galerii produktu lub zdjÄ™cie okĹ‚adki wpisu.
  * Zwracanie klucza `canonical_url` (bazowy adres URL bez parametrĂłw filtrujÄ…cych czy walutowych) uĹ‚atwia automatyczne wstrzykniÄ™cie tagu `<link rel="canonical" href="..." />` w sekcji `<head>` na froncie Astro.
* **Automatyczne Przekierowania 301**:
  * Wbudowane obserwatory Eloquent automatycznie tworzÄ… rekordy `301` w module [RedirectRules](file:///d:/Projekty/_BOILERPLATE/laravel-filament-ecommerce-boilerplate/app/Models/RedirectRule.php) przy zmianie sluga produktu, kategorii, wpisu lub strony.
  * Zaimplementowano inteligentne aktualizowanie istniejÄ…cych przekierowaĹ„ przy Ĺ‚aĹ„cuchowych zmianach slugĂłw, zapobiegajÄ…c powstawaniu pÄ™tli i Ĺ‚aĹ„cuchĂłw przekierowaĹ„.
  * Dedykowany endpoint `/api/redirects/resolve` uĹ‚atwia routerowi frontendu Astro bezkosztowe rozwiÄ…zywanie starych Ĺ›cieĹĽek na nowe.
* **Teksty Alternatywne (Alt) dla grafik**:
  * PeĹ‚na obsĹ‚uga tekstĂłw alternatywnych dla gĹ‚Ăłwnego zdjÄ™cia produktu, okĹ‚adki wpisu na blogu oraz grafiki stron w panelu Filament (zapisywane bez migracji w kolumnie `metadata`).
* **Dane NAP & Local GEO (WiarygodnoĹ›Ä‡ Lokalna)**:
  * Sekcja w konfiguracji sklepu umoĹĽliwiajÄ…ca podanie spĂłjnych danych teleadresowych (NAP): ulica, miasto, kod pocztowy, telefon, wspĂłĹ‚rzÄ™dne geograficzne (Latitude/Longitude) oraz godziny otwarcia.
* **E-E-A-T & Citations (WydajnoĹ›Ä‡ w AEO/GEO)**:
  * **Profile autorĂłw**: Dodano dedykowane pola biogramu, zdjÄ™cia oraz profilu LinkedIn dla autorĂłw wpisĂłw blogowych.
  * **Bibliografia (ĹąrĂłdĹ‚a)**: Wbudowany repeater pozwalajÄ…cy na definiowanie zewnÄ™trznych, wiarygodnych ĹşrĂłdeĹ‚ (linkĂłw bibliograficznych) dla artykuĹ‚Ăłw na blogu.
* **Dynamiczne Schematy JSON-LD (Schema.org)**:
  * Zautomatyzowane generowanie i serwowanie gotowych schematĂłw strukturalnych w API pod kluczem `schema_json_ld`:
    * `Product` & `Offer` (Catalog API — ceny, waluta, oceny, dostępność, a także marka `brand`, kod `mpn`/SKU, stan `itemCondition` oraz termin ważności ceny `priceValidUntil` dla pełnej zgodności z Google Search Console i Merchant Center).
    * `FAQPage` (FAQ API â€” struktury pytaĹ„ i odpowiedzi).
    * `BlogPosting` (Blog API â€” dane wpisu, autorytet autora typu `Person` i wydawcy `Organization`).
    * `LocalBusiness` (Reviews API â€” Ĺ‚Ä…czenie opinii Google/sklepu z danymi NAP firmy).
* **Optymalizacja MediĂłw & ObrazĂłw (WebP & ResponsywnoĹ›Ä‡)**:
  * **Konwersja WebP**: Automatyczna konwersja przesyĹ‚anych w CMS grafik (JPEG, PNG, GIF) do formatu WebP w locie na serwerze.
  * **Skalowanie RozdzielczoĹ›ci**: Automatyczne zmniejszanie obrazĂłw przekraczajÄ…cych maksymalne wymiary (szerokoĹ›Ä‡/wysokoĹ›Ä‡) zdefiniowane w ustawieniach z zachowaniem proporcji.
  * **Kopie Responsywne**: Automatyczne tworzenie mniejszych wariantĂłw obrazĂłw (np. `360w`, `720w`, `1200w`) w tym samym katalogu dla optymalnego dopasowania do rĂłĹĽnych ekranĂłw.
  * **Integracja z frontendem (`PublicMediaUrl`)**: Helper `PublicMediaUrl` udostÄ™pnia metody `resolveResponsive()` oraz `responsiveSrcset()` uĹ‚atwiajÄ…ce generowanie optymalnych atrybutĂłw `srcset` w szablonach HTML i Astro.
  * **Centrum ZarzÄ…dzania w CMS**: Administrator moĹĽe bezpoĹ›rednio w panelu edycji UstawieĹ„ Sklepu wĹ‚Ä…czaÄ‡/wyĹ‚Ä…czaÄ‡ opcje konwersji, zmieniaÄ‡ dopuszczalne wymiary oraz definiowaÄ‡ niestandardowe szerokoĹ›ci kopii responsywnych.
* **Newsletter & Kampanie (`NewsletterSubscribers`, `NewsletterCampaigns`)**:
  * Baza subskrybentĂłw oraz kreator kampanii newsletterowych (RichEditor) z moĹĽliwoĹ›ciÄ… wysyĹ‚ki testowej i masowej (asynchronicznej w tle).
  * **Mechanizm Double Opt-In (Ustawa o Ĺ›wiadczeniu usĹ‚ug drogÄ… elektronicznÄ… & RODO)**:
    * **Zapis przez API** (`POST /api/newsletter/subscribe`): Nowy adres e-mail otrzymuje status `pending` (is_active = false) oraz unikalny token weryfikacyjny. System automatycznie wysyĹ‚a maila transakcyjnego z linkiem aktywacyjnym.
    * **Zapis podczas checkoutu** (`POST /api/checkout/place`): Klient moĹĽe opcjonalnie zaznaczyÄ‡ zgodÄ™ marketingowÄ… (`marketing_accepted` lub `customer.marketing_accepted`). Zgodnie z zasadÄ… rozdzielnoĹ›ci zgĂłd, zgoda ta jest nieobowiÄ…zkowa i nie blokuje zamĂłwienia. JeĹ›li zostanie zaznaczona, system w tle rejestruje subskrybenta (status `pending`), wysyĹ‚a maila weryfikacyjnego i zapisuje podpis zgody w metadanych zamĂłwienia (`marketing_acceptance`).
    * **Potwierdzenie subskrypcji** (`GET /newsletter/confirm/{token}`): KlikniÄ™cie linku aktywacyjnego w mailu weryfikuje token, zmienia status subskrybenta na `active` (is_active = true), zapisuje datÄ™ i czas oraz adres IP klikniÄ™cia (na potrzeby dowodu audytowego dla UODO), a nastÄ™pnie przekierowuje uĹĽytkownika na stronÄ™ sukcesu na froncie (`FRONTEND_URL` + `/newsletter/confirmed`). W przypadku bĹ‚Ä™dnego lub wygasĹ‚ego tokenu nastÄ™puje przekierowanie na stronÄ™ bĹ‚Ä™du (`FRONTEND_URL` + `/newsletter/error`).
    * **Rezygnacja z subskrypcji (Opt-Out)**: System umoĹĽliwia rezygnacjÄ™ z subskrypcji w dwojaki sposĂłb:
      * **API** (`POST /api/newsletter/unsubscribe`): Przyjmuje parametr `email`, zmienia status subskrybenta na `unsubscribed`, ustawia datÄ™ `unsubscribed_at` oraz `is_active = false`.
      * **Web (Link z stopki maila)** (`GET /newsletter/unsubscribe/{email}` z weryfikacjÄ… podpisu URL): Bezpieczny, podpisany kryptograficznie link wyrejestrowania generowany dla kaĹĽdego subskrybenta indywidualnie. KlikniÄ™cie linku bezpiecznie wypisuje uĹĽytkownika i przekierowuje go na stronÄ™ sukcesu na froncie (`FRONTEND_URL` + `/newsletter/unsubscribed`). W przypadku bĹ‚Ä™dnej lub podrobionej sygnatury nastÄ™puje przekierowanie na stronÄ™ bĹ‚Ä™du (`FRONTEND_URL` + `/newsletter/error`).
    * **Generowanie linkĂłw wyrejestrowania w kampaniach**: KaĹĽdy e-mail wysyĹ‚any w ramach kampanii (lub wysyĹ‚ki testowej) automatycznie obsĹ‚uguje placeholder `{{unsubscribe_url}}` lub `{{UNSUBSCRIBE_URL}}`, podstawiajÄ…c spersonalizowany, podpisany link rezygnacji. W przypadku braku tego placeholdera w szablonie, system automatycznie doĹ‚Ä…cza estetycznÄ… stopkÄ™ z linkiem wyrejestrowujÄ…cym na koĹ„cu maila.
    * **Dane w bazie**: Tabela `newsletter_subscribers` przechowuje statusy (`pending`, `active`, `unsubscribed`), token weryfikacyjny (`double_opt_in_token`), adres IP potwierdzenia (`double_opt_in_ip`) oraz znacznik czasu (`double_opt_in_confirmed_at`).
* **ZarzÄ…dzanie Skryptami ĹšledzÄ…cymi & Ciasteczkami (Cookies)**:
  * MoĹĽliwoĹ›Ä‡ wĹ‚Ä…czenia/wyĹ‚Ä…czenia baneru cookies (`cookie_banner_enabled`) na froncie oraz edycji nagĹ‚Ăłwka (`cookie_banner_title`) i treĹ›ci baneru (`cookie_banner_description`) bez ingerencji w kod.
  * Przechowywanie i walidacja kluczy analitycznych w bazie danych: Google Tag Manager ID (`google_tag_manager_id` w formacie `GTM-XXXXXX`), Google Analytics ID (`google_analytics_id` w formacie `G-XXXXXX`), oraz Facebook Pixel ID (`facebook_pixel_id`).
  * **Niestandardowe skrypty w sekcji head** (`custom_head_scripts`): Dowolne kody Ĺ›ledzÄ…ce (np. Umami, Plausible, Fathom, Hotjar) wklejane w panelu Filament bez dotykania kodu Astro.
  * **Rejestr ZgĂłd RODO (Consent Log)**: Rejestrowanie w bazie danych granularnych zgĂłd na cookies przesĹ‚anych przez API (`POST /api/cookie-consents`) oraz dedykowany przeglÄ…darkowy log audytowy w panelu Filament (`CookieConsentResource`).
  * Dane sÄ… automatycznie wystawiane przez bezstanowe API do wdroĹĽenia po stronie Astro.
* **Belka ogĹ‚oszeĹ„ / komunikatĂłw (Announcement Bar)**:
  * Proste zarzÄ…dzanie komunikatami pilnymi, kryzysowymi lub promocyjnymi z poziomu ustawieĹ„ sklepu. Pole tekstowe `announcement_text` oraz wĹ‚Ä…cznik `announcement_enabled` pozwalajÄ… na bĹ‚yskawiczne wyĹ›wietlenie paska na samej gĂłrze strony.
* **đźŚŤ WielojÄ™zycznoĹ›Ä‡ (Multilingual Support)**:
  * PeĹ‚na integracja z pakietem `spatie/laravel-translatable` dla kluczowych pĂłl tekstowych (nazwy, opisy, tytuĹ‚y i treĹ›ci w produktach, kategoriach, blogu i stronach CMS).
  * System zakĹ‚adek (`Tabs`) w formularzach Filament do wygodnego i niezaleĹĽnego wprowadzania wersji polskiej (`pl`) i angielskiej (`en`) w bazie SQLite/MySQL jako JSON.
  * Middleware `SetLocaleMiddleware` automatycznie parsujÄ…cy nagĹ‚Ăłwek `Accept-Language` ĹĽÄ…dania w celu dynamicznego serwowania danych w odpowiednim jÄ™zyku w API.
* **đź”” Powiadomienia w Czasie Rzeczywistym (WebSockets)**:
  * WdroĹĽona natywna obsĹ‚uga silnika **Laravel Reverb**.
  * Zdarzenie `OrderPaid` z obsĹ‚ugÄ… `ShouldBroadcast` rozsyĹ‚ajÄ…ce informacje o opĹ‚aconych zamĂłwieniach na kanale prywatnym `admin.orders` (autoryzowanym tylko dla kont z flagÄ… `is_admin === true`).
  * Skrypt nasĹ‚uchujÄ…cy Echo zintegrowany bezpoĹ›rednio w panelu administracyjnym Filament przez render hook `PanelsRenderHook::BODY_END`, ktĂłry w czasie rzeczywistym wyĹ›wietla administratorom toasty o nowych pĹ‚atnoĹ›ciach z przyciskiem przekierowania do szczegĂłĹ‚Ăłw zamĂłwienia.
* **đź› ď¸Ź Tryb Konserwacji (Maintenance Mode)**:
  * MoĹĽliwoĹ›Ä‡ tymczasowego wyĹ‚Ä…czenia sklepu z poziomu panelu Filament.
  * ObsĹ‚uga whitelisty adresĂłw IP administratora (ktĂłrzy nadal widzÄ… sklep) oraz spersonalizowanego komunikatu tekstowego dla klientĂłw.
  * Middleware `StoreMaintenanceMode` automatycznie blokuje ruch API (zwracajÄ…c kod `503 Service Unavailable` z komunikatem JSON) oraz przekierowuje uĹĽytkownikĂłw webowych na stronÄ™ budowy (z wykluczeniem panelu `/admin` dla admina).

### â­ď¸Ź Opinie & Recenzje (`ProductReviews`)

* **Opinie o produktach**: ModuĹ‚ recenzji produktowych (ocena 1-5 gwiazdek, komentarz) z automatycznÄ… weryfikacjÄ… zakupu (system sprawdza, czy dany e-mail zakupiĹ‚ produkt w przeszĹ‚oĹ›ci) oraz panelem moderacji (zatwierdzanie opinii przed ich publikacjÄ…).
* **Opinie ogĂłlne o firmie**: MoĹĽliwoĹ›Ä‡ elastycznego wĹ‚Ä…czania opinii ogĂłlnych i wyboru ich ĹşrĂłdeĹ‚ (tylko Google Places, tylko opinie z bazy danych sklepu dodane rÄ™cznie/przez formularz, lub oba ĹşrĂłdĹ‚a jednoczeĹ›nie poĹ‚Ä…czone chronologicznie w jeden feed).
* **Konfiguracja**: WĹ‚Ä…czanie/wyĹ‚Ä…czanie opinii produktowych i ogĂłlnych z poziomu panelu ustawieĹ„ sklepu.

### đź“Š Analityka & Monitorowanie (Dashboard)

* **Kokpit GĹ‚Ăłwny (`StoreDashboard`)**:
  * Pierwszy widok panelu administratora agregujÄ…cy najwaĹĽniejsze wskaĹşniki (dzisiejsze zamĂłwienia, 7-dniowy przychĂłd brutto, profile klientĂłw), statusy konfiguracji integracji oraz skrĂłty do najpilniejszych zadaĹ„ operacyjnych.
* **Statystyki Sklepu (`Analytics Overview`)**:
  * Wykresy i podsumowania unikalnych wizyt, odsĹ‚on oraz transakcji (zakres 7 dni).
  * Obliczanie wspĂłĹ‚czynnika konwersji na poszczegĂłlnych etapach checkoutu.
  * Raporty najpopularniejszych produktĂłw, stron wejĹ›ciowych, ĹşrĂłdeĹ‚ (hosts) oraz kampanii UTM.
* **Stan Operacyjny Systemu (`Operations Health`)**:
  * NarzÄ™dzie diagnostyczne sprawdzajÄ…ce poprawnoĹ›Ä‡ poĹ‚Ä…czenia z bazÄ… danych, konfiguracjÄ™ kluczy API dla pĹ‚atnoĹ›ci Stripe i Przelewy24 oraz gotowoĹ›Ä‡ integracji.
* **Weryfikacja Kondycji & Konfiguracji (`/api/health`)**:
  * Endpoint zwracajÄ…cy uproszczony status kondycji publicznie, a dla zalogowanych administratorĂłw peĹ‚nÄ… konfiguracjÄ™ sklepu, obsĹ‚ugiwane waluty, progi darmowej dostawy oraz statusy gotowoĹ›ci poszczegĂłlnych integracji.

### đź› ď¸Ź BezpieczeĹ„stwo, Logowanie & Diagnostyka

* **System rĂłl i uprawnieĹ„ pracownikĂłw (RBAC)**: Dedykowany podziaĹ‚ uprawnieĹ„ w panelu Filament CMS w oparciu o przypisanÄ… rolÄ™ (`admin`, `manager`, `employee`, `customer`):
  * **admin** - Super-administrator posiadajÄ…cy peĹ‚ny dostÄ™p techniczny (konfiguracja integracji Stripe/Przelewy24, zarzÄ…dzanie kontami, logi systemowe, Failed Jobs, backupy).
  * **manager** - MenedĹĽer sklepu posiadajÄ…cy peĹ‚ny wglÄ…d biznesowy (zarzÄ…dzanie produktami, kategoriami, kuponami, zamĂłwieniami, zwrotami, klientami, blogiem i stronami CMS). Nie ma dostÄ™pu do ustawieĹ„ technicznych i logĂłw systemowych.
  * **employee** - Pracownik operacyjny posiadajÄ…cy dostÄ™p wyĹ‚Ä…cznie do obsĹ‚ugi zamĂłwieĹ„ i zwrotĂłw RMA (np. zmiana statusĂłw realizacyjnych, wpisywanie numerĂłw trackingowych, generowanie etykiet kurierskich InPost/ORLEN Paczka). Nie moĹĽe usuwaÄ‡ rekordĂłw, edytowaÄ‡ cen produktĂłw, zarzÄ…dzaÄ‡ treĹ›ciÄ… strony i nie ma wglÄ…du w statystyki finansowe.
  * **customer** - Klient serwisu, ktĂłry zarzÄ…dza swoim profilem wyĹ‚Ä…cznie przez API Sanctum (brak fizycznego dostÄ™pu do panelu `/admin`).
  * *Uwaga:* Kokpit gĹ‚Ăłwny (`StoreDashboard`) automatycznie filtruje wykresy i ukrywa statystyki przychodĂłw (finanse) oraz alerty integracji dla roli `employee`.
* **ZarzÄ…dzanie UĹĽytkownikami CMS (`UserResource`)**:
  * PeĹ‚na kontrola i edycja kont uĹĽytkownikĂłw panelu w grupie **System i logi** pod spolszczonym adresem `/uzytkownicy-cms`.
  * **Zaawansowane Zabezpieczenia RBAC**:
    * *Blokada samousuniÄ™cia*: UĹĽytkownik nie moĹĽe usunÄ…Ä‡ wĹ‚asnego konta, co zapobiega przypadkowej utracie dostÄ™pu do systemu.
    * *Ochrona roli admina*: Administrator nie moĹĽe samodzielnie zmieniÄ‡ swojej roli na niĹĽszÄ… (np. manager lub employee), co zapobiega sytuacji braku aktywnego administratora w systemie.
    * *Ochrona kont administratorĂłw*: UĹĽytkownicy o niĹĽszych rolach (`manager`, `employee`) nie majÄ… uprawnieĹ„ do edycji ani usuwania kont z rolÄ… `admin` (akcje edycji i kasowania sÄ… dla nich automatycznie blokowane i ukrywane na poziomie tabeli oraz formularza).
* **Spolszczone Slugi Uwierzytelniania i Nawigacja**:
  * PeĹ‚ne spolszczenie tras logowania, rejestracji, odzyskiwania hasĹ‚a oraz ustawiania nowego hasĹ‚a (np. `/logowanie`, `/rejestracja`, `/odzyskiwanie-hasla`, `/ustaw-nowe-haslo`), co zwiÄ™ksza spĂłjnoĹ›Ä‡ wizualnÄ… panelu.
  * **Zreorganizowana Nawigacja (Menu)**: Ujednolicona i czytelna struktura bocznego paska nawigacji podzielona na przejrzyste grupy: *Sklep i sprzedaĹĽ*, *Katalog produktĂłw*, *Klienci i kontakt*, *TreĹ›ci i marketing* oraz *System i logi*.
* **Odzyskiwanie porzuconych koszykĂłw**: Automatyczne wykrywanie nieukoĹ„czonych zamĂłwieĹ„ (Drafts) i wysyĹ‚ka e-maili przypominajÄ…cych po okreĹ›lonym czasie retencji (np. po 2 godzinach, do ustawienia w panelu/konfiguracyjnie), zintegrowana z szablonami e-mail w CMS. Rejestracja komendy `php artisan app:recover-abandoned-carts` uruchamianej cyklicznie w tle.
* **Audyt AktywnoĹ›ci (`AdminActivityLogs`)**:
  * Automatyczny rejestr dziaĹ‚aĹ„ podejmowanych przez administratorĂłw w panelu CMS za pomocÄ… **Eloquent Observers** (logowanie tworzenia, edycji z wykazem zmienionych pĂłl, kasowania oraz przywracania obiektĂłw, z zachowaniem prywatnoĹ›ci haseĹ‚ i poufnych danych).
* **BezpieczeĹ„stwo danych (SoftDeletes)**:
  * WdroĹĽony mechanizm bezpiecznego usuwania (`SoftDeletes`) dla kluczowych danych (zamĂłwienia, produkty, klienci, zwroty, strony, wpisy blogowe). Rekordy trafiajÄ… do wirtualnego kosza w panelu Filament CMS, z ktĂłrego administratorzy mogÄ… je przywracaÄ‡ lub trwale kasowaÄ‡.
* **Uwierzytelnianie dwuskĹ‚adnikowe (2FA - Filament Breezy)**:
  * PeĹ‚ne wsparcie dla weryfikacji dwuetapowej administratorĂłw w panelu CMS. MoĹĽliwoĹ›Ä‡ konfiguracji kodĂłw OTP (One-Time Password) przez aplikacje takie jak Google Authenticator bezpoĹ›rednio w zakĹ‚adce profilu uĹĽytkownika.
* **System kopii zapasowych (Backups)**:
  * Zintegrowany pakiet `spatie/laravel-backup` zabezpieczajÄ…cy bazÄ™ danych SQLite oraz przesĹ‚ane pliki (`storage/app/public` i `storage/app/private`). Komenda `php artisan backup:run` automatycznie generuje paczkÄ™ zip i zapisuje jÄ… w lokalnym repozytorium.
* **Szablony, Logi Maili & Integracji (`EmailTemplates`, `TransactionalEmailLogs`, `IntegrationLogs`)**:
  * Edytor szablonĂłw wiadomoĹ›ci transakcyjnych (potwierdzenia, dostawy cyfrowe, przypomnienia o koszyku) z placeholderami, podglÄ…d historii wysĹ‚anych wiadomoĹ›ci oraz log komunikacji bram pĹ‚atniczych.
* **Zadania w Tle (`FailedJobs`)**:
  * PodglÄ…d zadaĹ„ w kolejce, ktĂłre zakoĹ„czyĹ‚y siÄ™ niepowodzeniem, wraz z moĹĽliwoĹ›ciÄ… ich ponownego uruchomienia bezpoĹ›rednio z panelu.

---

## âš–ď¸Ź ZgodnoĹ›Ä‡ Prawna e-Commerce (Wymogi 2026)

Boilerplate jest w peĹ‚ni dostosowany do najnowszych unijnych i krajowych regulacji prawnych obowiÄ…zujÄ…cych w 2026 roku:

* **Elektroniczny przycisk odstÄ…pienia od umowy (RMA - Dyrektywa 2023/2673)**:
  * Uproszczony dwuetapowy proces odstÄ…pienia od umowy dostÄ™pny dla zalogowanych klientĂłw oraz goĹ›ci (bez konta) za poĹ›rednictwem publicznego API `POST /api/returns`.
  * Podanie przyczyny zwrotu jest caĹ‚kowicie opcjonalne.
  * System automatycznie rejestruje znacznik czasu zĹ‚oĹĽenia oĹ›wiadczenia i wysyĹ‚a trwaĹ‚e potwierdzenie e-mail (`OrderReturnConfirmationMail`) z wykazem zwracanych produktĂłw.
* **PrzejrzystoĹ›Ä‡ Promocji i Cen (Dyrektywa Omnibus dla wariantĂłw)**:
  * Automatyczna rejestracja historii cen regularnych i promocyjnych produktĂłw oraz ich wariantĂłw w bazie danych.
  * Dynamiczne obliczanie najniĹĽszej ceny w ostatnich 30 dniach przed promocjÄ… na poziomie produktu oraz wariantĂłw, zwracane bezpoĹ›rednio w API katalogu (`lowest_price_last_30_days`).
* **Status PrzedsiÄ™biorcy Uprzywilejowanego (JDG B2C)**:
  * ObsĹ‚uga oĹ›wiadczeĹ„ o braku zawodowego charakteru transakcji dla jednoosobowych dziaĹ‚alnoĹ›ci gospodarczych (JDG) w koszyku/checkout.
  * Zapisywanie flagi `is_privileged_entrepreneur` w bazie danych zamĂłwienia i jej prezentacja w panelu administratora Filament CMS (w widoku tabeli, szczegĂłĹ‚Ăłw infolist oraz edycji).
* **Zakaz Geoblokowania**:
  * Silnik wyceny i checkoutu pomija walidacjÄ™ stref dostawy dla koszykĂłw zawierajÄ…cych wyĹ‚Ä…cznie produkty cyfrowe lub usĹ‚ugi, zapobiegajÄ…c blokowaniu transakcji transgranicznych dla dĂłbr cyfrowych.
* **Rozliczenia UK VAT i Norwegia (VOEC)**:
  * Automatyczny pobĂłr podatkĂłw VAT w checkout poniĹĽej progĂłw B2C dla Wielkiej Brytanii (20% VAT dla przesyĹ‚ek $\le 135$ GBP) oraz Norwegii (25% VAT dla przesyĹ‚ek $\le 3000$ NOK za sztukÄ™). PowyĹĽej tych limitĂłw VAT wynosi 0% (przeniesienie cĹ‚a/podatku na odbiorcÄ™).
* **CĹ‚o importowe UE (WymĂłg od 1 lipca 2026)**:
  * System automatycznie wylicza i dolicza ryczaĹ‚towe cĹ‚o importowe w wysokoĹ›ci 3 EUR (przeliczone na walutÄ™ docelowÄ… koszyka) za kaĹĽdÄ… unikalnÄ… kategoriÄ™ taryfowÄ… (kod HS) dla paczek wysyĹ‚anych spoza obszaru celnego UE do konsumentĂłw w UE (z moĹĽliwoĹ›ciÄ… wĹ‚Ä…czenia/wyĹ‚Ä…czenia w panelu).
* **OgĂłlne BezpieczeĹ„stwo ProduktĂłw (GPSR)**:
  * Gotowe pola CMS i atrybuty API do opisu producenta, osoby odpowiedzialnej w UE, ostrzeĹĽeĹ„ o ryzyku oraz pliku PDF z instrukcjÄ… bezpieczeĹ„stwa dla produktĂłw fizycznych.
* **Rejestr ZgĂłd Cookies (Consent Log - RODO)**:
  * Rejestrowanie w bazie danych logĂłw zgĂłd na ciasteczka (`cookie_consents`) przesĹ‚anych z banera frontendowego przez API `POST /api/cookie-consents` (unikalny token sesji, granularne wybory analityka/marketing/funkcjonalne, wersja banera, znacznik czasu, user agent).
  * Czytelny, dedykowany podglÄ…d logĂłw zgĂłd w panelu Filament CMS (`CookieConsentResource`) na potrzeby audytu i kontroli UODO.

---

## đź’» Dedykowane Komendy CLI (Artisan)

Aplikacja udostÄ™pnia zestaw autorskich poleceĹ„ CLI wspierajÄ…cych zarzÄ…dzanie, importy oraz automatyzacjÄ™ zadaĹ„:

* **ZarzÄ…dzanie administratorami**:

  ```bash
  php artisan app:make-admin-user {email} --name="Nazwa" --password="HasĹ‚o" --promote-existing
  ```

  Tworzy nowe konto administratora z dostÄ™pem do panelu Filament lub nadaje uprawnienia admina (`is_admin = true`) dla istniejÄ…cego konta (wymaga flagi `--promote-existing`).
* **Uniwersalny Import JSON**:

  ```bash
  php artisan app:import-shop-json {dataset} {sciezka/do/pliku.json} --dry-run
  ```

  Pozwala na masowy import danych do bazy. Przydatne przy migracji lub zasilaniu bazy.
  ObsĹ‚ugiwane datasety (`dataset`): `products`, `product-categories`, `content-pages`, `blog-posts`, `faq-items`, `coupons`, `newsletter-subscribers`, `redirect-rules`, `customers`, `orders`. Flaga `--dry-run` wykonuje walidacjÄ™ bez zapisywania w bazie.
* **Agregacja Statystyk (Dobowa)**:

  ```bash
  php artisan app:aggregate-analytics-daily --date="2026-06-14"
  ```

  Wylicza dobowe raporty zagregowane (wizyty, odsĹ‚ony, checkouty, zakupy, najpopularniejsze produkty, kampanie UTM) z surowych eventĂłw analitycznych. DomyĹ›lnie przetwarza dane z wczoraj. Jest automatycznie wywoĹ‚ywana codziennie w tle o godzinie `02:15`.
* **Import opinii Google Places**:

  ```bash
  php artisan app:import-google-reviews-snapshot {sciezka/do/pliku-opinii.json} --dry-run
  ```

  Wczytuje lokalnÄ… kopiÄ™ 5-gwiazdkowych opinii Google Places i zapisuje je w bazie ustawieĹ„ sklepu. Przydatne na Ĺ›rodowisku deweloperskim w celu unikniÄ™cia pĹ‚atnych zapytaĹ„ do API Google Places.
* **Odzyskiwanie Porzuconych KoszykĂłw**:

  ```bash
  php artisan app:recover-abandoned-carts
  ```

  Wyszukuje koszyki (zamĂłwienia o statusie szkic/draft) porzucone przez klientĂłw na czas dĹ‚uĹĽszy niĹĽ zdefiniowany prĂłg retencji (domyĹ›lnie 2 godziny) i wysyĹ‚a do nich e-maile przypominajÄ…ce. Jest automatycznie uruchamiana co godzinÄ™ w tle.
* **Czyszczenie Historii Cen (Omnibus)**:

  ```bash
  php artisan app:cleanup-price-history
  ```

  Usuwa stare wpisy z historii cen produktĂłw (starsze niĹĽ 90 dni) w celu optymalizacji i czyszczenia bazy danych, w peĹ‚ni zachowujÄ…c 30-dniowe wymogi dyrektywy Omnibus. Jest automatycznie uruchamiana raz na dobÄ™ w tle.
* **Retencja Danych Newslettera (RODO)**:

  ```bash
  php artisan app:cleanup-pending-subscribers {--days=14}
  ```

  Bezpowrotnie usuwa z bazy danych adresy e-mail ze statusem `pending` (niepotwierdzone Double Opt-In), ktĂłre zostaĹ‚y dodane dawniej niĹĽ okreĹ›lona liczba dni (domyĹ›lnie 14). Pozwala na przestrzeganie zasad retencji danych osobowych RODO. Jest automatycznie uruchamiana raz w tygodniu w tle.
* **Czyszczenie Porzuconych SzkicĂłw KoszykĂłw**:

  ```bash
  php artisan app:cleanup-abandoned-carts {--days=30}
  ```

  Automatycznie usuwa z bazy danych nieukoĹ„czone zamĂłwienia o statusie `draft` (szkice koszykĂłw) starsze niĹĽ okreĹ›lona liczba dni (domyĹ›lnie 30). Zapobiega nadmiernemu rozrostowi tabeli zamĂłwieĹ„ i spowalnianiu bazy danych. Jest automatycznie uruchamiana raz na dobÄ™ w tle.

---

## đźš€ Szybki Start (Quick Start)

Po skopiowaniu tej paczki do nowego katalogu projektu, wykonaj nastÄ™pujÄ…ce kroki:

### 1. Przygotowanie Ĺ›rodowiska i zaleĹĽnoĹ›ci

Zainstaluj paczki PHP oraz wygeneruj lokalny plik Ĺ›rodowiskowy `.env`:

```bash
composer install
copy .env.example .env
php artisan key:generate
```

### 2. Konfiguracja bazy danych

DomyĹ›lnie aplikacja jest skonfigurowana pod SQLite. StwĂłrz pusty plik bazy danych:

```bash
# W Windows PowerShell:
New-Item -ItemType File -Path "database/database.sqlite" -Force
```

*Uwaga: JeĹ›li wolisz MySQL/PostgreSQL, zmieĹ„ dane w pliku `.env`.*

### 3. Migracje i dane demonstracyjne

Uruchom migracje bazy danych oraz zasiej przykĹ‚adowe dane (kategorie, produkty demo, podstawowe strony CMS, konto administratora oraz domyĹ›lne szablony e-mail):

```bash
php artisan migrate --seed --seeder=DevCmsReviewSeeder
```

*(Uwaga: DomyĹ›lne szablony e-mail moĹĽna rĂłwnieĹĽ zasiedliÄ‡ osobno komendÄ… `php artisan db:seed --class=EmailTemplateSeeder`)*

DomyĹ›lne dane logowania do panelu administratora:

- **URL panelu**: `/admin` (np. `http://localhost:8000/admin`)
- **Login**: `admin@genericshop.local`
- **HasĹ‚o**: `Admin1234!`

### 4. Kompilacja assetĂłw i uruchomienie serwera deweloperskiego

Zainstaluj zaleĹĽnoĹ›ci frontendu i zbuduj produkcyjne assety panelu Filament:

```bash
npm install
npm run build
```

Aby uruchomiÄ‡ peĹ‚ne Ĺ›rodowisko deweloperskie (serwer HTTP, kompilator Vite HMR, worker kolejek oraz debugger logĂłw Pail) w jednym terminalu, skorzystaj z przygotowanego skrĂłtu:

```bash
composer run dev
```

*Alternatywnie (szybka instalacja automatyczna):*
Po pobraniu repozytorium i utworzeniu pliku bazy SQLite moĹĽesz uruchomiÄ‡ pojedynczÄ… komendÄ™ instalacyjnÄ…:

```bash
composer run setup
```

### 5. Uruchomienie testĂłw automatycznych i testu dymnego (Smoke Test)

Boilerplate posiada peĹ‚ny pakiet testĂłw integracyjnych i bezpieczeĹ„stwa.

MoĹĽesz przeprowadziÄ‡ kompletny test dymny (Smoke Test), ktĂłry automatycznie zweryfikuje konfiguracjÄ™ Ĺ›rodowiska, bazy danych, pobierze ewentualne brakujÄ…ce zaleĹĽnoĹ›ci i uruchomi testy:

```bash
# Na systemach Linux/macOS/Git Bash:
npm run smoke

# Na systemie Windows (PowerShell):
npm.cmd run smoke
```

*Uwaga: PowyĹĽszy skrypt uruchamia w tle plik [local-smoke-test.sh](file:///d:/Projekty/_BOILERPLATE/laravel-filament-ecommerce-boilerplate/scripts/local-smoke-test.sh).*

Alternatywnie moĹĽesz uruchomiÄ‡ standardowe testy PHPUnit za pomocÄ…:

```bash
php artisan test
```

Aby przetestowaÄ‡ wyĹ‚Ä…cznie reguĹ‚y bezpieczeĹ„stwa (np. rate-limiting, weryfikacjÄ™ e-mail, autoryzacjÄ™ zamĂłwieĹ„), uĹĽyj:

```bash
php artisan test --filter=SecurityTest
```

---

## âš™ď¸Ź Kluczowa Konfiguracja (`.env` i `config/`)

### đź—„ď¸Ź SQLite i tryb WAL (Write-Ahead Logging)

DomyĹ›lna konfiguracja bazy danych pod SQLite zostaĹ‚a zoptymalizowana pod kÄ…tem wspĂłĹ‚bieĹĽnoĹ›ci zapisu i odczytu (np. podczas finalizacji zamĂłwieĹ„ przy jednoczesnym dziaĹ‚aniu kolejki zadaĹ„ DB w tle na hostingu wspĂłĹ‚dzielonym):

* **Tryb WAL (`journal_mode=wal`)**: Zapobiega blokowaniu bazy danych (bĹ‚Ä™dy typu *database is locked*) przy wspĂłĹ‚bieĹĽnych operacjach zapisu.
* **Synchronous normal (`synchronous=normal`)**: Zmniejsza narzut operacji I/O przy wĹ‚Ä…czonym WAL bez utraty spĂłjnoĹ›ci.
* **Busy Timeout (`busy_timeout=5000`)**: Wymusza na SQLite oczekiwanie do 5 sekund na odblokowanie bazy przed zwrĂłceniem bĹ‚Ä™du.

Zmienne konfiguracyjne w `.env`:

```env
DB_BUSY_TIMEOUT=5000
DB_JOURNAL_MODE=wal
DB_SYNCHRONOUS=normal
```

### đź›’ Konfiguracja Sklepu (`config/shop.php`)

To gĹ‚Ăłwny plik konfiguracji biznesowej sklepu. MoĹĽesz tu zdefiniowaÄ‡:

- NazwÄ™ sklepu i walutÄ™ (domyĹ›lnie `PLN`)
- PrĂłg darmowej dostawy (`free_shipping_threshold` w groszach)
- Segmenty klientĂłw (`loyal_5`, `loyal_8`, `wholesale_30` itp.)
- DostÄ™pne metody dostawy (InPost, DPD, kurierzy wraz z cennikiem i flagami COD)

> [!NOTE]
> Istnieje rĂłĹĽnica w nazewnictwie kluczy segmentacji klientĂłw: w pliku konfiguracyjnym uĹĽywane sÄ… klucze angielskie (`loyal_5`, `loyal_8`, `wholesale_30`), natomiast baza danych, kontrolery API oraz walidacja ĹĽÄ…daĹ„ korzystajÄ… bezpoĹ›rednio z wartoĹ›ci enuma [CustomerSegment.php](file:///d:/Projekty/_BOILERPLATE/laravel-filament-ecommerce-boilerplate/app/Domain/Commerce/Enums/CustomerSegment.php) w jÄ™zyku polskim: `staly_klient_5`, `staly_klient_8` oraz `hurt_30`. Podczas wysyĹ‚ania ĹĽÄ…daĹ„ do API (np. `/api/quote` lub `/api/checkout/draft`) naleĹĽy posĹ‚ugiwaÄ‡ siÄ™ polskimi wartoĹ›ciami enuma.

### đź’ł PĹ‚atnoĹ›ci Stripe

WĹ‚Ä…czana w `.env`:

```env
STRIPE_ENABLED=true
STRIPE_KEY="twoj-klucz-publiczny"
STRIPE_SECRET="twoj-klucz-prywatny"
STRIPE_WEBHOOK_SECRET="twoj-sekret-webhooka"
```

Aplikacja obsĹ‚uguje webhooki Stripe pod adresem `/api/integrations/stripe/payment-callback` (np. do potwierdzania statusu pĹ‚atnoĹ›ci przez sesje Checkout).

### đź’ł PĹ‚atnoĹ›ci Przelewy24

WĹ‚Ä…czana w `.env`:

```env
PRZELEWY24_ENABLED=true
PRZELEWY24_MERCHANT_ID=XXXXX
PRZELEWY24_POS_ID=XXXXX
PRZELEWY24_CRC="twoj-kod-crc"
PRZELEWY24_API_KEY="twoj-klucz-api"
PRZELEWY24_API_BASE_URL="https://sandbox.przelewy24.pl/api/v1"
```

### â­ď¸Ź Opinie Google & Opinie ze Strony

Aplikacja potrafi pobieraÄ‡ opinie z wizytĂłwki Google lub Ĺ‚Ä…czyÄ‡ je z recenzjami wprowadzonymi bezpoĹ›rednio w sklepie.
Ustawienia klucza API Google i nazwy biznesu w `.env`:

```env
GOOGLE_PLACES_API_KEY="twoj-klucz-google-api"
GOOGLE_PLACES_BUSINESS_NAME="Moja Firma Sp. z o.o."
```

JeĹ›li chcesz zaimportowaÄ‡ statyczny backup opinii Google (przydatne przy braku klucza API na Ĺ›rodowisku lokalnym):

```bash
php artisan app:import-google-reviews-snapshot sciezka/do/pliku-opinii.json
```

### âŹ° Odzyskiwanie Porzuconych KoszykĂłw

Proces odzyskiwania dziaĹ‚a w oparciu o komendÄ™ Artisan wywoĹ‚ywanÄ… automatycznie co godzinÄ™:

```bash
php artisan app:recover-abandoned-carts
```

Ustawienia progĂłw czasowych retencji oraz wĹ‚Ä…czenie wysyĹ‚ki moĹĽna kontrolowaÄ‡ w pliku `config/shop.php` pod kluczem `abandoned_cart`:

* `hours_threshold`: po ilu godzinach koszyk (Draft) uznawany jest za porzucony (domyĹ›lnie `2`).
* `enabled`: czy automatyczna wysyĹ‚ka jest wĹ‚Ä…czona.

### đź—şď¸Ź Strefy wysyĹ‚kowe & Walidacja krajĂłw

Konfiguracja stref odbywa siÄ™ bezpoĹ›rednio z poziomu panelu Filament w sekcji **Ustawienia Sklepu -> WysyĹ‚ka i pĹ‚atnoĹ›ci**.

* JeĹ›li nie zostanÄ… skonfigurowane ĹĽadne strefy, sklep domyĹ›lnie dopuszcza wysyĹ‚kÄ™ **tylko do Polski (PL)** za standardowÄ… kwotÄ™ podanÄ… przy danej metodzie wysyĹ‚ki.
* JeĹ›li administrator skonfiguruje strefy wysyĹ‚ki (np. wprowadzajÄ…c kody ISO takie jak DE, FR, GB), klienci mogÄ… skĹ‚adaÄ‡ zamĂłwienia do tych krajĂłw pod warunkiem przypisania odpowiedniej ceny nadpisujÄ…cej (ceny strefowej) dla wybranej metody dostawy. Kraje nieobsĹ‚ugiwane przez ĹĽadnÄ… strefÄ™ bÄ™dÄ… automatycznie odrzucane podczas walidacji zamĂłwienia w koszyku.

### đźšš Integracje WysyĹ‚kowe (InPost i ORLEN Paczka)

Dane uwierzytelniajÄ…ce i adresowe nadawcy dla integracji kurierskich konfiguruje siÄ™ za pomocÄ… zmiennych `.env`:

* **InPost (ShipX)**:
  ```env
  INPOST_ORGANIZATION_ID="twoje-id-organizacji"
  INPOST_TOKEN="twoj-token-api"
  INPOST_SANDBOX=true # true dla testĂłw, false na produkcji
  ```
* **ORLEN Paczka (SOAP)**:
  ```env
  ORLEN_PACZKA_PARTNER_ID="twoje-id-partnera"
  ORLEN_PACZKA_PARTNER_KEY="twoj-klucz-partnera"
  ORLEN_PACZKA_SANDBOX=true # true dla testĂłw SOAP, false na produkcji
  ```

### đźŹ˘ Integracje KsiÄ™gowe (Fakturownia, iFirma, inFakt, wFirma)

Aktywne integracje w [config/accounting.php](file:///d:/Projekty/_BOILERPLATE/laravel-filament-ecommerce-boilerplate/config/accounting.php) automatycznie odbierajÄ… dane opĹ‚aconych zamĂłwieĹ„ i generujÄ… dokumenty sprzedaĹĽy w zewnÄ™trznych serwisach:

```env
# Fakturownia
ACCOUNTING_FAKTUROWNIA_ENABLED=false
ACCOUNTING_FAKTUROWNIA_API_TOKEN="token"
ACCOUNTING_FAKTUROWNIA_DOMAIN="twoja-subdomena"

# iFirma
ACCOUNTING_IFIRMA_ENABLED=false
ACCOUNTING_IFIRMA_API_KEY="klucz-api"
ACCOUNTING_IFIRMA_USERNAME="email-wlasciciela"

# inFakt
ACCOUNTING_INFAKT_ENABLED=false
ACCOUNTING_INFAKT_API_KEY="klucz-api"

# wFirma
ACCOUNTING_WFIRMA_ENABLED=false
ACCOUNTING_WFIRMA_API_KEY="haslo-api"
ACCOUNTING_WFIRMA_ACCESS_KEY="klucz-dostepu-uzytkownika"
```

### đź“§ BezpieczeĹ„stwo Poczty w Ĺšrodowiskach Testowych (Mail Safety Redirect)

Aby zapobiec przypadkowemu wysyĹ‚aniu maili (np. przypomnieĹ„ o koszykach, newslettera) do prawdziwych klientĂłw podczas testĂłw na Ĺ›rodowisku deweloperskim lub staging, moĹĽna przekierowaÄ‡ caĹ‚Ä… wychodzÄ…cÄ… pocztÄ™ na jeden wybrany adres:

```env
MAIL_REDIRECT_TO="testy@twojadomena.pl"
```

JeĹĽeli zmienna jest pusta, maile bÄ™dÄ… dostarczane bezpoĹ›rednio do oryginalnych odbiorcĂłw.

### đźŤŞ Analityka, Tracking, Ciasteczka i OgĹ‚oszenia (Public API)

ZarzÄ…dzanie skryptami Ĺ›ledzÄ…cymi, banerem zgodnoĹ›ci (Cookie Banner) oraz belkÄ… ogĹ‚oszeĹ„ (Announcement Bar) odbywa siÄ™ w panelu **Ustawienia Sklepu**. Dane te sÄ… automatycznie udostÄ™pniane dla zewnÄ™trznego frontendu (np. Astro) za pomocÄ… dedykowanego, bezstanowego publicznego endpointu API:

* **GET `/api/store/settings`** â€” zwraca czysty obiekt JSON zawierajÄ…cy m.in. nazwÄ™ sklepu, walutÄ™, prĂłg darmowej dostawy, konfiguracjÄ™ analityki i cookies (wĹ‚Ä…cznik baneru, Google Tag Manager ID, Google Analytics ID, Facebook Pixel ID, nagĹ‚Ăłwek i treĹ›Ä‡ baneru oraz niestandardowe skrypty `custom_head_scripts`) oraz ustawienia ogĹ‚oszeĹ„ (`announcement_enabled`, `announcement_text`) bez ĹĽadnych sekretnych kluczy API.

### đź“Š Autorska Analityka Pierwszej Strony (First-party Analytics)

System zbierania surowych zdarzeĹ„ analitycznych z frontendu (poprzez `POST /api/analytics/events`) konfiguruje siÄ™ w `.env`:

```env
ANALYTICS_ENABLED=true # WĹ‚Ä…cza system zbierania zdarzeĹ„
ANALYTICS_ACCEPTED_ENVIRONMENTS=production,staging # Ĺšrodowiska, z ktĂłrych zdarzenia sÄ… zapisywane w bazie
```

### đź”” Powiadomienia w Czasie Rzeczywistym & WebSockets (Laravel Reverb)

Konfiguracja serwera WebSocket Reverb dla powiadomieĹ„ w tle i toastĂłw w panelu Filament odbywa siÄ™ przez zmienne `.env`:

```env
REVERB_APP_ID=554226
REVERB_APP_KEY=sotes759n4rmf701rqp7
REVERB_APP_SECRET=ofhyxj9yy8oi2gv8i1j5
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### đź”Ś Wykaz EndpointĂłw API (`routes/api.php`)

System udostÄ™pnia kompletne, bezstanowe REST API przygotowane pod integracjÄ™ z nowoczesnym frontendem w technologii **Astro**:

#### âš™ď¸Ź Ustawienia i Status

* **GET `/api/health`** â€” Zwraca status kondycji operacyjnej (baza danych, integracje).
* **GET `/api/store/settings`** â€” Podstawowa konfiguracja sklepu (waluta, darmowa dostawa, tracking, cookies, ogĹ‚oszenia, status guest checkout).
* **POST `/api/analytics/events`** â€” Ingestion (przesyĹ‚anie) surowych zdarzeĹ„ analitycznych z frontendu (np. wejĹ›cia, przejĹ›cie do kasy, klikniÄ™cia). Zabezpieczony przed spamem (maks. 60/min z jednego IP).

#### đź›’ Katalog, Opinie i Magazyn

* **GET `/api/catalog`** â€” Pobieranie listy produktĂłw (obsĹ‚uguje wyszukiwanie tekstowe `query`, warianty, Omnibus, sortowanie i zaawansowane filtry np. `?category=slug`, `?price_min=100`, `?price_max=500`, atrybuty dynamiczne oraz paginacjÄ™).
* **GET `/api/catalog/search/suggest`** — Autouzupełnianie i sugestie wyszukiwania (zwraca słowa kluczowe, dopasowane kategorie oraz produkty z uwzględnieniem cen indywidualnych).
* **GET `/api/catalog/products/{slug}/recommendations`** — Rekomendacje dla danego produktu (zwraca ręcznie powiązane produkty typu upsell/cross-sell/podobne oraz rekomendacje dynamiczne "Kupowane razem" oparte na regułach Collaborative Filtering z historii zamówień).
* **GET `/api/catalog/products/{slug}`** â€” SzczegĂłĹ‚owe informacje o produkcie (warianty, opcje, Omnibus, powiÄ…zane produkty/rekomendacje, struktura JSON-LD dla wyszukiwarek).
* **GET `/api/catalog/products/{slug}/reviews`** â€” Pobieranie opinii o produkcie.
* **POST `/api/catalog/products/{slug}/reviews`** â€” Dodawanie recenzji o produkcie z automatycznÄ… weryfikacjÄ… zakupu na dany e-mail.
* **GET `/api/reviews/google`** â€” Pobieranie opinii Google Places poĹ‚Ä…czonych z opiniami wewnÄ™trznymi (caching, fallback na snapshot, JSON-LD `LocalBusiness`).
* **GET `/api/inventory/{sku}`** â€” Sprawdzenie aktualnej iloĹ›ci i dostÄ™pnoĹ›ci magazynowej, lokalizacji towaru oraz statusu synchronizacji dla konkretnego SKU.
* **GET `/api/account/wishlist`** â€” Pobieranie produktĂłw z listy ĹĽyczeĹ„ zalogowanego klienta.
* **POST `/api/account/wishlist`** â€” Dodanie produktu do listy ĹĽyczeĹ„.
* **DELETE `/api/account/wishlist/{productId}`** â€” UsuniÄ™cie produktu z listy ĹĽyczeĹ„.
* **POST `/api/catalog/products/back-in-stock-subscribe`** â€” Zapisanie adresu e-mail klienta na powiadomienie o powrocie wyprzedanego produktu lub wariantu do magazynu.

#### đź“ť CMS i Nawigacja

* **GET `/api/content/map`** â€” Zwraca peĹ‚nÄ… strukturÄ™ aktywnych stron CMS, grup FAQ i bloga na potrzeby routera Astro.
* **GET `/api/content/pages`** & **GET `/api/content/pages/{slug}`** â€” Strony statyczne.
* **GET `/api/blog/posts`** & **GET `/api/blog/posts/{slug}`** â€” Lista i szczegĂłĹ‚y wpisĂłw blogowych (biogram autora, bibliografia E-E-A-T, JSON-LD).
* **GET `/api/faq`** â€” Baza najczÄ™stszych pytaĹ„ i odpowiedzi (generuje JSON-LD `FAQPage`).
* **GET `/api/redirects/resolve`** â€” Inteligentny parser przekierowaĹ„ 301, rozwiÄ…zujÄ…cy dawne slugi na aktualne Ĺ›cieĹĽki.

#### đź“¬ Newsletter

* **POST `/api/newsletter/subscribe`** â€” Zapis do newslettera (zabezpieczony Double Opt-In z mailem aktywacyjnym).
* **POST `/api/newsletter/unsubscribe`** â€” Wypisanie siÄ™ z newslettera (Opt-out, wymaga parametru `email`, zmienia status na `unsubscribed`).
* **GET `/newsletter/unsubscribe/{email}`** (Web) â€” Bezpieczny, podpisany kryptograficznie link wyrejestrowania (Opt-out) z stopki mailowej.
* **GET `/newsletter/confirm/{token}`** (Web) â€” Potwierdzenie zapisu do newslettera (Double Opt-In) po klikniÄ™ciu w link weryfikacyjny. Zmienia status subskrybenta na `active` i loguje IP/czas.

#### đź’ł Wycena i Koszyk (Checkout)

* **POST `/api/quote`** â€” Bezstanowe wyliczenie wartoĹ›ci koszyka w locie (ceny, strefy wysyĹ‚ki, kupony rabatowe, VAT OSS).
* **POST `/api/checkout/draft`** â€” Zapisanie stanu koszyka jako szkicu zamĂłwienia (Draft) w bazie danych.
* **POST `/api/checkout/place`** â€” ZĹ‚oĹĽenie i finalizacja zamĂłwienia (wymaga akceptacji zgĂłd i regulaminĂłw).
* **GET `/api/checkout/orders/{number}`** â€” Pobieranie aktualnego statusu zĹ‚oĹĽonego zamĂłwienia.
* **POST `/api/checkout/orders/{number}/payment-session`** â€” Rejestracja transakcji i wygenerowanie sesji pĹ‚atnoĹ›ci (Stripe, Przelewy24, w tym Direct BLIK).
* **GET `/api/b2b/gus/{nip}`** â€” Pobieranie danych kontrahenta z GUS BIR i BiaĹ‚ej Listy MF z automatycznym fallbackiem.

#### âš–ď¸Ź ZgodnoĹ›Ä‡, Zwroty (RMA) i Zgody (RODO)

* **POST `/api/returns`** â€” ZgĹ‚oszenie zwrotu (RMA) dla goĹ›ci oraz zalogowanych klientĂłw. Wymaga przesĹ‚ania numeru zamĂłwienia, adresu e-mail, opcjonalnego powodu zwrotu oraz listy zwracanych wariantĂłw z iloĹ›ciami.
* **GET `/api/account/returns/{orderReturn}`** â€” Pobranie szczegĂłĹ‚owych danych konkretnego zgĹ‚oszenia zwrotu (RMA) zalogowanego klienta.
* **POST `/api/cookie-consents`** â€” Rejestracja w logu audytowym zgĂłd RODO na ciasteczka (funkcjonalne, analityczne, marketingowe).

#### đź‘¤ Konto Klienta (Autoryzowane via Sanctum)

* **POST `/api/auth/login`** â€” Logowanie klienta.
* **POST `/api/auth/register`** â€” Rejestracja nowego konta.
* **POST `/api/auth/logout`** â€” Wylogowanie klienta (uniewaĹĽnienie bieĹĽÄ…cego tokenu Sanctum).
* **POST `/api/auth/forgot-password`** â€” Inicjowanie procedury resetowania hasĹ‚a (generowanie tokenu i wysyĹ‚ka e-maila).
* **POST `/api/auth/reset-password`** â€” Zmiana/reset hasĹ‚a na podstawie tokenu wysĹ‚anego w e-mailu.
* **POST `/api/auth/email/resend`** â€” Ponowne wysĹ‚anie linku aktywacyjnego/weryfikacyjnego na e-mail klienta.
* **GET `/api/auth/email/verify/{id}/{hash}`** â€” Weryfikacja adresu e-mail za pomocÄ… podpisanego URL-a z maila.
* **GET `/api/account/me`** â€” Dane zalogowanego uĹĽytkownika (profil, segment lojalnoĹ›ciowy/hurtowy, zgody marketingowe).
* **GET `/api/account/orders`** â€” Historia zamĂłwieĹ„ klienta.
* **GET `/api/account/addresses`** â€” Pobieranie listy adresĂłw zalogowanego klienta.
* **POST `/api/account/addresses`** â€” Dodanie nowego adresu (ze wsparciem dla domyĹ›lnego adresu wysyĹ‚ki/rozliczenia).
* **PUT `/api/account/addresses/{id}`** â€” Aktualizacja wybranego adresu.
* **DELETE `/api/account/addresses/{id}`** â€” UsuniÄ™cie adresu.
* **GET `/api/account/returns`** â€” Pobieranie listy zgĹ‚oszonych zwrotĂłw (RMA) zalogowanego klienta.

---

### âšˇ Optymalizacja (WPO), DostÄ™pnoĹ›Ä‡ (WCAG) & BezpieczeĹ„stwo (Hardening)

Szablon zawiera wbudowane mechanizmy optymalizacji wydajnoĹ›ci bazy danych, dostÄ™pnoĹ›ci dla czytnikĂłw ekranowych oraz zaawansowane zabezpieczenia sieciowe.

#### 1. Optymalizacja WydajnoĹ›ci (WPO)

* **Eliminacja zapytaĹ„ N+1**: Endpointy katalogu produktĂłw (`/api/catalog`) zostaĹ‚y w peĹ‚ni zoptymalizowane za pomocÄ… Eloquent eager loading oraz podzapytaĹ„ select. Wyszukiwanie danych Omnibus, licznikĂłw opinii i ocen wykonuje staĹ‚Ä… liczbÄ™ 3 zapytaĹ„ ($O(1)$) zamiast setek operacji na bazie ($O(N)$).
* `TRACK_REDIRECT_HITS=true` â€” WĹ‚Ä…cza Ĺ›ledzenie statystyk przekierowaĹ„ URL. Ustawienie `false` wyĹ‚Ä…cza zapisy do bazy danych przy kaĹĽdym wywoĹ‚aniu starego adresu URL, co eliminuje narzut zapisu (przydatne przy duĹĽym natÄ™ĹĽeniu ruchu).

#### 2. DostÄ™pnoĹ›Ä‡ (WCAG 2.1 AA) i Europejski Akt o DostÄ™pnoĹ›ci (EAA)

* **Ekrany administracyjne Filament**: Widoki dashboardu i statusu operacyjnego zostaĹ‚y zoptymalizowane pod kÄ…tem czytnikĂłw ekranowych za pomocÄ… dedykowanych etykiet `aria-label` i rĂłl ARIA dla blokĂłw interaktywnych.
* **Wymagania dla Frontendu Astro (ObowiÄ…zkowe standardy WCAG 2.1 AA)**: Szablon Astro zintegrowany z tym API musi bezwzglÄ™dnie speĹ‚niaÄ‡ wymogi EAA (obowiÄ…zujÄ…ce od 28 czerwca 2025 r.):
  * **PeĹ‚na nawigacja klawiaturÄ…**: Wszystkie interaktywne elementy (przyciski, linki, pola formularzy) muszÄ… posiadaÄ‡ czytelny, widoczny fokus (`:focus-visible`) i byÄ‡ w peĹ‚ni sterowalne tabulatorem.
  * **Kontrast tekstu i grafiki**: Stosunek jasnoĹ›ci tekstu do tĹ‚a musi wynosiÄ‡ minimum 4.5:1 (lub 3:1 dla duĹĽego tekstu).
  * **Wsparcie dla czytnikĂłw ekranu (Semantyka HTML)**: Stosowanie poprawnej struktury nagĹ‚ĂłwkĂłw (`<h1>` do `<h6>`), uĹĽywanie atrybutĂłw `aria-invalid`, `aria-required`, `aria-describedby` dla bĹ‚Ä™dĂłw walidacji oraz dostarczanie tekstĂłw alternatywnych (`alt`) zwracanych przez API mediĂłw.
  * **Brak elementĂłw migajÄ…cych**: Ĺ»aden element nie moĹĽe migaÄ‡ szybciej niĹĽ 3 razy na sekundÄ™ (zapobieganie atakom padaczki).
  * **Etykiety przyciskĂłw checkoutu z obowiÄ…zkiem zapĹ‚aty**: Zgodnie z ustawÄ… o prawach konsumenta, przycisk finalizujÄ…cy zamĂłwienie na froncie musi jasno okreĹ›laÄ‡ obowiÄ…zek zapĹ‚aty. W szablonie Astro naleĹĽy stosowaÄ‡ wyĹ‚Ä…cznie precyzyjne sformuĹ‚owania, np.: **"KupujÄ™ i pĹ‚acÄ™"** lub **"ZamĂłwienie z obowiÄ…zkiem zapĹ‚aty"** (zabronione sÄ… zwroty typu "Dalej", "WyĹ›lij", "Kup teraz" itp.).
  * **Link do Platformy ODR w stopce i regulaminie**: KaĹĽdy sklep wdroĹĽony na tym boilerplate ma obowiÄ…zek umieszczenia w widocznym miejscu (stopka witryny) oraz w regulaminie aktywnego, klikalnego linku do europejskiego systemu ODR (rozstrzyganie sporĂłw konsumenckich) pod adresem: `https://ec.europa.eu/consumers/odr`.

#### 3. BezpieczeĹ„stwo i Hardening

Ustawienia zabezpieczeĹ„ moĹĽna kontrolowaÄ‡ poprzez zmienne Ĺ›rodowiskowe w `.env`:

* `SESSION_ENCRYPT=true` â€” Szyfruje dane sesji w bazie danych (zalecane na produkcji).
* `SESSION_SECURE_COOKIE=true` â€” Wymusza ciasteczka sesji wyĹ‚Ä…cznie przez HTTPS.
* `SESSION_SAME_SITE=strict` â€” Blokuje ataki CSRF.
* `FILAMENT_PATH=admin` â€” Zmienia domyĹ›lny URL panelu administratora (zabezpieczenie przed botami).
* `SANCTUM_TOKEN_EXPIRATION=10080` â€” Czas waĹĽnoĹ›ci tokenĂłw API dla klientĂłw (w minutach, domyĹ›lnie 7 dni).
* `ALLOWED_ORIGINS=http://localhost:3000` â€” Lista dozwolonych domen (CORS) oddzielona przecinkami.
* `FRONTEND_URL=http://localhost:3000` â€” Adres aplikacji frontendowej, uĹĽywany m.in. przy weryfikacji e-mail.
* `STOREFRONT_URL=http://localhost:3000` â€” Adres witryny sklepowej, uĹĽywany do przekierowaĹ„ zwrotnych z bramki pĹ‚atniczej (Stripe/Przelewy24).
* `AUTH_PASSWORD_RESET_EXPIRE=15` â€” Czas waĹĽnoĹ›ci linku resetowania hasĹ‚a (w minutach).
* `AUTH_PASSWORD_TIMEOUT=3600` â€” Czas, po ktĂłrym ponowne podanie hasĹ‚a jest wymagane do wraĹĽliwych akcji (w sekundach).
* `ADD_SECURITY_HEADERS=true` â€” Dodaje nagĹ‚Ăłwki bezpieczeĹ„stwa HTTP na poziomie PHP middleware (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`).
* `CONTENT_SECURITY_POLICY="..."` â€” Definiuje nagĹ‚Ăłwek Content Security Policy (CSP). DomyĹ›lna konfiguracja pozwala na zaĹ‚adowanie skryptĂłw i stylĂłw panelu Filament (Alpine.js, Livewire) oraz osadzanie filmĂłw z YouTube (`frame-src` na youtube.com).
* `COOP_HEADER=same-origin` â€” NagĹ‚Ăłwek `Cross-Origin-Opener-Policy` chroniÄ…cy przed wyciekami okien cross-origin.
* `CORP_HEADER=same-origin` â€” NagĹ‚Ăłwek `Cross-Origin-Resource-Policy` okreĹ›lajÄ…cy politykÄ™ wspĂłĹ‚dzielenia zasobĂłw.
* **Rate-limiting analityki**: Endpoint `/api/analytics/events` jest domyĹ›lnie zabezpieczony za pomocÄ… middleware `throttle:60,1` (maksymalnie 60 ĹĽÄ…daĹ„ na minutÄ™ z jednego adresu IP), zapobiegajÄ…c atakom DOS na bazÄ™ danych.

### âś‰ď¸Ź DostarczalnoĹ›Ä‡ i Reputacja Poczty (SMTP & List-Unsubscribe)

Aby newslettery, maile weryfikacyjne oraz transakcyjne docieraĹ‚y do skrzynek odbiorcĂłw bez wpadania do folderu SPAM, system pocztowy w szablonie posiada wbudowane optymalizacje:

* **NagĹ‚Ăłwek List-Unsubscribe**: System automatycznie wstrzykuje nagĹ‚Ăłwek `List-Unsubscribe` do wiadomoĹ›ci newslettera (`NewsletterMail`). Pozwala to nowoczesnym klientom pocztowym (np. Gmail, Outlook, Yahoo) na wyĹ›wietlenie przycisku rezygnacji bezpoĹ›rednio w interfejsie pocztowym odbiorcy obok danych nadawcy.
* **Double Opt-In**: Zapis do newslettera wymaga klikniÄ™cia w link aktywacyjny wysyĹ‚any w mailu (`NewsletterDoubleOptInMail`). Chroni to TwojÄ… domenÄ™ przed dodawaniem faĹ‚szywych adresĂłw e-mail przez boty.
* **Konfiguracja SMTP/API (Produkcja)**: Na Ĺ›rodowisku produkcyjnym w pliku `.env` naleĹĽy zmieniÄ‡ `MAIL_MAILER=log` na `MAIL_MAILER=smtp` i uzupeĹ‚niÄ‡ parametry zaufanego dostawcy poczty (np. Mailgun, Brevo, Seohost). Aby maile miaĹ‚y peĹ‚nÄ… reputacjÄ™, domena wysyĹ‚kowa musi posiadaÄ‡ odpowiednie wpisy DNS: **SPF**, **DKIM** oraz **DMARC**.

---

### âŹ° ObsĹ‚uga Kolejek (Queues) na Produkcji

Procesy takie jak wysyĹ‚anie powiadomieĹ„ (np. rejestracja, weryfikacja e-mail, porzucone koszyki, zadania realizacji zamĂłwieĹ„) sÄ… realizowane asynchronicznie w tle. W tym celu baza danych jest domyĹ›lnie wykorzystywana jako kolejka (`QUEUE_CONNECTION=database`).

W zaleĹĽnoĹ›ci od infrastruktury serwerowej masz dwie opcje uruchomienia obsĹ‚ugi kolejek:

#### Opcja A: Serwer VPS / Dedykowany (Zalecane)

Uruchom staĹ‚y proces demona w tle (np. za pomocÄ… narzÄ™dzia **Supervisor**), aby natychmiast przetwarzaÄ‡ zadania:

```bash
php artisan queue:work --tries=3
```

#### Opcja B: Hosting WspĂłĹ‚dzielony (Shared Hosting)

Na hostingu wspĂłĹ‚dzielonym, gdzie nie moĹĽna uruchamiaÄ‡ staĹ‚ych procesĂłw w tle, kolejki sÄ… automatycznie obsĹ‚ugiwane przez harmonogram zadaĹ„ Laravela. W [routes/console.php](file:///d:/Projekty/_BOILERPLATE/laravel-filament-ecommerce-boilerplate/routes/console.php) skonfigurowano zadanie:

```php
Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();
```

W panelu zarzÄ…dzania hostingiem (np. LH, Cyberfolks, Zenbox) musisz jedynie dodaÄ‡ zadanie **Cron** uruchamiane **co minutÄ™** (`* * * * *`), wskazujÄ…c na plik `artisan` Twojego projektu:

```bash
/sciezka/do/php /sciezka/do/twojego/projektu/artisan schedule:run >> /dev/null 2>&1
```

Gdy pojawiÄ… siÄ™ zadania (np. po zĹ‚oĹĽeniu zamĂłwienia), Cron odpali proces, ktĂłry wykona wszystkie zadania z kolejki i automatycznie wyĹ‚Ä…czy siÄ™ po oprĂłĹĽnieniu bazy zadaĹ„, nie obciÄ…ĹĽajÄ…c serwera.

---

## đź“‚ Struktura Domenowa (`app/Domain/`)

Kod biznesowy zostaĹ‚ podzielony na modularne warstwy domenowe:

- **Commerce**: Logika koszyka, wyliczania cen, silnika promocji, pĹ‚atnoĹ›ci i zamĂłwieĹ„.
- **Analytics**: Agregacja danych analitycznych (odwiedziny stron, rozpoczÄ™cia checkoutu, zakupy).
- **Customers**: ObsĹ‚uga profili uĹĽytkownikĂłw i przypisywanie segmentĂłw lojalnoĹ›ciowych.
- **Communication**: Dynamiczne szablony e-mail z obsĹ‚ugÄ… placeholderĂłw oraz obsĹ‚uga wysyĹ‚ki kampanii newslettera w tle.
- **Storefront**: API dla zewnÄ™trznego frontu (Astro).

---

## đźŽ¨ Gotowe Snippety i Widgety Frontendowe (`resources/frontend-snippets/`)

W katalogu `resources/frontend-snippets/` przygotowano gotowe komponenty oraz skrypty uĹ‚atwiajÄ…ce integracjÄ™ z frontendem w technologii Astro/JavaScript:

* **`InPostGeowidget.astro`** â€” Integracja z mapÄ… PaczkomatĂłw InPost Geowidget v4. ObsĹ‚uguje wybĂłr Paczkomatu, prezentacjÄ™ szczegĂłĹ‚Ăłw oraz wysyĹ‚a zdarzenie DOM `inpost-point-selected` z danymi punktu przygotowanymi do wysyĹ‚ki w API checkoutu (`delivery_point`).
* **`OrlenPaczkaWidget.astro`** â€” Widget mapy dla ORLEN Paczka (protokĂłĹ‚ SMX) do wyboru punktĂłw odbioru i automatĂłw paczkowych. Po dokonaniu wyboru wysyĹ‚a zdarzenie `orlen-point-selected`.
* **`BlikWidget.astro`** â€” Referencyjny interfejs wprowadzania 6-cyfrowego kodu BLIK dla pĹ‚atnoĹ›ci bez przekierowania (Direct BLIK / 0-click).
* **`PayPoWidget.astro`** â€” Estetyczny widget na kartÄ™ produktu wyĹ›wietlajÄ…cy informacjÄ™ o pĹ‚atnoĹ›ci odroczonej PayPo (kup teraz, zapĹ‚aÄ‡ za 30 dni).
* **`GusAutocomplete.js`** â€” Skrypt kliencki obsĹ‚ugujÄ…cy wyszukiwanie danych firmy po NIP z automatycznym fallbackiem na tryb rÄ™czny w przypadku problemĂłw z zewnÄ™trznymi rejestrami paĹ„stwowymi.
