## 1. Wymogi prawne i techniczne dla banera cookies (Consent Banner)

Baner cookies przestał być jedynie „okienkiem informacyjnym”. Obecnie jest to zaawansowane narzędzie techniczne, które musi spełniać pięć cech ważnej zgody według RODO: zgoda musi być dobrowolna, konkretna, świadoma, jednoznaczna oraz możliwa do udowodnienia.

### A. Zasada uprzedniego blokowania (Prior Blocking) – kluczowy wymóg techniczny

To najczęstszy błąd techniczny w e-commerce. Żadne skrypty śledzące, marketingowe czy analityczne (takie jak Google Analytics, Meta Pixel, Hotjar, TikTok Pixel) **nie mogą załadować się w przeglądarce użytkownika przed wyrażeniem przez niego aktywnej zgody**.

* Po wejściu na stronę i przed kliknięciem jakiegokolwiek przycisku na banerze, w tle mogą uruchomić się **wyłącznie cookies niezbędne** (strictly necessary), odpowiadające np. za utrzymanie koszyka zakupowego, sesję logowania czy zapamiętanie wybranej wersji językowej.
* Zgodność z tą zasadą jest pierwszą rzeczą weryfikowaną podczas kontroli technicznej (poprzez zakładkę *Network* w narzędziach deweloperskich przeglądarki).

### B. Zasada pełnej symetrii (Równorzędność wyboru)

Baner nie może stosować tzw. *dark patterns* (manipulacyjnych praktyk projektowych) polegających na nakłanianiu użytkownika do akceptacji.

* **Przycisk „Odrzuć wszystko” (Reject All) musi być tak samo widoczny jak przycisk „Akceptuj wszystko” (Accept All) i znajdować się na tej samej, pierwszej warstwie banera**. Niedozwolone jest ukrywanie opcji odrzucenia pod linkiem w tekście, mniejszym drukiem lub dopiero na drugim ekranie (w tzw. ustawieniach zaawansowanych).
* Przycisk „Odrzuć wszystko” nie może mieć celowo gorszego kontrastu (np. jasnoszary przycisk na jasnoszarym tle) w stosunku do jaskrawego przycisku „Akceptuj wszystko”. Oba muszą mieć identyczną wagę wizualną, ten sam rozmiar i czcionkę.
* **Zamknięcie banera krzyżykiem „X”** (jeśli występuje) nie może oznaczać domyślnej zgody. Zgodnie z wytycznymi europejskich organów nadzorczych, zamknięcie banera bez kliknięcia „Akceptuj” musi technicznie skutkować domyślnym odrzuceniem wszystkich plików cookies oprócz niezbędnych.

### C. Zgoda granularna (szczegółowa)

Sklep internetowy nie może wymuszać zgody na wszystko („wszystko albo nic”). Użytkownik musi mieć możliwość wejścia w ustawienia szczegółowe i samodzielnego zaznaczenia, na które kategorie cookies wyraża zgodę:

* **Kategorie:** niezbędne (necessary), analityczne/statystyczne (analytics), funkcjonalne (functional) oraz marketingowe/reklamowe (marketing).
* Wszystkie checkboxy lub suwaki (toggles) dla cookies nieniezbędnych (analityka, marketing, preferencje) **muszą być domyślnie odznaczone/wyłączone (OFF)**. Użytkownik musi je aktywnie włączyć.

### D. Łatwe wycofanie zgody w każdym momencie

Udzielenie zgody nie jest ostateczne. Użytkownik ma prawo zmienić zdanie w dowolnym momencie, a **wycofanie zgody musi być tak samo proste jak jej udzielenie** (wymagać tej samej liczby kliknięć).

* Na stronie sklepu musi znajdować się stały, łatwo dostępny element (np. pływająca, mała ikonka tarczy/ciasteczka w rogu ekranu lub wyraźny link „Zarządzaj cookies” w stopce strony), który po kliknięciu natychmiast wywołuje baner z preferencjami i umożliwia zmianę ustawień.

### E. Obowiązek rejestrowania zgód (Consent Log)

W razie kontroli Prezesa UODO (Urzędu Ochrony Danych Osobowych), to na właścicielu sklepu spoczywa ciężar dowodu, że zgoda została pozyskana legalnie. System sklepowy (lub wdrożona platforma CMP – Consent Management Platform) musi rejestrować w bazie danych tzw. logi zgody:

* Unikalny identyfikator użytkownika/sesji (np. zahashowany).
* Dokładny znacznik czasu (data i godzina) wyrażenia lub zmiany zgody.
* Informację o tym, które kategorie cookies zostały zaakceptowane, a które odrzucone.
* Wersję banera oraz treść komunikatów, jaką użytkownik widział w momencie podejmowania decyzji.

### F. Google Consent Mode v2 (Wymóg technologiczny Google)

Dla sklepów korzystających z narzędzi Google (Google Ads, Google Analytics, systemy remarketingu) w 2026 roku kluczowe jest pełne wdrożenie mechanizmu **Google Consent Mode v2**. Baner cookies musi być zintegrowany z warstwą danych (dataLayer) i przekazywać do skryptów Google cztery kluczowe parametry zgody użytkownika:

1. `analytics_storage` – zgoda na cookies analityczne.
2. `ad_storage` – zgoda na cookies reklamowe.
3. `ad_user_data` – zgoda na przesyłanie danych użytkownika do Google w celach reklamowych (wymóg Consent Mode v2).
4. `ad_personalization` – zgoda na personalizację reklam i remarketing (wymóg Consent Mode v2).

## 2. Podział kompetencji nadzorczych w Polsce (UODO vs UKE)

Wdrożenie cookies w Polsce podlega dwóm różnym reżimom prawnym i dwóm organom nadzorczym:

1. **Prezes Urzędu Ochrony Danych Osobowych (UODO):** Nadzoruje zgodność z **RODO**. Interweniuje w sytuacji, gdy pliki cookies zbierają i przetwarzają dane osobowe (np. unikalne identyfikatory klientów, adresy IP wykorzystywane do profilowania marketingowego).
2. **Prezes Urzędu Komunikacji Elektronicznej (UKE):** Nadzoruje zgodność z ustawą  **Prawo Komunikacji Elektronicznej (PKE)** , która weszła w życie i zastąpiła dotychczasowe Prawo Telekomunikacyjne. UKE kontroluje sam fakt instalowania oprogramowania i zapisywania jakichkolwiek informacji (w tym cookies) na urządzeniu końcowym użytkownika (telefonie, komputerze) bez jego uprzedniej, jednoznacznej zgody.

Dlatego audyt zgodności cookies musi uwzględniać zarówno ochronę prywatności (RODO), jak i techniczną integralność urządzenia użytkownika (PKE).

## 3. Wymogi co do dokumentu „Polityka Cookies”

Dokument ten musi być precyzyjny i aktualny. Umieszczenie w polityce prywatności ogólnego zapisu typu *„używamy plików cookies w celach statystycznych i reklamowych”* jest obecnie rażącym naruszeniem prawa.

Zgodnie z wymogami przejrzystości RODO, polityka cookies musi zawierać:

* **Jasne definicje:** Wyjaśnienie prostym językiem, czym są pliki cookies, jak dzielą się na własne (first-party) i podmiotów trzecich (third-party) oraz do czego służą poszczególne kategorie.
* **Szczegółowy rejestr (tabelę) stosowanych ciasteczek:** Lista musi być generowana na podstawie regularnych skanów technicznych strony i zawierać dla każdego pliku cookie:
  * **Nazwę pliku** (np. `_ga`, `_fbp`).
  * **Dostawcę** (np. Google, Meta, właściciel sklepu).
  * **Dokładny cel i funkcję** (np. „rejestrowanie unikalnego identyfikatora do celów statystycznych Google Analytics”).
  * **Okres przechowywania / czas życia** (np. sesyjne, 1 dzień, 2 lata).
  * **Typ pobieranych danych**.
* **Instrukcję zarządzania z poziomu przeglądarki:** Jasny opis, jak użytkownik może całkowicie zablokować lub usunąć pliki cookies bezpośrednio w ustawieniach najpopularniejszych przeglądarek (Chrome, Firefox, Safari, Edge).

## 4. Specyfika zagraniczna (UK i Norwegia)

* **Wielka Brytania (UK):** Brytyjski organ nadzorczy ICO (Information Commissioner's Office) rygorystycznie podchodzi do zakazu stosowania *dark patterns* oraz braku opcji „Odrzuć wszystko” na pierwszej warstwie banera. Wymogi w UK po Brexicie (UK GDPR) są niemal tożsame z unijnymi w zakresie interfejsu banerów cookies.
* **Norwegia:** Podlega pod rygory EOG (Europejskiego Obszaru Gospodarczego). Norweski organ nadzorczy (Datatilsynet) ściśle współpracuje z Europejską Radą Ochrony Danych (EROD/EDPB) i egzekwuje identyczne zasady: prior blocking, symetria przycisków oraz pełna przejrzystość w polityce cookies.
