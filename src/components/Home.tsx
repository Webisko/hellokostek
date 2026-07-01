import React, { useState, useRef, useEffect } from "react";
import { createPortal } from "react-dom";
import ProductSlider from "./ProductSlider";
import Testimonials from "./Testimonials";
import {
  ArrowRight,
  ArrowLeft,
  Send,
  X
} from "lucide-react";
import heroObraz from "../assets/portret_franka.webp";
import portretLeona from "../assets/portret_Leona.webp";
import portretMarysi from "../assets/portret_Marysi.webp";
import portretOliwii from "../assets/portret_Oliwii.webp";
import portretSlubnyPary from "../assets/portret_slubny_pary.webp";

const getSrc = (img: any): string => (img && typeof img === 'object' && 'src' in img ? img.src : img);
const heroObrazUrl = getSrc(heroObraz);
const portretLeonaUrl = getSrc(portretLeona);
const portretMarysiUrl = getSrc(portretMarysi);
const portretOliwiiUrl = getSrc(portretOliwii);
const portretSlubnyParyUrl = getSrc(portretSlubnyPary);

const portfolioItems = [
  {
    src: portretLeonaUrl,
    alt: "Portret kobiety",
    title: "Portret Kobiety",
    format: "Płótno prostokątne • 30×40 cm",
    technique: "Klasyczna technika olejna na płótnie",
    isOval: false
  },
  {
    src: portretOliwiiUrl,
    alt: "Portret psa Tequili",
    title: "Portret psa Tequili",
    format: "Płótno owalne • 30×40 cm",
    technique: "Klasyczna technika olejna na płótnie",
    isOval: true
  },
  {
    src: portretSlubnyParyUrl,
    alt: "Portret trzech dziewczynek",
    title: "Portret Trzech Dziewczynek",
    format: "Płótno prostokątne • 40×50 cm",
    technique: "Klasyczna technika olejna na płótnie",
    isOval: false
  },
  {
    src: portretMarysiUrl,
    alt: "Portret ś spiącego niemowlęcia",
    title: "Portret Śpiącego Niemowlęcia",
    format: "Płótno prostokątne • 30×40 cm",
    technique: "Klasyczna technika olejna na płótnie",
    isOval: false
  }
];

export default function Home() {
  const [emailForm, setEmailForm] = useState({
    name: "",
    email: "",
    subject: "portrait_commission",
    message: "",
    shape: "",
    size: ""
  });
  const [emailFiles, setEmailFiles] = useState<File[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isMounted, setIsMounted] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    setIsMounted(true);
  }, []);

  // Lightbox state (will be preserved for compatibility, but disabled from the main grid clicks)
  const [lightbox, setLightbox] = useState({
    isOpen: false,
    currentIndex: 0
  });

  const closeLightbox = () => {
    setLightbox({
      isOpen: false,
      currentIndex: 0
    });
  };

  const nextLightboxImage = () => {
    setLightbox(prev => ({
      ...prev,
      currentIndex: (prev.currentIndex + 1) % portfolioItems.length
    }));
  };

  const prevLightboxImage = () => {
    setLightbox(prev => ({
      ...prev,
      currentIndex: (prev.currentIndex - 1 + portfolioItems.length) % portfolioItems.length
    }));
  };

  // Block body scroll when lightbox is open
  useEffect(() => {
    if (lightbox.isOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [lightbox.isOpen]);

  useEffect(() => {
    if (!lightbox.isOpen) return;
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") closeLightbox();
      else if (e.key === "ArrowRight") nextLightboxImage();
      else if (e.key === "ArrowLeft") prevLightboxImage();
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [lightbox.isOpen]);

  const handleContactSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!emailForm.shape) {
      alert("Proszę wybrać podobrazie.");
      return;
    }
    setIsSubmitting(true);
    setTimeout(() => {
      setIsSubmitting(false);
      setEmailForm({ name: "", email: "", subject: "portrait_commission", message: "", shape: "", size: "" });
      setEmailFiles([]);
      if (typeof window !== "undefined") {
        window.location.href = "/hellokostek/sukces-kontakt";
      }
    }, 1000);
  };

  const basePath = "/hellokostek";

  return (
    <div className="bg-white min-h-screen text-gray-900 animate-fadeIn">
      {/* 1. HERO SECTION: 50/50 ASYMMETRIC SPLIT */}
      <section className="pt-12 md:pt-20 lg:pt-16 xl:pt-12 2xl:pt-20 pb-12 md:pb-20 lg:pb-16 xl:pb-14 2xl:pb-24">
        <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 grid grid-cols-1 lg:grid-cols-12 gap-16 lg:gap-12 xl:gap-20 items-center">
          {/* Left: Massive Serif text + CTA */}
          <div className="lg:col-span-7 2xl:col-span-6 space-y-8 font-sans text-center lg:text-left flex flex-col items-center lg:items-start">
            <div className="block">
              <span className="font-mono text-[10px] sm:text-xs tracking-wider sm:tracking-widest uppercase text-gray-400 font-bold block">
                PRACOWNIA ARTYSTYCZNA • KOSTEK MACIEJ KOSTECZKA
              </span>
            </div>
            <h1 className="font-display text-4xl sm:text-6xl md:text-7xl lg:text-[80px] xl:text-[110px] 2xl:text-[120px] leading-[0.95] tracking-tighter text-gray-950 font-normal">
              Człowiek dla człowieka – <span className="text-[#E0115F]">sztuka prawdziwa</span> bez AI
            </h1>
            <p className="font-sans text-gray-700 text-lg leading-relaxed max-w-xl font-normal mx-auto lg:mx-0">
              Malarstwo olejne
            </p>
            <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4 w-full">
              <a
                href="#kontakt-sekcja"
                className="button text-center"
              >
                <div className="button__blobs">
                  <div></div>
                  <div></div>
                  <div></div>
                </div>
                <div className="button__text font-bold">
                  Zamów swój portret
                  <ArrowRight className="w-4 h-4" />
                </div>
              </a>
            </div>
          </div>
          {/* Right: Premium oil portrait presentation */}
          <div className="lg:col-span-5 2xl:col-span-6 flex flex-col justify-center">
            <div className="relative w-full aspect-square overflow-hidden rounded-[32px] border border-gray-100 shadow-sm bg-gray-55">
              <img
                src={heroObrazUrl}
                alt="Portret olejny namalowany ze zdjęcia"
                referrerPolicy="no-referrer"
                className="w-full h-full aspect-square object-cover block"
              />
            </div>
          </div>
        </div>
      </section>

      {/* 2. PORTFOLIO GRID: ART-GALLERY STYLE ASYMMETRIC GRID WITH TRANSITIONS */}
      <section className="bg-white border-y border-gray-100 py-20 md:py-28 lg:py-24 xl:py-20 2xl:py-32">
        <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 space-y-16">
          <header className="space-y-3 max-w-2xl mx-auto text-center">
            <span className="font-mono text-xs text-[#E0115F] uppercase tracking-widest block font-bold">PORTFOLIO</span>
            <h2 className="font-display text-4xl sm:text-5xl text-gray-950 font-normal">
              Portrety z mojej pracowni
            </h2>
            <p className="font-sans text-gray-600 text-base leading-relaxed max-w-[480px] mx-auto">
              Dotychczasowe zamówienia.
            </p>
          </header>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-16">
            {portfolioItems.map((item, idx) => (
              <div key={idx} className="space-y-6 group">
                <div 
                  className={`aspect-[3/4] overflow-hidden bg-gray-100 border border-gray-100 relative ${item.isOval ? "rounded-[50%]" : "rounded-[24px]"}`}
                  style={{ borderRadius: item.isOval ? "50%" : undefined }}
                >
                  <img
                    src={item.src}
                    alt={item.alt}
                    referrerPolicy="no-referrer"
                    className="w-full h-full object-cover transition-all duration-700 ease-out group-hover:scale-105"
                  />
                </div>
                <div className="text-center pt-2">
                  <span className="font-mono text-sm sm:text-base text-gray-500 block">{item.format}</span>
                </div>
              </div>
            ))}
          </div>

          {/* Action buttons under the grid */}
          <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-4 pt-12 md:pt-16 w-full max-w-md mx-auto sm:max-w-none">
            <a
              href="#kontakt-sekcja"
              className="button w-full sm:w-auto text-center cursor-pointer"
            >
              <div className="button__blobs">
                <div></div>
                <div></div>
                <div></div>
              </div>
              <div className="button__text font-bold">
                Zamów swój portret
                <ArrowRight className="w-4 h-4 ml-1.5" />
              </div>
            </a>
            <a
              href={`${basePath}/galeria`}
              className="button button--secondary w-full sm:w-auto text-center cursor-pointer"
            >
              <div className="button__blobs">
                <div></div>
                <div></div>
                <div></div>
              </div>
              <div className="button__text font-bold">
                Zobacz galerię portretów
                <ArrowRight className="w-4 h-4 ml-1.5" />
              </div>
            </a>
          </div>
        </div>
      </section>

      {/* 2.5 TESTIMONIALS SECTION */}
      <Testimonials />

      {/* 3. PROCESS TIMELINE: SYMMETRICAL, HORIZONTAL 4-STEP AXIS */}
      <section id="jak-zamowic-sekcja" className="py-20 md:py-28 lg:py-24 xl:py-20 2xl:py-32 scroll-mt-20">
        <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0">
          <header className="space-y-3 max-w-2xl mb-20 mx-auto text-center">
            <span className="font-mono text-xs text-[#E0115F] uppercase tracking-widest block font-bold">ETAPY WSPÓŁPRACY</span>
            <h2 className="font-display text-5xl text-gray-950 font-normal">Przejrzyste zasady, pewny efekt</h2>
            <p className="font-sans text-gray-700 text-base leading-relaxed">
              Eliminuję stres związany z zamawianiem tradycyjnych dzieł sztuki przez internet. Każdy krok jest przejrzysty i poddany Twojej akceptacji.
            </p>
          </header>

          {/* Horizontal Process Axis timeline aligned left */}
          <div className="relative pt-2 md:pt-8 border-t-0 md:border-t border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-10 md:gap-6 lg:gap-12 xl:gap-16">
              {/* Step 1 */}
              <div className="space-y-3 relative flex flex-col items-start text-left pl-8 md:pl-0 md:items-center md:text-center group">
                <div className="absolute left-[7.5px] top-4 bottom-[-52px] w-[1px] bg-gray-200 md:hidden" />
                <div className="absolute left-0 top-0 md:-top-8 md:-translate-y-1/2 md:left-1/2 md:-translate-x-1/2 w-4 h-4 rounded-full border-2 border-gray-400 bg-white z-10 group-hover:scale-155 group-hover:bg-[#C4F013] group-hover:border-[#C4F013] transition-all duration-300" />
                <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-bold block">Krok 01</span>
                <h3 className="font-display text-xl sm:text-2xl text-gray-900 font-normal">Chcę coś takiego!</h3>
                <p className="font-sans text-sm sm:text-base text-gray-600 leading-relaxed max-w-[300px]">
                  Przesyłasz jedno lub więcej zdjęć e-mailem lub za pomocą formularza na dole strony.
                </p>
              </div>
              {/* Step 2 */}
              <div className="space-y-3 relative flex flex-col items-start text-left pl-8 md:pl-0 md:items-center md:text-center group">
                <div className="absolute left-[7.5px] top-4 bottom-[-52px] w-[1px] bg-gray-200 md:hidden" />
                <div className="absolute left-0 top-0 md:-top-8 md:-translate-y-1/2 md:left-1/2 md:-translate-x-1/2 w-4 h-4 rounded-full border-2 border-gray-400 bg-white z-10 group-hover:scale-155 group-hover:bg-[#C4F013] group-hover:border-[#C4F013] transition-all duration-300" />
                <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-bold block">Krok 02</span>
                <h3 className="font-display text-xl sm:text-2xl text-gray-900 font-normal">Sprawdź to!</h3>
                <p className="font-sans text-sm sm:text-base text-gray-600 leading-relaxed max-w-[300px]">
                  Komponuję obraz w programie graficznym, wysyłam do Ciebie i decydujesz o poprawkach do projektu jeszcze przed malowaniem.
                </p>
              </div>
              {/* Step 3 */}
              <div className="space-y-3 relative flex flex-col items-start text-left pl-8 md:pl-0 md:items-center md:text-center group">
                <div className="absolute left-[7.5px] top-4 bottom-[-52px] w-[1px] bg-gray-200 md:hidden" />
                <div className="absolute left-0 top-0 md:-top-8 md:-translate-y-1/2 md:left-1/2 md:-translate-x-1/2 w-4 h-4 rounded-full border-2 border-gray-400 bg-white z-10 group-hover:scale-155 group-hover:bg-[#C4F013] group-hover:border-[#C4F013] transition-all duration-300" />
                <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-bold block">Krok 03</span>
                <h3 className="font-display text-xl sm:text-2xl text-gray-900 font-normal">Do roboty!</h3>
                <p className="font-sans text-sm sm:text-base text-gray-600 leading-relaxed max-w-[300px]">
                  Dopiero po pełnej akceptacji cyfrowego projektu wpłacasz 50% zadatku. W tym momencie rozpoczynam pracę na płótnie.
                </p>
              </div>
              {/* Step 4 */}
              <div className="space-y-3 relative flex flex-col items-start text-left pl-8 md:pl-0 md:items-center md:text-center group">
                <div className="absolute left-[7.5px] top-4 bottom-0 w-[1px] bg-gray-200 md:hidden" />
                <div className="absolute left-0 top-0 md:-top-8 md:-translate-y-1/2 md:left-1/2 md:-translate-x-1/2 w-4 h-4 rounded-full border-2 border-gray-400 bg-white z-10 group-hover:scale-155 group-hover:bg-[#C4F013] group-hover:border-[#C4F013] transition-all duration-300" />
                <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-bold block">Krok 04</span>
                <h3 className="font-display text-xl sm:text-2xl text-gray-900 font-normal">Gimme, Gimme!</h3>
                <p className="font-sans text-sm sm:text-base text-gray-600 leading-relaxed max-w-[300px]">
                  Po 3-4 tygodniach przesyłam zdjęcia gotowego obrazu. Coś do zmiany? Masz trzy bezpłatne poprawki! Po akceptacji dopłacasz drugą połowę, a ja wysyłam ubezpieczoną paczkę kurierem.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 4. PRICING: TWO BORDERLESS, CLEAN COLUMNS */}
      <section className="bg-stone-50 border-y border-gray-100 py-20 md:py-28 lg:py-24 xl:py-20 2xl:py-32">
        <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 space-y-24">
          <header className="max-w-4xl space-y-3 mx-auto text-center">
            <span className="font-mono text-xs text-[#E0115F] uppercase tracking-widest block font-bold">PROSTE WARUNKI</span>
            <h2 className="font-display text-5xl text-gray-950 font-normal">Cennik portretów ze zdjęcia</h2>
            <p className="font-sans text-gray-600 text-base leading-relaxed max-w-[620px] mx-auto">
              Dwa standardowe formaty podobrazi malarskich. Podane kwoty to ceny wyjściowe dla portretowania jednej osoby.
            </p>
          </header>
          <div className="space-y-8">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-10 md:gap-6 lg:gap-16 xl:gap-24 max-w-4xl mx-auto items-stretch">
              {/* Format 1: Rectangle */}
              <div className="flex flex-col justify-between space-y-8 py-4">
                <div className="space-y-6 text-center">
                  <div className="w-48 aspect-[3/4] mx-auto bg-white border border-gray-150 rounded-none shadow-[0_8px_24px_rgba(0,0,0,0.02)] flex flex-col items-center justify-center transition-all duration-500 hover:scale-[1.03] hover:border-[#E0115F]/40 hover:shadow-[0_12px_32px_rgba(224,17,95,0.05)] mb-8 select-none relative group">
                    <div className="absolute inset-2 border border-dashed border-gray-100 rounded-none group-hover:border-[#E0115F]/20 transition-colors" />
                    <span className="text-sm font-mono text-gray-500 z-10 font-bold uppercase tracking-widest leading-none">3:4</span>
                    <span className="text-xs font-mono text-gray-400 z-10 mt-2 tracking-wide uppercase leading-none">Prostokąt</span>
                  </div>
                  <div className="space-y-1.5">
                    <h3 className="font-mono text-lg sm:text-xl font-normal text-[#E0115F]">Płótno prostokątne</h3>
                    <div className="font-mono text-sm sm:text-base text-gray-500 font-normal">
                      30x40 cm*
                    </div>
                  </div>
                </div>
                <div className="flex justify-center gap-3 md:gap-2 lg:gap-4 items-baseline pt-4 border-t border-gray-100">
                  <span className="text-sm font-mono text-gray-400">CENA STARTOWA:</span>
                  <span className="font-mono text-3xl font-bold text-gray-900">od 800 zł**</span>
                </div>
              </div>
              {/* Format 2: Oval */}
              <div className="flex flex-col justify-between space-y-8 py-4">
                <div className="space-y-6 text-center">
                  <div
                    className="w-48 aspect-[3/4] mx-auto bg-white border border-gray-150 shadow-[0_8px_24px_rgba(0,0,0,0.02)] flex flex-col items-center justify-center transition-all duration-500 hover:scale-[1.03] hover:border-[#E0115F]/40 hover:shadow-[0_12px_32px_rgba(224,17,95,0.05)] mb-8 select-none relative group rounded-[50%] overflow-hidden"
                    style={{ borderRadius: "50%" }}
                  >
                    <div
                      className="absolute inset-2 border border-dashed border-gray-100 group-hover:border-[#E0115F]/20 transition-colors rounded-[50%]"
                      style={{ borderRadius: "50%" }}
                    />
                    <span className="text-sm font-mono text-gray-500 z-10 font-bold uppercase tracking-widest leading-none">3:4</span>
                    <span className="text-xs font-mono text-gray-400 z-10 mt-2 tracking-wide uppercase leading-none">Owal</span>
                  </div>
                  <div className="space-y-1.5">
                    <h3 className="font-mono text-lg sm:text-xl font-normal text-[#E0115F]">Płótno owalne</h3>
                    <div className="font-mono text-sm sm:text-base text-gray-500 font-normal">
                      30x40 cm*
                    </div>
                  </div>
                </div>
                <div className="flex justify-center gap-3 md:gap-2 lg:gap-4 items-baseline pt-4 border-t border-gray-100">
                  <span className="text-sm font-mono text-gray-400">CENA STARTOWA:</span>
                  <span className="font-mono text-3xl font-bold text-gray-900">od 800 zł**</span>
                </div>
              </div>
            </div>
            {/* Centered italicized informational subtext note with generous limits */}
            <div className="max-w-2xl mx-auto text-center space-y-2">
              <p className="font-sans text-sm italic text-gray-650 leading-relaxed">
                * Na specjalne życzenie maluję również na płótnach o innych wymiarach.
              </p>
              <p className="font-sans text-sm italic text-gray-650 leading-relaxed">
                ** Cena zależna od wielkości płótna i ilości portretowanych postaci.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* 6. CONTACT SECTION: FORM */}
      <section id="kontakt-sekcja" className="border-y border-gray-100 bg-stone-50 py-20 md:py-28 lg:py-24 xl:py-20 2xl:py-32 scroll-mt-20">
        <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 grid grid-cols-1 xl:grid-cols-12 gap-16 items-start">
          <div className="xl:col-span-5 space-y-8 font-sans text-center xl:text-left">
            <span className="font-mono text-xs text-[#E0115F] uppercase tracking-widest block font-bold">Porozmawiajmy o portrecie dla Ciebie</span>
            <h2 className="font-display text-5xl text-gray-950 leading-[1.1] font-normal">Stwórzmy coś wyjątkowego razem</h2>
            <p className="text-gray-600 text-base leading-relaxed max-w-xl mx-auto xl:mx-0">
              Prześlij swoje zdjęcia i opisz pomysł. Bez żadnych zobowiązań się temu przyjrzę i podpowiem jak najlepiej zaaranżować kompozycję oraz tło, aby portret Ci się spodobał!
            </p>
          </div>
          <div className="xl:col-span-7 bg-white rounded-3xl border border-off-black p-6 sm:p-10 space-y-6">
            <h3 className="font-display text-2xl text-gray-900 border-b border-gray-100 pb-4">Zamów wycenę Twojego portretu</h3>
            <form onSubmit={handleContactSubmit} className="space-y-6 font-sans">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div className="space-y-1">
                  <label htmlFor="home-contact-name" className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Imię *</label>
                  <input
                    type="text"
                    id="home-contact-name"
                    name="name"
                    required
                    placeholder="np. Anna Kowalska"
                    value={emailForm.name}
                    onChange={(e) => setEmailForm({...emailForm, name: e.target.value})}
                    className="w-full p-3 bg-gray-55 border border-gray-200 focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-sm transition-all"
                  />
                </div>
                <div className="space-y-1">
                  <label htmlFor="home-contact-email" className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">adres e-mail *</label>
                  <input
                    type="email"
                    id="home-contact-email"
                    name="email"
                    required
                    placeholder="np. anna.kowalska@gmail.com"
                    value={emailForm.email}
                    onChange={(e) => setEmailForm({...emailForm, email: e.target.value})}
                    className="w-full p-3 bg-gray-55 border border-gray-200 focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-sm transition-all"
                  />
                </div>
              </div>
              
              <div className="space-y-1">
                <label className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Podobrazie *</label>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                    emailForm.shape === "rectangle" && emailForm.size === "30x40" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-500 hover:bg-white"
                  }`}>
                    <input
                      type="radio"
                      name="podobrazie"
                      value="prostokat_30x40"
                      checked={emailForm.shape === "rectangle" && emailForm.size === "30x40"}
                      onChange={() => setEmailForm({...emailForm, shape: "rectangle", size: "30x40"})}
                      className="sr-only"
                    />
                    Prostokątne 30x40
                  </label>
                  <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                    emailForm.shape === "oval" && emailForm.size === "30x40" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-500 hover:bg-white"
                  }`}>
                    <input
                      type="radio"
                      name="podobrazie"
                      value="owalne_30x40"
                      checked={emailForm.shape === "oval" && emailForm.size === "30x40"}
                      onChange={() => setEmailForm({...emailForm, shape: "oval", size: "30x40"})}
                      className="sr-only"
                    />
                    Owalne 30x40
                  </label>
                  <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                    emailForm.shape === "other" && emailForm.size === "other" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-500 hover:bg-white"
                  }`}>
                    <input
                      type="radio"
                      name="podobrazie"
                      value="inne"
                      checked={emailForm.shape === "other" && emailForm.size === "other"}
                      onChange={() => setEmailForm({...emailForm, shape: "other", size: "other"})}
                      className="sr-only"
                    />
                    Inne
                  </label>
                </div>
              </div>

              <div className="space-y-1">
                <label htmlFor="home-contact-message" className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Opisz swoją wizję lub przeznaczenie obrazu *</label>
                <textarea
                  id="home-contact-message"
                  name="message"
                  required
                  rows={6}
                  placeholder="Dla kogo powstaje obraz? Czy to pamiątka rodzinna czy prezent na rocznicę? A może chcesz swojego kota wielu miejscach jednocześnie (na drapaku i w ramce)? Daj znać!"
                  value={emailForm.message}
                  onChange={(e) => setEmailForm({...emailForm, message: e.target.value})}
                  className="w-full p-3 bg-gray-55 border border-gray-200 focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-sm transition-all resize-none leading-relaxed"
                />
              </div>
              <div className="space-y-2">
                <span className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Dołącz poglądowe zdjęcia (JPG, PNG)</span>
                <div
                  onClick={() => fileInputRef.current?.click()}
                  className="border border-dashed border-gray-200 rounded-xl p-6 text-center bg-gray-50/50 hover:bg-gray-50 transition-all cursor-pointer relative"
                >
                  <input
                    type="file"
                    ref={fileInputRef}
                    multiple
                    onChange={(e) => {
                      if (e.target.files) {
                        setEmailFiles(Array.from(e.target.files));
                      }
                    }}
                    className="hidden"
                  />
                  <div className="space-y-2 pointer-events-none">
                    <div className="text-xs font-medium text-gray-500">
                      {emailFiles.length > 0 ? (
                        <span className="text-[#E0115F] font-bold">
                          ✓ Wybrano {emailFiles.length} {emailFiles.length === 1 ? 'plik' : emailFiles.length < 5 ? 'pliki' : 'plików'}: {emailFiles.map(f => f.name).join(", ")}
                        </span>
                      ) : (
                        <span>Kliknij, by załączyć klatki zdjęciowe (możesz też wysłać je bezpośrednio w mailu)</span>
                      )}
                    </div>
                    <p className="text-xs text-gray-400">Twoje dane są w pełni bezpieczne.</p>
                  </div>
                </div>
              </div>
              <div className="pt-4">
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="button button--full cursor-pointer"
                >
                  <div className="button__blobs">
                    <div></div>
                    <div></div>
                    <div></div>
                  </div>
                  <div className="button__text">
                    {isSubmitting ? (
                      <span>Generowanie wiadomości...</span>
                    ) : (
                      <>
                        <span>Wyślij zapytanie</span>
                        <Send className="w-4 h-4" />
                      </>
                    )}
                  </div>
                </button>
                <p className="text-xs text-gray-550 font-sans text-center mt-4 leading-relaxed font-normal">
                  Wiadomość zostanie przesłana bezpośrednio do mojej asystentki – rudej kotki o imieniu Aurea, która miauknięciem informuje o nowych wiadomościach z formularza. Odpowiedź ode mnie wraz z propozycją kompozycji otrzymasz z odbiciem łapki na podany adres e-mail.
                </p>
              </div>
            </form>
          </div>
        </div>
      </section>

      <ProductSlider />

      {isMounted && lightbox.isOpen && createPortal(
        <div
          className="fixed inset-0 w-screen h-screen z-50 flex flex-col md:flex-row bg-neutral-950 text-white transition-opacity duration-300 overflow-hidden"
          onClick={closeLightbox}
        >
          {/* Global Close Button (Float top right, highly accessible) */}
          <button
            onClick={closeLightbox}
            className="absolute top-4 right-4 md:top-6 md:right-6 text-neutral-400 hover:text-white bg-black/40 backdrop-blur-md hover:bg-white/10 p-2.5 rounded-full transition-all duration-300 cursor-pointer z-56 border-none"
            aria-label="Zamknij podgląd"
          >
            <X className="w-5 h-5" />
          </button>

          {/* Main Image Area (Left/Center) */}
          <div
            className="flex-grow flex items-center justify-center p-6 md:p-12 relative h-[60vh] md:h-screen bg-black"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Image */}
            <img
              src={portfolioItems[lightbox.currentIndex].src}
              alt={portfolioItems[lightbox.currentIndex].alt}
              referrerPolicy="no-referrer"
              className={`max-w-full max-h-full object-contain shadow-2xl select-none transition-transform duration-300 ${
                portfolioItems[lightbox.currentIndex].isOval ? "rounded-full aspect-[3/4]" : "rounded-lg"
              }`}
            />
            {/* Navigation buttons overlaid on the image area */}
            <button
              onClick={(e) => { e.stopPropagation(); prevLightboxImage(); }}
              className="absolute left-4 md:left-8 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white hover:bg-gray-55 text-gray-900 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-[0_0_15px_rgba(196,240,19,0.45)] focus:outline-none z-55 cursor-pointer border-none"
              aria-label="Poprzednie zdjęcie"
            >
              <ArrowLeft className="w-5 h-5" />
            </button>
            <button
              onClick={(e) => { e.stopPropagation(); nextLightboxImage(); }}
              className="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white hover:bg-gray-55 text-gray-900 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-[0_0_15px_rgba(196,240,19,0.45)] focus:outline-none z-55 cursor-pointer border-none"
              aria-label="Następne zdjęcie"
            >
              <ArrowRight className="w-5 h-5" />
            </button>
          </div>
          {/* Sidebar Area (Right) */}
          <div
            className="w-full md:w-[380px] lg:w-[420px] shrink-0 bg-neutral-900 border-t md:border-t-0 md:border-l border-white/10 p-6 md:p-10 flex flex-col justify-between h-[40vh] md:h-screen relative z-55 overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Details Section */}
            <div className="space-y-6 pt-6 md:pt-10 font-sans">
              <div className="flex justify-between items-baseline text-xs text-neutral-400 font-mono">
                <span>Warsztat tradycyjny</span>
                <span>Portret {lightbox.currentIndex + 1} z {portfolioItems.length}</span>
              </div>
              <h2 className="font-display text-2xl md:text-3xl font-normal tracking-tight text-white leading-tight">
                {portfolioItems[lightbox.currentIndex].title}
              </h2>
              <div className="space-y-2.5 text-xs text-neutral-400 leading-relaxed">
                <p>• {portfolioItems[lightbox.currentIndex].technique}</p>
                <p>• Format: {portfolioItems[lightbox.currentIndex].format}</p>
                <p>• Wykończenie: Naciąg na krosna sosnowe</p>
                <p>• Realizacja: Ręcznie malowany na zamówienie ze zdjęcia</p>
              </div>
            </div>
            {/* Actions Section */}
            <div className="pt-6 border-t border-white/5">
              <button
                onClick={() => {
                  closeLightbox();
                  const formSection = document.getElementById("kontakt-sekcja");
                  if (formSection) formSection.scrollIntoView({ behavior: "smooth" });
                }}
                className="button button--full cursor-pointer"
              >
                <div className="button__blobs">
                  <div></div>
                  <div></div>
                  <div></div>
                </div>
                <div className="button__text font-bold">
                  Zamów podobny portret
                  <ArrowRight className="w-4 h-4" />
                </div>
              </button>
            </div>
          </div>
        </div>,
        document.body
      )}
    </div>
  );
}
