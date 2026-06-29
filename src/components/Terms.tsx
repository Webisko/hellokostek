import React, { useState, useEffect } from "react";
import { BookOpen, Sparkles, FileEdit, CreditCard, Truck, RotateCcw, AlertTriangle, Copyright, Scale, Info, Mail, ArrowRight } from "lucide-react";

export default function Terms() {
  const [activeSection, setActiveSection] = useState<string>("postanowienia-ogolne");

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
    <div className="animate-fadeIn pt-12 md:pt-20 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 max-w-[1600px] mx-auto space-y-16">
      {/* Editorial Header */}
      <header className="border-b border-gray-100 pb-12 max-w-4xl">
        <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block mb-4">
          Regulamin i zasady zakupów
        </span>
        <h1 className="font-display text-5xl sm:text-6xl text-gray-900 tracking-tight leading-none font-normal">
          Regulamin Sklepu Internetowego
        </h1>
        <p className="font-sans text-gray-500 text-sm mt-4">
          Stan na: Czerwiec 2026 r.
        </p>
      </header>

      {/* Intro Text & Quick Navigation layout */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
        {/* Main Content Column */}
        <div className="lg:col-span-8 space-y-12 font-sans text-gray-750 leading-relaxed max-w-3xl">
          <p className="text-base sm:text-lg text-gray-600">
            Poniższy Regulamin określa zasady korzystania ze sklepu internetowego <strong>hellokostek</strong>, dostępnego pod adresem <a href="https://hellokostek.pl/" className="text-gray-900 underline hover:text-[#E0115F] transition-colors">https://hellokostek.pl/</a>, prowadzonego przez Macieja Kosteczkę.
          </p>

          {/* Section 1 */}
          <section id="postanowienia-ogolne" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <BookOpen className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 1. Postanowienia ogólne</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. Sklep internetowy działający pod adresem <code>https://hellokostek.pl/</code> prowadzony jest przez: <strong>hellokostek Maciej Kosteczka</strong> z siedzibą w: <strong>Rynek 33, 42-470 Siewierz, woj. śląskie, Polska</strong>, NIP: <strong>6252363656</strong>, REGON: <strong>527158196</strong>, wpisany do Centralnej Ewidencji i Informacji o Działalności Gospodarczej (CEIDG), zwany dalej <strong>„Sprzedawcą”</strong>.
              </p>
              <p>
                2. Dane kontaktowe ze Sprzedawcą:
              </p>
              <ul className="list-disc pl-6 space-y-1 text-gray-650">
                <li>Adres e-mail: <a href="mailto:kontakt@hellokostek.pl" className="text-gray-950 font-bold hover:text-[#E0115F] transition-colors">kontakt@hellokostek.pl</a></li>
                <li>Telefon: <a href="tel:+48662707153" className="text-gray-950 font-bold hover:text-[#E0115F] transition-colors">662 707 153</a></li>
              </ul>
              <p>
                3. Niniejszy Regulamin określa zasady korzystania ze Sklepu, składania zamówień na gotowe prace malarskie, zasady zlecania i realizacji prac malarskich na indywidualne zamówienie, sposoby płatności, dostawy oraz procedury reklamacji i zwrotów.
              </p>
              <p>
                4. Klientem Sklepu może być osoba fizyczna posiadająca pełną zdolność do czynności prawnych (w tym Konsument oraz Przedsiębiorca na prawach konsumenta) lub osoba prawna.
              </p>
            </div>
          </section>

          {/* Section 2 */}
          <section id="definicje" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <Sparkles className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 2. Definicje przedmiotów sprzedaży</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                W ramach Sklepu rozróżnia się dwa rodzaje asortymentu:
              </p>
              <ul className="list-disc pl-6 space-y-2 text-gray-650">
                <li>
                  <strong>Produkt Gotowy</strong> – istniejący, fizyczny obraz (dzieło malarskie lub rysunkowe) dostępny w ofercie Sklepu, o określonych wymiarach, technice i cenie, gotowy do natychmiastowej wysyłki.
                </li>
                <li>
                  <strong>Produkt na Zamówienie (Dzieło Spersonalizowane)</strong> – praca malarska (np. portret ze zdjęcia) realizowana przez artystę od podstaw na indywidualne życzenie Klienta, według jego specyfikacji (określony temat, format, paleta kolorystyczna, nadesłane zdjęcia referencyjne).
                </li>
              </ul>
            </div>
          </section>

          {/* Section 3 */}
          <section id="skladanie-zamowien" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <FileEdit className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 3. Składanie i realizacja zamówień</h2>
            </div>
            <div className="space-y-6 text-sm sm:text-base">
              <div className="space-y-2">
                <h3 className="font-display font-bold text-gray-950 text-base">1. Zamówienia na Produkty Gotowe:</h3>
                <p>
                  Klient składa zamówienie poprzez dodanie wybranego obrazu do koszyka, wypełnienie formularza dostawy i kliknięcie przycisku finalizującego zakup. Umowa sprzedaży zostaje zawarta z chwilą otrzymania przez Klienta wiadomości e-mail potwierdzającej przyjęcie zamówienia do realizacji przez Sprzedawcę.
                </p>
              </div>
              <div className="space-y-2">
                <h3 className="font-display font-bold text-gray-950 text-base">2. Zamówienia na Produkty na Zamówienie (Indywidualne):</h3>
                <p>
                  Proces rozpoczyna się od kontaktu Klienta ze Sprzedawcą (za pomocą formularza lub e-maila) w celu przedstawienia wizji i wytycznych do obrazu. Sprzedawca dokonuje indywidualnej wyceny oraz określa szacowany termin realizacji dzieła.
                </p>
                <p>
                  Akceptacja wyceny przez Klienta stanowi podstawę do wystawienia zamówienia i opłacenia zaliczki (zazwyczaj w wysokości 30% lub według indywidualnych ustaleń). Umowa o dzieło zostaje zawarta po zaksięgowaniu ustalonej wpłaty na koncie Sprzedawcy.
                </p>
                <p>
                  Sprzedawca zastrzega sobie prawo do konsultowania etapów powstawania obrazu (np. szkic, podmalówka) drogą elektroniczną. Po ostatecznej akceptacji cyfrowej prezentacji gotowego obrazu przez Klienta i dopłacie pozostałej kwoty, praca jest zabezpieczana i wysyłana.
                </p>
              </div>
            </div>
          </section>

          {/* Section 4 */}
          <section id="ceny-i-platnosci" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <CreditCard className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 4. Ceny i metody płatności</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. Wszystkie ceny podane w Sklepie wyrażone są w złotych polskich (PLN) i zawierają wszystkie podatki. Ceny nie zawierają kosztów dostawy, które są wskazywane w trakcie składania zamówienia.
              </p>
              <p>
                2. <strong>Transparentność cen (Omnibus):</strong> W przypadku wprowadzenia promocji cenowej na gotowe dzieło, obok ceny promocyjnej Sprzedawca prezentuje najniższą cenę tego produktu, która obowiązywała w okresie 30 dni przed wprowadzeniem obniżki.
              </p>
              <p>
                3. Sklep udostępnia następujące metody płatności:
              </p>
              <ul className="list-disc pl-6 space-y-2 text-gray-650">
                <li>Szybkie płatności elektroniczne / BLIK / Karty płatnicze za pośrednictwem operatora: <strong>Stripe</strong>.</li>
                <li>Tradycyjny przelew bankowy na konto Sprzedawcy podawany indywidualnie w e-mailu potwierdzającym zamówienie.</li>
              </ul>
            </div>
          </section>

          {/* Section 5 */}
          <section id="dostawa" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <Truck className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 5. Dostawa i zabezpieczenie przesyłek</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. Dostawa realizowana jest na terytorium Rzeczypospolitej Polskiej oraz wybranych krajów za pośrednictwem wyspecjalizowanych firm kurierskich (np. <strong>InPost, DPD, DHL, UPS, Poczta Polska</strong>).
              </p>
              <p>
                2. Ze względu na unikatowy i delikatny charakter przedmiotów sprzedaży, Sprzedawca zobowiązuje się do profesjonalnego zabezpieczenia obrazów na czas transportu (stosowanie folii pęcherzykowej, narożników ochronnych, sztywnego kartonu lub skrzyń).
              </p>
              <p>
                3. Czas realizacji dostawy dla Produktów Gotowych wynosi zazwyczaj <strong>2-5 dni roboczych</strong>. Dla Produktów na Zamówienie czas ten jest ustalany indywidualnie w umowie (zależnie od techniki malarskiej, formatu i czasu schnięcia mediów).
              </p>
            </div>
          </section>

          {/* Section 6 */}
          <section id="zwroty-i-odstapienie" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <RotateCcw className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 6. Prawo do odstąpienia od umowy (Zwroty)</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <div className="bg-amber-50 border border-amber-200 p-6 rounded-2xl space-y-3">
                <div className="flex gap-2.5 items-center text-amber-800 font-bold text-sm">
                  <AlertTriangle className="w-5 h-5 shrink-0" />
                  <span>WAŻNE (Zróżnicowanie prawne ze względu na charakter dzieła):</span>
                </div>
                <div className="text-amber-900 text-xs sm:text-sm leading-relaxed space-y-2">
                  <p>
                    <strong>1. W przypadku Produktów Gotowych:</strong> Klient będący Konsumentem ma prawo odstąpić od umowy sprzedaży w terminie 14 dni bez podania jakiejkolwiek przyczyny. Termin ten biegnie od dnia objęcia obrazu w posiadanie przez Klienta. Koszt odesłania zwrotu ponosi Klient. Obraz must zostać zwrócony w stanie nienaruszonym, odpowiednio zabezpieczonym do transportu.
                  </p>
                  <p>
                    <strong>2. W przypadku Produktów na Zamówienie (Spersonalizowanych):</strong> Zgodnie z <strong>art. 38 ust. 1 pkt 3 ustawy o prawach konsumenta</strong>, prawo do odstąpienia od umowy zawartej na odległość <strong>nie przysługuje konsumentowi</strong> w odniesieniu do umów, w których przedmiotem świadczenia jest towar nieprefabrykowany, wyprodukowany według specyfikacji konsumenta lub służący zaspokojeniu jego zindywidualizowanych potrzeb.
                  </p>
                  <p className="font-semibold underline">
                    Oznacza to, że obrazy malowane na indywidualne zamówienie Klienta (według jego wytycznych, nadesłanego zdjęcia, formatu czy wybranej kolorystyki) nie podlegają zwrotowi.
                  </p>
                </div>
              </div>
            </div>
          </section>

          {/* Section 7 */}
          <section id="reklamacje" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <Info className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 7. Reklamacje (Zgodność towaru z umową)</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. Sprzedawca ma obowiązek dostarczyć produkt wolny od wad i zgodny z zawartą umową.
              </p>
              <p>
                2. W przypadku stwierdzenia uszkodzeń mechanicznych powstałych podczas transportu, Klient proszony jest o spisanie protokołu szkody w obecności kuriera oraz niezwłoczny kontakt ze Sprzedawcą.
              </p>
              <p>
                3. Reklamacje z tytułu braku zgodności towaru z umową należy składać na adres e-mail: <a href="mailto:kontakt@hellokostek.pl" className="font-bold text-gray-900 hover:text-[#E0115F] transition-colors">kontakt@hellokostek.pl</a>.
              </p>
              <p>
                4. Sprzedawca ustosunkuje się do reklamacji Klienta w terminie 14 dni od dnia jej otrzymania.
              </p>
            </div>
          </section>

          {/* Section 8 */}
          <section id="prawa-autorskie" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <Copyright className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 8. Prawa autorskie do dzieł malarskich</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. Wszystkie dzieła malarskie (zarówno gotowe, jak i tworzone na zamówienie) sprzedawane za pośrednictwem Sklepu stanowią utwór w rozumieniu ustawy z dnia 4 lutego 1994 r. o prawie autorskim i prawach pokrewnych.
              </p>
              <p>
                2. Zakup fizycznego egzemplarza obrazu przez Klienta <strong>nie powoduje przeniesienia majątkowych praw autorskich</strong> na jego rzecz.
              </p>
              <p>
                3. Klient nabywa wyłącznie własność fizycznego nośnika (obrazu) i ma prawo do jego eksponowania w celach prywatnych. Klientowi bez pisemnej, osobnej zgody autora nie przysługuje prawo do:
              </p>
              <ul className="list-disc pl-6 space-y-2 text-gray-650 text-sm">
                <li>Reprodukowania, kopiowania i powielania obrazu (np. tworzenia druków artystycznych, plakatów, gadżetów).</li>
                <li>Wykorzystywania wizerunku obrazu w celach komercyjnych.</li>
                <li>Wprowadzania jakichkolwiek modyfikacji w strukturę dzieła.</li>
              </ul>
            </div>
          </section>

          {/* Section 9 */}
          <section id="pozasadowe-rozwiazywanie-sporow" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <Scale className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 9. Pozasądowe sposoby rozpatrywania reklamacji</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                Konsument ma możliwość skorzystania z pozasądowych sposobów rozpatrywania reklamacji i dochodzenia roszczeń, m.in. poprzez:
              </p>
              <ol className="list-decimal pl-6 space-y-2 text-gray-650">
                <li>Zwrócenie się do stałego polubownego sądu konsumenckiego.</li>
                <li>Zwrócenie się do Miejskiego lub Powiatowego Rzecznika Konsumentów.</li>
                <li>Skorzystanie z unijnej platformy ODR (Online Dispute Resolution) dostępnej pod adresem: <a href="http://ec.europa.eu/consumers/odr/" target="_blank" rel="noopener noreferrer" className="text-gray-900 underline hover:text-[#E0115F] transition-colors">http://ec.europa.eu/consumers/odr/</a>.</li>
              </ol>
            </div>
          </section>

          {/* Section 10 */}
          <section id="postanowienia-koncowe" className="space-y-6 scroll-mt-24">
            <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
              <Info className="w-6 h-6 text-[#E0115F] shrink-0" />
              <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 10. Postanowienia końcowe</h2>
            </div>
            <div className="space-y-4 text-sm sm:text-base">
              <p>
                1. W sprawach nieuregulowanych niniejszym Regulaminem mają zastosowanie powszechnie obowiązujące przepisy prawa polskiego, w szczególności Kodeksu cywilnego oraz ustawy o prawach konsumenta.
              </p>
              <p>
                2. Sprzedawca zastrzega sobie prawo do zmian w Regulaminie z ważnych przyczyn (np. zmiany przepisów prawa). Do zamówień złożonych przed zmianą Regulaminu stosuje się wersję obowiązującą w dniu złożenia zamówienia.
              </p>
            </div>
          </section>
        </div>

        {/* Sidebar Navigation (Asymmetric) */}
        <aside className="lg:col-span-4 lg:sticky lg:top-28 bg-gray-50 border border-gray-100 p-6 rounded-3xl space-y-6">
          <div>
            <h3 className="font-mono text-xs uppercase tracking-widest text-gray-400 font-semibold mb-2">Spis treści</h3>
            <nav className="flex flex-col gap-2.5 font-sans text-sm font-light">
              <a 
                href="#postanowienia-ogolne" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "postanowienia-ogolne" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "postanowienia-ogolne" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 1. Postanowienia ogólne</span>
              </a>
              <a 
                href="#definicje" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "definicje" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "definicje" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 2. Definicje przedmiotów</span>
              </a>
              <a 
                href="#skladanie-zamowien" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "skladanie-zamowien" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "skladanie-zamowien" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 3. Składanie zamówień</span>
              </a>
              <a 
                href="#ceny-i-platnosci" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "ceny-i-platnosci" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "ceny-i-platnosci" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 4. Ceny i płatności</span>
              </a>
              <a 
                href="#dostawa" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "dostawa" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "dostawa" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 5. Dostawa i wysyłka</span>
              </a>
              <a 
                href="#zwroty-i-odstapienie" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "zwroty-i-odstapienie" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "zwroty-i-odstapienie" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 6. Prawo do zwrotu</span>
              </a>
              <a 
                href="#reklamacje" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "reklamacje" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "reklamacje" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 7. Reklamacje</span>
              </a>
              <a 
                href="#prawa-autorskie" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "prawa-autorskie" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "prawa-autorskie" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 8. Prawa autorskie</span>
              </a>
              <a 
                href="#pozasadowe-rozwiazywanie-sporow" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "pozasadowe-rozwiazywanie-sporow" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "pozasadowe-rozwiazywanie-sporow" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 9. Rozpatrywanie sporów</span>
              </a>
              <a 
                href="#postanowienia-koncowe" 
                className={`transition-all flex items-center gap-1.5 py-0.5 duration-300 ${
                  activeSection === "postanowienia-koncowe" 
                    ? "text-[#E0115F] font-semibold translate-x-1" 
                    : "text-stone-500 hover:text-[#E0115F]"
                }`}
              >
                <ArrowRight className={`w-3.5 h-3.5 transition-colors duration-300 ${
                  activeSection === "postanowienia-koncowe" ? "text-[#E0115F]" : "text-stone-400"
                }`} />
                <span>§ 10. Postanowienia końcowe</span>
              </a>
            </nav>
          </div>

          <div className="border-t border-gray-200/60 pt-6 space-y-4">
            <h4 className="font-display font-bold text-gray-900 text-sm">Pytania do regulaminu?</h4>
            <p className="text-xs text-gray-500 leading-relaxed">
              Mój regulamin ma na celu jasne zabezpieczenie zarówno Twoich praw, jak i integralności rękodzieła.
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
    </div>
  );
}
