import React, { useState, useEffect } from "react";
import { createPortal } from "react-dom";
import { BookOpen, Sparkles, FileEdit, CreditCard, Truck, RotateCcw, AlertTriangle, Copyright, Scale, Info, Mail, ArrowRight, X } from "lucide-react";

const TERMS_SECTIONS = [
  { id: "postanowienia-ogolne", label: "§ 1. Postanowienia ogólne" },
  { id: "definicje", label: "§ 2. Definicje przedmiotów" },
  { id: "skladanie-zamowien", label: "§ 3. Składanie zamówień" },
  { id: "ceny-i-platnosci", label: "§ 4. Ceny i płatności" },
  { id: "dostawa", label: "§ 5. Dostawa i wysyłka" },
  { id: "zwroty-i-odstapienie", label: "§ 6. Prawo do zwrotu" },
  { id: "reklamacje", label: "§ 7. Reklamacje" },
  { id: "prawa-autorskie", label: "§ 8. Prawa autorskie" },
  { id: "pozasadowe-rozwiazywanie-sporow", label: "§ 9. Rozpatrywanie sporów" },
  { id: "postanowienia-koncowe", label: "§ 10. Postanowienia końcowe" }
];

export default function Terms() {
  const [activeSection, setActiveSection] = useState<string>("postanowienia-ogolne");
  const [isMounted, setIsMounted] = useState(false);
  const [isTocDrawerOpen, setIsTocDrawerOpen] = useState(false);
  const [lastUpdated, setLastUpdated] = useState<string>("Czerwiec 2026 r.");
  const [dynamicSections, setDynamicSections] = useState<{ id: string; label: string; content: string }[] | null>(null);

  const activeTocSections = dynamicSections && dynamicSections.length > 0
    ? dynamicSections.map((s) => ({ id: s.id, label: s.label }))
    : TERMS_SECTIONS;

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

    const apiBase = import.meta.env.PUBLIC_API_URL || "http://localhost:8000/api";
    fetch(`${apiBase}/content/pages/regulamin`)
      .then((res) => {
        if (!res.ok) throw new Error("API network error");
        return res.json();
      })
      .then((payload) => {
        const page = payload.data?.page;
        if (page) {
          if (page.last_updated_formatted) {
            setLastUpdated(`${page.last_updated_formatted} r.`);
          }
          if (Array.isArray(page.sections) && page.sections.length > 0) {
            setDynamicSections(page.sections);
          }
        }
      })
      .catch((err) => {
        console.warn("Could not fetch regulamin from API, using default static content:", err);
      });
    
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
          Regulamin i zasady zakupów
        </span>
        <h1 className="font-display text-5xl sm:text-6xl text-gray-900 tracking-tight leading-none font-normal">
          Regulamin Sklepu Internetowego
        </h1>
        <p className="font-sans text-gray-500 text-sm mt-4">
          Stan na: {lastUpdated}
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
            Poniższy Regulamin określa zasady korzystania ze sklepu internetowego <strong>hellokostek</strong>, dostępnego pod adresem <a href="https://hellokostek.pl/" className="text-gray-900 underline hover:text-[#E0115F] transition-colors">https://hellokostek.pl/</a>, prowadzonego przez Macieja Kosteczkę.
          </p>

          {dynamicSections && dynamicSections.length > 0 ? (
            dynamicSections.map((sec) => (
              <section key={sec.id} id={sec.id} className="space-y-6 scroll-mt-24">
                <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
                  <BookOpen className="w-6 h-6 text-[#E0115F] shrink-0" />
                  <h2 className="font-display text-2xl text-gray-900 font-semibold">{sec.label}</h2>
                </div>
                <div 
                  className="space-y-4 text-sm sm:text-base text-gray-700 leading-relaxed [&>p]:mb-3 [&>ul]:list-disc [&>ul]:pl-6 [&>ul]:space-y-1 [&>ol]:list-decimal [&>ol]:pl-6 [&>ol]:space-y-1"
                  dangerouslySetInnerHTML={{ __html: sec.content }}
                />
              </section>
            ))
          ) : (
            <>
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
                </div>
              </section>

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
                    2. Reklamacje z tytułu braku zgodności towaru z umową należy składać na adres e-mail: <a href="mailto:kontakt@hellokostek.pl" className="font-bold text-gray-900 hover:text-[#E0115F] transition-colors">kontakt@hellokostek.pl</a>.
                  </p>
                </div>
              </section>

              <section id="prawa-autorskie" className="space-y-6 scroll-mt-24">
                <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
                  <Copyright className="w-6 h-6 text-[#E0115F] shrink-0" />
                  <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 8. Prawa autorskie do dzieł malarskich</h2>
                </div>
                <div className="space-y-4 text-sm sm:text-base">
                  <p>
                    1. Wszystkie dzieła malarskie (zarówno gotowe, jak i tworzone na zamówienie) sprzedawane za pośrednictwem Sklepu stanowią utwór w rozumieniu ustawy z dnia 4 lutego 1994 r. o prawie autorskim i prawach pokrewnych.
                  </p>
                </div>
              </section>

              <section id="pozasadowe-rozwiazywanie-sporow" className="space-y-6 scroll-mt-24">
                <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
                  <Scale className="w-6 h-6 text-[#E0115F] shrink-0" />
                  <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 9. Pozasądowe sposoby rozpatrywania reklamacji</h2>
                </div>
                <div className="space-y-4 text-sm sm:text-base">
                  <p>
                    Konsument ma możliwość skorzystania z pozasądowych sposobów rozpatrywania reklamacji i dochodzenia roszczeń.
                  </p>
                </div>
              </section>

              <section id="postanowienia-koncowe" className="space-y-6 scroll-mt-24">
                <div className="flex items-center gap-3 border-b border-gray-100 pb-3">
                  <Info className="w-6 h-6 text-[#E0115F] shrink-0" />
                  <h2 className="font-display text-2xl text-gray-900 font-semibold">§ 10. Postanowienia końcowe</h2>
                </div>
                <div className="space-y-4 text-sm sm:text-base">
                  <p>
                    1. W sprawach nieuregulowanych niniejszym Regulaminem mają zastosowanie powszechnie obowiązujące przepisy prawa polskiego.
                  </p>
                </div>
              </section>
            </>
          )}
        </div>

        {/* Sidebar Navigation (Asymmetric) */}
        <aside className="hidden lg:block lg:col-span-4 lg:sticky lg:top-28 bg-gray-50 border border-gray-100 p-6 rounded-3xl space-y-6">
          <div>
            <h3 className="font-mono text-xs uppercase tracking-widest text-gray-400 font-semibold mb-2">Spis treści</h3>
            <nav className="flex flex-col gap-2.5 font-sans text-sm font-light">
              {activeTocSections.map((sec) => (
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
                  {activeTocSections.map((sec) => (
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
