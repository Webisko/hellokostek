import React, { useState, useEffect } from "react";
import { createPortal } from "react-dom";
import { ShieldCheck, ClipboardList, Users, UserCheck, Cookie, Scale, FileText, Mail, ArrowRight, X, BookOpen } from "lucide-react";

const PRIVACY_SECTIONS = [
  { id: "postanowienia-ogolne", label: "§ 1. Postanowienia ogólne" },
  { id: "cele-i-podstawy-przetwarzania", label: "§ 2. Cele i podstawy prawne" },
  { id: "odbiorcy-danych", label: "§ 3. Odbiorcy danych" },
  { id: "prawa-uzytkownikow", label: "§ 4. Twoje prawa (RODO)" },
  { id: "pliki-cookies", label: "§ 5. Pliki cookies" },
  { id: "profilowanie-i-omnibus", label: "§ 6. Dyrektywa Omnibus" },
  { id: "postanowienia-koncowe", label: "§ 7. Postanowienia końcowe" }
];

export default function PrivacyPolicy() {
  const [activeSection, setActiveSection] = useState<string>("postanowienia-ogolne");
  const [isMounted, setIsMounted] = useState(false);
  const [isTocDrawerOpen, setIsTocDrawerOpen] = useState(false);

  const handleScrollToSection = (e: React.MouseEvent<HTMLAnchorElement>, id: string) => {
    e.preventDefault();
    const element = document.getElementById(id);
    if (element) {
      element.scrollIntoView({ behavior: "smooth", block: "start" });
      window.history.pushState(null, "", `#${id}`);
      setActiveSection(id);
    }
  };

  useEffect(() => {
    setIsMounted(true);
    
    // Przewijanie do kotwicy z adresu URL po załadowaniu
    const hash = window.location.hash.replace("#", "");
    if (hash) {
      const timer = setTimeout(() => {
        const element = document.getElementById(hash);
        if (element) {
          element.scrollIntoView({ behavior: "smooth", block: "start" });
          setActiveSection(hash);
        }
      }, 150);
      return () => clearTimeout(timer);
    }
  }, []);

  useEffect(() => {
    const sections = document.querySelectorAll("section[id]");
    const observerOptions = {
      root: null,
      rootMargin: "-10% 0px -55% 0px",
      threshold: 0,
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          setActiveSection(entry.target.id);
        }
      });
    }, observerOptions);

    sections.forEach((section) => observer.observe(section));

    return () => {
      sections.forEach((section) => observer.unobserve(section));
    };
  }, []);
  
  return (
    <div className="animate-fadeIn pt-6 md:pt-8 xl:pt-12 2xl:pt-20 pb-16 content-container space-y-8 xl:space-y-16">
      {/* Editorial Header */}
      <header className="border-b border-gray-100 pb-12 max-w-4xl">
        <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block mb-4">
          Regulacje i ochrona danych
        </span>
        <h1 className="font-display text-5xl sm:text-6xl text-gray-900 tracking-tight leading-none font-normal">
          Polityka Prywatności i Plików Cookies
        </h1>
        <p className="font-sans text-gray-500 text-sm mt-4">
          Stan na: Czerwiec 2026 r.
        </p>
      </header>

      {/* Mobile Table of Contents Trigger (Sticky, floating) */}
      <div className="sticky top-[63px] md:top-[72px] lg:hidden z-30 flex justify-between items-center w-full py-3 pointer-events-none">
        <button
          onClick={() => setIsTocDrawerOpen(true)}
          className="pointer-events-auto flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-200 bg-white text-gray-800 text-xs font-semibold uppercase tracking-wider hover:bg-gray-55 hover:border-gray-300 transition-all cursor-pointer shadow-md active:scale-[0.98]"
        >
          <BookOpen className="w-4 h-4 text-[#E0115F]" />
          Spis treści
        </button>
      </div>

      {/* Intro Text & Quick Navigation layout */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
        {/* Main Content Column */}
        <div className="lg:col-span-8 space-y-12 font-sans text-gray-750 leading-relaxed max-w-3xl">
          <p className="text-base sm:text-lg text-gray-600">
            Niniejsza Polityka Prywatności określa zasady przetwarzania i ochrony danych osobowych użytkowników korzystających ze strony internetowej i sklepu <strong>hellokostek</strong>, dostępnego pod adresem: <a href="https://hellokostek.pl/" className="text-gray-900 underline hover:text-[#E0115F] transition-colors">https://hellokostek.pl/</a>.
          </p>

          {/* Section 1 */}
          <section id="postanowienia-ogolne" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <ShieldCheck className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 1. Postanowienia ogólne</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. Administratorem danych osobowych zbieranych za pośrednictwem Sklepu hellokostek jest <strong>hellokostek Maciej Kosteczka</strong> z siedzibą w: <strong>Rynek 33, 42-470 Siewierz, woj. śląskie, Polska</strong>, NIP: <strong>6252363656</strong>, REGON: <strong>527158196</strong>, wpisany do Centralnej Ewidencji i Informacji o Działalności Gospodarczej (CEIDG), zwany dalej <strong>„Administratorem”</strong>.
              </p>
              <p>
                2. Kontakt z Administratorem w sprawach związanych z ochroną i przetwarzaniem danych osobowych jest możliwy za pośrednictwem adresu e-mail: <a href="mailto:kontakt@hellokostek.pl" className="font-bold text-gray-900 hover:text-[#E0115F] transition-colors">kontakt@hellokostek.pl</a> lub korespondencyjnie na adres wskazany w ust. 1.
              </p>
              <p>
                3. Dane osobowe użytkowników są przetwarzane zgodnie z Rozporządzeniem Parlamentu Europejskiego i Rady (UE) 2016/679 z dnia 27 kwietnia 2016 r. (RODO), ustawą o ochronie danych osobowych oraz ustawą – Prawo komunikacji elektronicznej (PKE).
              </p>
            </div>
          </section>

          {/* Section 2 */}
          <section id="cele-i-podstawy-przetwarzania" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <ClipboardList className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 2. Cele, podstawy prawne oraz okres przetwarzania danych</h2>
            </div>
            <p className="text-sm sm:text-base">
              Dane osobowe użytkowników są przetwarzane w celach związanych z funkcjonowaniem galerii, sprzedażą dzieł sztuki oraz realizacją projektów artystycznych na zamówienie:
            </p>
            
            <div className="space-y-6 mt-4">
              {/* Purpose 1 */}
              <div className="space-y-2">
                <h3 className="font-display font-bold text-gray-950 text-base">1. Sprzedaż gotowych prac malarskich</h3>
                <ul className="list-disc pl-6 space-y-1 text-gray-650">
                  <li><strong>Cel:</strong> Realizacja zamówienia złożonego w sklepie internetowym, w tym pakowanie, zabezpieczenie przesyłki, wysyłka kurierska lub do punktu odbioru oraz informowanie o statusie dostawy.</li>
                  <li><strong>Podstawa prawna:</strong> Art. 6 ust. 1 lit. b RODO (przetwarzanie niezbędne do wykonania umowy sprzedaży).</li>
                  <li><strong>Okres przechowywania:</strong> Przez okres niezbędny do realizacji transakcji, a następnie do czasu upływu terminów przedawnienia roszczeń z tytułu umowy (zgodnie z Kodeksem cywilnym).</li>
                </ul>
              </div>

              {/* Purpose 2 */}
              <div className="space-y-2">
                <h3 className="font-display font-bold text-gray-950 text-base">2. Realizacja prac malarskich na zamówienie (indywidualne projekty artystyczne)</h3>
                <ul className="list-disc pl-6 space-y-1 text-gray-650">
                  <li><strong>Cel:</strong> Przyjęcie specyfikacji zamówienia, przetwarzanie wytycznych, kontakt w celu uzgodnienia kompozycji, wymiarów, kolorystyki, przesyłanie etapów prac do akceptacji oraz ostateczna realizacja i dostawa spersonalizowanego dzieła sztuki.</li>
                  <li><strong>Podstawa prawna:</strong> Art. 6 ust. 1 lit. b RODO (podjęcie działań na żądanie osoby, której dane dotyczą, przed zawarciem umowy oraz wykonanie umowy o dzieło).</li>
                  <li><strong>Okres przechowywania:</strong> Przez czas trwania ustaleń, okres tworzenia dzieła, a po jego wydaniu – do czasu przedawnienia roszczeń lub wygaśnięcia uprawnień z tytułu rękojmi.</li>
                </ul>
              </div>

              {/* Purpose 3 */}
              <div className="space-y-2">
                <h3 className="font-display font-bold text-gray-950 text-base">3. Obsługa formularza kontaktowego i zapytań o wycenę obrazów</h3>
                <ul className="list-disc pl-6 space-y-1 text-gray-650">
                  <li><strong>Cel:</strong> Odpowiedzi na zapytania ofertowe, wycenę prac indywidualnych, konsultacje artystyczne drogą mailową lub telefoniczną.</li>
                  <li><strong>Podstawa prawna:</strong> Art. 6 ust. 1 lit. f RODO (prawnie uzasadniony interes Administratora polegający na sprawnej komunikacji z potencjalnymi klientami i oferowaniu swoich usług).</li>
                  <li><strong>Okres przechowywania:</strong> Przez czas niezbędny do obsługi zapytania i zakończenia korespondencji, nie dłużej jednak niż przez 1 rok od ostatniego kontaktu, chyba że korespondencja prowadzi do zawarcia umowy.</li>
                </ul>
              </div>

              {/* Purpose 4 */}
              <div className="space-y-2">
                <h3 className="font-display font-bold text-gray-950 text-base">4. Obowiązki finansowo-księgowe</h3>
                <ul className="list-disc pl-6 space-y-1 text-gray-650">
                  <li><strong>Cel:</strong> Wystawianie faktur, rachunków, prowadzenie ksiąg rachunkowych oraz dokumentacji podatkowej związanej ze sprzedażą dzieł sztuki.</li>
                  <li><strong>Podstawa prawna:</strong> Art. 6 ust. 1 lit. c RODO w związku z przepisami prawa podatkowego i ustawy o rachunkowości.</li>
                  <li><strong>Okres przechowywania:</strong> Przez okres 5 lat, licząc od końca roku kalendarzowego, w którym upłynął termin płatności podatku za dany rok.</li>
                </ul>
              </div>

              {/* Purpose 5 */}
              <div className="space-y-2">
                <h3 className="font-display font-bold text-gray-950 text-base">5. Dochodzenie roszczeń i ochrona praw autorskich</h3>
                <ul className="list-disc pl-6 space-y-1 text-gray-650">
                  <li><strong>Cel:</strong> Ustalenie, dochodzenie lub obrona przed roszczeniami prawnymi (np. spory transportowe, opóźnienia w płatnościach) oraz ochrona osobistych i majątkowych praw autorskich do stworzonych dzieł malarskich.</li>
                  <li><strong>Podstawa prawna:</strong> Art. 6 ust. 1 lit. f RODO (prawnie uzasadniony interes polegający na ochronie praw majątkowych i wizerunku marki).</li>
                  <li><strong>Okres przechowywania:</strong> Do czasu prawomocnego zakończenia postępowań lub upływu terminów przedawnienia roszczeń.</li>
                </ul>
              </div>
            </div>
          </section>

          {/* Section 3 */}
          <section id="odbiorcy-danych" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <Users className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 3. Odbiorcy danych osobowych</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                W celu zapewnienia najwyższej jakości obsługi oraz bezpiecznej dostawy unikatowych i wartościowych przesyłek, dane osobowe mogą być przekazywane następującym kategoriom podmiotów:
              </p>
              <ol className="list-decimal pl-6 space-y-2 text-gray-650">
                <li><strong>Dostawcy usług logistycznych i kurierskich:</strong> podmioty wyspecjalizowane w transporcie krajowym i międzynarodowym (np. InPost, DPD, DHL, UPS, Poczta Polska) w celu dostarczenia zakupionych obrazów.</li>
                <li><strong>Operatorzy płatności online:</strong> podmioty obsługujące bezpieczne płatności elektroniczne, karty płatnicze lub system BLIK (np. Stripe, PayU, Przelewy24, Tpay).</li>
                <li><strong>Dostawcy usług IT:</strong> firmy zapewniające stabilne działanie hostingu, serwerów pocztowych oraz narzędzi technicznych, na których osadzona jest strona hellokostek.</li>
                <li><strong>Zewnętrzna obsługa księgowa:</strong> biuro rachunkowe przetwarzające dokumentację finansową sklepu.</li>
              </ol>
              <p>
                Administrator nie przekazuje danych osobowych do państw trzecich (spoza Europejskiego Obszaru Gospodarczego), chyba że jest to niezbędne do realizacji wysyłki międzynarodowej na wyraźne życzenie klienta.
              </p>
            </div>
          </section>

          {/* Section 4 */}
          <section id="prawa-uzytkownikow" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <UserCheck className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 4. Prawa osoby, której dane dotyczą</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                Każdemu użytkownikowi, którego dane są przetwarzane, przysługują następujące prawa wynikające z RODO:
              </p>
              <ul className="list-disc pl-6 space-y-2 text-gray-650">
                <li><strong>Prawo dostępu</strong> do swoich danych oraz otrzymania ich kopii.</li>
                <li><strong>Prawo do sprostowania</strong> (poprawiania) danych, jeśli są nieprawidłowe lub niekompletne.</li>
                <li><strong>Prawo do usunięcia danych</strong> („prawo do bycia zapomnianym”) – jeśli brak jest podstaw do ich dalszego przetwarzania.</li>
                <li><strong>Prawo do ograniczenia przetwarzania</strong> danych w sytuacjach określonych w art. 18 RODO.</li>
                <li><strong>Prawo do przenoszenia danych</strong> do innego administratora w ustrukturyzowanym formacie.</li>
                <li><strong>Prawo do wniesienia sprzeciwu</strong> wobec przetwarzania danych na podstawie prawnie uzasadnionego interesu Administratora.</li>
                <li><strong>Prawo do cofnięcia zgody</strong> w dowolnym momencie (w zakresie, w jakim przetwarzanie opiera się na zgodzie), bez wpływu na zgodność z prawem przetwarzania przed jej cofnięciem.</li>
              </ul>
              <p>
                W celu realizacji swoich praw należy skontaktować się z Administratorem pod adresem e-mail: <a href="mailto:kontakt@hellokostek.pl" className="font-bold text-gray-900 hover:text-[#E0115F] transition-colors">kontakt@hellokostek.pl</a>.
              </p>
              <p>
                Użytkownik ma również prawo wnieść skargę do organu nadzorczego: <strong>Prezes Urzędu Ochrony Danych Osobowych (PUODO)</strong>, ul. Stawki 2, 00-193 Warszawa.
              </p>
            </div>
          </section>

          {/* Section 5 */}
          <section id="pliki-cookies" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <Cookie className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 5. Pliki cookies i technologie śledzące</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. Strona hellokostek wykorzystuje pliki cookies (małe pliki tekstowe zapisywane na urządzeniu końcowym) oraz pokrewne technologie w celu zapewnienia prawidłowego wyświetlania zawartości wizualnej galerii, optymalizacji procesu zakupowego oraz prowadzenia statystyk ruchu.
              </p>
              <p>
                2. Zgodnie z <strong>Prawem komunikacji elektronicznej (PKE)</strong>, instalowanie opcjonalnych plików cookies (analitycznych, marketingowych) wymaga uprzedniej, wyraźnej i dobrowolnej zgody użytkownika, wyrażonej za pośrednictwem baneru cookies wyświetlanego przy wejściu na stronę.
              </p>
              <p>
                3. Sklep stosuje:
              </p>
              <ul className="list-disc pl-6 space-y-2 text-gray-650">
                <li><strong>Cookies niezbędne (techniczne):</strong> kluczowe dla działania funkcji koszyka, nawigacji oraz zapamiętania preferencji prywatności użytkownika. Nie wymagają one zgody marketingowej.</li>
                <li><strong>Cookies opcjonalne (analityczne/marketingowe):</strong> stosowane za zgodą użytkownika (np. Google Analytics) w celu badania popularności poszczególnych kolekcji obrazów lub optymalizacji kampanii promocyjnych.</li>
              </ul>
              <p>
                4. Użytkownik może w każdej chwili zmodyfikować ustawienia cookies z poziomu swojej przeglądarki internetowej lub klikając w odnośnik zarządzania prywatnością w stopce strony.
              </p>
            </div>
          </section>

          {/* Section 6 */}
          <section id="profilowanie-i-omnibus" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <Scale className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 6. Profilowanie i przejrzystość cen (Dyrektywa Omnibus)</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. W ramach funkcjonowania Sklepu dane osobowe mogą być przetwarzane w sposób zautomatyzowany w celu analizowania ruchu na stronie, jednak nie wywołuje to wobec użytkowników żadnych skutków prawnych ani istotnych decyzji.
              </p>
              <p>
                2. Sklep informuje, że <strong>nie stosuje algorytmów automatycznego indywidualnego dostosowywania cen</strong> (profilowania cen) dla poszczególnych konsumentów na podstawie ich zachowań w sieci. Wszystkie ceny gotowych prac oraz stawki bazowe za zamówienia indywidualne są transparentne i jednakowe dla każdego klienta.
              </p>
              <p>
                3. W przypadku organizowania promocji cenowych na gotowe obrazy, Sklep każdorazowo informuje o najniższej cenie danego dzieła, która obowiązywała w okresie 30 dni przed wprowadzeniem obniżki, zgodnie z wymogami Dyrektywy Omnibus.
              </p>
            </div>
          </section>

          {/* Section 7 */}
          <section id="postanowienia-koncowe" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <FileText className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 7. Postanowienia końcowe</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. Administrator stosuje zaawansowane środki techniczne i organizacyjne chroniące dane osobowe przed przypadkowym lub niezgodnym z prawem zniszczeniem, utratą, modyfikacją lub nieuprawnionym ujawnieniem.
              </p>
              <p>
                2. Niniejsza Polityka Prywatności jest na bieżąco weryfikowana i w razie potrzeby aktualizowana, aby zachować pełną zgodność z obowiązującymi przepisami prawa.
              </p>
              <p>
                3. Wszelkie zmiany w Polityce Prywatności będą publikowane bezpośrednio na tej stronie.
              </p>
            </div>
          </section>
        </div>

        {/* Sidebar Navigation (Asymmetric) */}
        <aside className="hidden lg:block lg:col-span-4 lg:sticky lg:top-28 bg-gray-55 border border-gray-100 p-6 rounded-3xl space-y-6">
          <div>
            <h3 className="font-mono text-xs uppercase tracking-widest text-gray-400 font-semibold mb-2">Spis treści</h3>
            <nav className="flex flex-col gap-2.5 font-sans text-sm font-light">
              {PRIVACY_SECTIONS.map((sec) => (
                <a 
                  key={sec.id}
                  href={`#${sec.id}`}
                  onClick={(e) => handleScrollToSection(e, sec.id)}
                  className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                    activeSection === sec.id 
                      ? "text-[#E0115F] font-semibold translate-x-1" 
                      : "text-stone-500 hover:text-[#E0115F]"
                  }`}
                >
                  <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                    activeSection === sec.id ? "text-[#E0115F]" : "text-stone-400"
                  }`} />
                  <span>{sec.label}</span>
                </a>
              ))}
            </nav>
          </div>

          <div className="border-t border-gray-200/60 pt-6 space-y-4">
            <h4 className="font-display font-bold text-gray-900 text-sm">Masz pytania dotyczące prywatności?</h4>
            <p className="text-xs text-gray-500 leading-relaxed">
              Jeśli chcesz zrealizować swoje prawa RODO lub masz jakiekolwiek wątpliwości, skontaktuj się ze mną bezpośrednio.
            </p>
            <a 
              href="/hellokostek/kontakt?subject=other_question"
              className="button button--full button--sm cursor-pointer"
            >
              <div className="button__blobs">
                <div></div>
                <div></div>
                <div></div>
              </div>
              <div className="button__text">
                <Mail className="w-4 h-4" />
                <span>Napisz do mnie</span>
              </div>
            </a>
          </div>
        </aside>
      </div>

      {/* Table of Contents Drawer (Off-Canvas Menu) */}
      {isMounted && createPortal(
        <div className={`fixed inset-0 z-50 overflow-hidden transition-all duration-300 ${isTocDrawerOpen ? "pointer-events-auto" : "pointer-events-none"}`}>
          {/* Backdrop */}
          <div 
            className={`absolute inset-0 bg-black transition-opacity duration-300 ${
              isTocDrawerOpen ? "opacity-50" : "opacity-0"
            }`} 
            onClick={() => setIsTocDrawerOpen(false)}
          />
          
          {/* Sliding Panel */}
          <div className="absolute inset-y-0 left-0 max-w-full flex">
            <div className={`w-screen max-w-xs sm:max-w-sm bg-white text-gray-905 shadow-2xl flex flex-col justify-between h-full transform transition-transform duration-300 ease-in-out ${
              isTocDrawerOpen ? "translate-x-0" : "-translate-x-full"
            }`}>
              {/* Header */}
              <div className="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <h2 className="text-lg font-display font-semibold text-gray-950">Spis treści</h2>
                <button 
                  onClick={() => setIsTocDrawerOpen(false)}
                  className="p-2 -mr-2 text-gray-400 hover:text-gray-900 rounded-full hover:bg-gray-100 transition-colors cursor-pointer border-none bg-transparent"
                  aria-label="Zamknij spis treści"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Content (Scrollable Navigation) */}
              <div className="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                <nav className="flex flex-col gap-3 font-sans text-sm font-light">
                  {PRIVACY_SECTIONS.map((sec) => (
                    <a 
                      key={sec.id}
                      href={`#${sec.id}`}
                      onClick={(e) => { setIsTocDrawerOpen(false); handleScrollToSection(e, sec.id); }}
                      className={`transition-all flex items-center gap-1.5 py-1 duration-300 ${
                        activeSection === sec.id 
                          ? "text-[#E0115F] font-semibold translate-x-1" 
                          : "text-stone-500 hover:text-[#E0115F]"
                      }`}
                    >
                      <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                        activeSection === sec.id ? "text-[#E0115F]" : "text-stone-400"
                      }`} />
                      <span>{sec.label}</span>
                    </a>
                  ))}
                </nav>
              </div>

              {/* Footer */}
              <div className="p-6 border-t border-gray-100 bg-gray-55">
                <a 
                  href="/hellokostek/kontakt?subject=other_question"
                  onClick={() => setIsTocDrawerOpen(false)}
                  className="button button--full button--sm cursor-pointer"
                >
                  <div className="button__blobs">
                    <div></div>
                    <div></div>
                    <div></div>
                  </div>
                  <div className="button__text">
                    <Mail className="w-4 h-4" />
                    <span>Napisz do mnie</span>
                  </div>
                </a>
              </div>
            </div>
          </div>
        </div>,
        document.body
      )}
    </div>
  );
}
