import React, { useState, useRef, useEffect } from "react";
import { createPortal } from "react-dom";
import ProductSlider from "./ProductSlider";
import Testimonials from "./Testimonials";
import FaqSection from "./FaqSection";
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
    title: "Portret kobiety",
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
    title: "Portret trzech dziewczynek",
    format: "Płótno prostokątne • 40×50 cm",
    technique: "Klasyczna technika olejna na płótnie",
    isOval: false
  },
  {
    src: portretMarysiUrl,
    alt: "Portret śpiącego niemowlęcia",
    title: "Portret śpiącego niemowlęcia",
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
  const [websiteUrl, setWebsiteUrl] = useState("");
  const [hpField, setHpField] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState("");
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [formErrors, setFormErrors] = useState<string[]>([]);
  const [isMounted, setIsMounted] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const formRef = useRef<HTMLFormElement>(null);
  const formStartedAtRef = useRef<number>(0);

  useEffect(() => {
    setIsMounted(true);
    formStartedAtRef.current = Date.now();
  }, []);

  const clearFieldError = (field: string) => {
    if (!fieldErrors[field]) return;
    setFieldErrors((prev) => {
      const next = { ...prev };
      delete next[field];
      return next;
    });
  };

  const focusFirstError = (errors: Record<string, string>) => {
    const firstKey = Object.keys(errors)[0];
    if (!firstKey) return;
    requestAnimationFrame(() => {
      const target = formRef.current?.querySelector<HTMLElement>(`[data-error-field="${firstKey}"]`);
      if (target) {
        target.scrollIntoView({ behavior: "smooth", block: "center" });
        const focusable = target.matches("input, select, textarea, button")
          ? target
          : target.querySelector<HTMLElement>("input, select, textarea, button");
        focusable?.focus({ preventScroll: true });
      }
    });
  };

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

  const handleContactSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const errors: Record<string, string> = {};
    if (emailForm.name.trim().length < 2) {
      errors.name = "Podaj swoje imię (minimum 2 znaki).";
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailForm.email.trim())) {
      errors.email = "Podaj prawidłowy adres e-mail.";
    }
    if (!emailForm.shape) {
      errors.shape = "Wybierz kształt podobrazia.";
    }
    if (emailForm.message.trim().length < 5) {
      errors.message = "Wpisz opis swojej wizji lub przeznaczenia obrazu (minimum 5 znaków).";
    }
    if (emailFiles.length > 5) {
      errors.files = "Możesz załączyć maksymalnie 5 plików.";
    }

    if (Object.keys(errors).length > 0) {
      setFieldErrors(errors);
      setFormErrors(Object.values(errors));
      focusFirstError(errors);
      return;
    }

    setFieldErrors({});
    setFormErrors([]);
    setIsSubmitting(true);
    setErrorMessage("");

    const apiBase = import.meta.env.PUBLIC_API_URL || "https://panel.hellokostek.pl/api";
    const body = new FormData();
    body.append("name", emailForm.name.trim());
    body.append("email", emailForm.email.trim());
    body.append("phone", "");
    body.append("website_url", websiteUrl);
    body.append("hp_field", hpField);
    body.append("_form_started_at", String(formStartedAtRef.current || Date.now()));
    body.append("subject", "Chcę zlecić ręcznie malowany portret ze zdjęcia (Strona główna)");
    body.append("message", emailForm.message.trim());

    if (emailForm.shape) body.append("shape", emailForm.shape);
    if (emailForm.size) body.append("size", emailForm.size);

    emailFiles.forEach((file) => {
      body.append("files[]", file);
    });

    try {
      const res = await fetch(`${apiBase}/inquiries`, {
        method: "POST",
        body: body
      });

      const data = await res.json();
      if (!res.ok) {
        const errors = data.errors || {};
        const firstErr = Object.values(errors)[0];
        const errText = Array.isArray(firstErr) ? firstErr[0] : (data.message || "Błąd wysyłania zapytania.");
        throw new Error(errText);
      }

      setIsSubmitting(false);
      setEmailForm({ name: "", email: "", subject: "portrait_commission", message: "", shape: "", size: "" });
      setEmailFiles([]);
      if (typeof window !== "undefined") {
        window.location.href = "/sukces-kontakt";
      }
    } catch (err: any) {
      setIsSubmitting(false);
      setErrorMessage(err.message || "Nie udało się wysłać zapytania. Spróbuj ponownie.");
    }
  };

  const basePath = "";

  return (
    <div className="bg-white min-h-screen text-gray-900 animate-fadeIn">
      {/* 2.5 TESTIMONIALS SECTION */}
      <Testimonials />

      {/* 3. PROCESS TIMELINE: SYMMETRICAL, HORIZONTAL 4-STEP AXIS */}
      <section id="jak-zamowic-sekcja" className="py-20 md:py-28 lg:py-24 xl:py-20 2xl:py-32 scroll-mt-20">
        <div className="content-container">
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
        <div className="content-container space-y-24">
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
        <div className="content-container grid grid-cols-1 xl:grid-cols-12 gap-16 items-start">
          <div className="xl:col-span-5 space-y-8 font-sans text-center xl:text-left">
            <span className="font-mono text-xs text-[#E0115F] uppercase tracking-widest block font-bold">Porozmawiajmy o portrecie dla Ciebie</span>
            <h2 className="font-display text-5xl text-gray-950 leading-[1.1] font-normal">Stwórzmy coś wyjątkowego razem</h2>
            <p className="text-gray-600 text-base leading-relaxed max-w-xl mx-auto xl:mx-0">
              Prześlij swoje zdjęcia i opisz pomysł. Bez żadnych zobowiązań się temu przyjrzę i podpowiem jak najlepiej zaaranżować kompozycję oraz tło, aby portret Ci się spodobał!
            </p>
          </div>
          <div className="xl:col-span-7 bg-white rounded-3xl border border-off-black p-6 sm:p-10 space-y-6">
            <h3 className="font-display text-2xl text-gray-900 border-b border-gray-100 pb-4">Zamów wycenę Twojego portretu</h3>
            <form ref={formRef} noValidate onSubmit={handleContactSubmit} className="space-y-6 font-sans">
              <input
                type="text"
                name="website_url"
                value={websiteUrl}
                onChange={(e) => setWebsiteUrl(e.target.value)}
                style={{ position: "absolute", left: "-9999px", width: "1px", height: "1px", opacity: 0 }}
                tabIndex={-1}
                autoComplete="new-password"
                aria-hidden="true"
              />
              <input
                type="text"
                name="hp_field"
                value={hpField}
                onChange={(e) => setHpField(e.target.value)}
                style={{ position: "absolute", left: "-9999px", width: "1px", height: "1px", opacity: 0 }}
                tabIndex={-1}
                autoComplete="new-password"
                aria-hidden="true"
              />
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div className="space-y-1" data-error-field="name">
                  <label htmlFor="home-contact-name" className="text-xs font-mono font-bold uppercase tracking-wider text-gray-600 block">Imię *</label>
                  <input
                    type="text"
                    id="home-contact-name"
                    name="name"
                    autoComplete="name"
                    required
                    aria-invalid={Boolean(fieldErrors.name)}
                    aria-describedby={fieldErrors.name ? "home-contact-name-error" : undefined}
                    placeholder="np. Anna"
                    value={emailForm.name}
                    onChange={(e) => {
                      setEmailForm({ ...emailForm, name: e.target.value });
                      clearFieldError("name");
                    }}
                    className={`w-full p-3 bg-gray-55 border ${fieldErrors.name ? "border-red-500" : "border-gray-200"} focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-base transition-all`}
                  />
                  {fieldErrors.name && (
                    <p id="home-contact-name-error" className="text-xs text-red-600">{fieldErrors.name}</p>
                  )}
                </div>
                <div className="space-y-1" data-error-field="email">
                  <label htmlFor="home-contact-email" className="text-xs font-mono font-bold uppercase tracking-wider text-gray-600 block">Adres e-mail *</label>
                  <input
                    type="email"
                    id="home-contact-email"
                    name="email"
                    autoComplete="email"
                    required
                    aria-invalid={Boolean(fieldErrors.email)}
                    aria-describedby={fieldErrors.email ? "home-contact-email-error" : undefined}
                    placeholder="np. anna@gmail.com"
                    value={emailForm.email}
                    onChange={(e) => {
                      setEmailForm({ ...emailForm, email: e.target.value });
                      clearFieldError("email");
                    }}
                    className={`w-full p-3 bg-gray-55 border ${fieldErrors.email ? "border-red-500" : "border-gray-200"} focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-base transition-all`}
                  />
                  {fieldErrors.email && (
                    <p id="home-contact-email-error" className="text-xs text-red-600">{fieldErrors.email}</p>
                  )}
                </div>
              </div>

              <div className="space-y-1" data-error-field="shape">
                <label className="text-xs font-mono font-bold uppercase tracking-wider text-gray-600 block">Podobrazie *</label>
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                    emailForm.shape === "rectangle" && emailForm.size === "30x40" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-600 hover:bg-white"
                  }`}>
                    <input
                      type="radio"
                      name="podobrazie"
                      value="prostokat_30x40"
                      checked={emailForm.shape === "rectangle" && emailForm.size === "30x40"}
                      onChange={() => {
                        setEmailForm({ ...emailForm, shape: "rectangle", size: "30x40" });
                        clearFieldError("shape");
                      }}
                      className="sr-only"
                    />
                    Prostokątne 30x40
                  </label>
                  <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                    emailForm.shape === "oval" && emailForm.size === "30x40" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-600 hover:bg-white"
                  }`}>
                    <input
                      type="radio"
                      name="podobrazie"
                      value="owalne_30x40"
                      checked={emailForm.shape === "oval" && emailForm.size === "30x40"}
                      onChange={() => {
                        setEmailForm({ ...emailForm, shape: "oval", size: "30x40" });
                        clearFieldError("shape");
                      }}
                      className="sr-only"
                    />
                    Owalne 30x40
                  </label>
                  <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                    emailForm.shape === "other" && emailForm.size === "other" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-600 hover:bg-white"
                  }`}>
                    <input
                      type="radio"
                      name="podobrazie"
                      value="inne"
                      checked={emailForm.shape === "other" && emailForm.size === "other"}
                      onChange={() => {
                        setEmailForm({ ...emailForm, shape: "other", size: "other" });
                        clearFieldError("shape");
                      }}
                      className="sr-only"
                    />
                    Inne
                  </label>
                </div>
                {fieldErrors.shape && (
                  <p className="text-xs text-red-600 mt-1">{fieldErrors.shape}</p>
                )}
              </div>

              <div className="space-y-1" data-error-field="message">
                <label htmlFor="home-contact-message" className="text-xs font-mono font-bold uppercase tracking-wider text-gray-600 block">Opisz swoją wizję lub przeznaczenie obrazu *</label>
                <textarea
                  id="home-contact-message"
                  name="message"
                  required
                  aria-invalid={Boolean(fieldErrors.message)}
                  aria-describedby={fieldErrors.message ? "home-contact-message-error" : undefined}
                  rows={6}
                  placeholder="Dla kogo powstaje obraz? Czy to pamiątka rodzinna czy prezent na rocznicę? A może chcesz swojego kota w wielu miejscach jednocześnie (na drapaku i w ramce)? Daj znać!"
                  value={emailForm.message}
                  onChange={(e) => {
                    setEmailForm({ ...emailForm, message: e.target.value });
                    clearFieldError("message");
                  }}
                  className={`w-full p-3 bg-gray-55 border ${fieldErrors.message ? "border-red-500" : "border-gray-200"} focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-base transition-all resize-none leading-relaxed`}
                />
                {fieldErrors.message && (
                  <p id="home-contact-message-error" className="text-xs text-red-600">{fieldErrors.message}</p>
                )}
              </div>
              <div className="space-y-2" data-error-field="files">
                <span className="text-xs font-mono font-bold uppercase tracking-wider text-gray-600 block">Dołącz poglądowe zdjęcia (JPG, PNG, WEBP, PDF)</span>
                <div
                  onClick={() => fileInputRef.current?.click()}
                  className={`border border-dashed ${fieldErrors.files ? "border-red-500" : "border-gray-200"} rounded-xl p-6 text-center bg-gray-50/50 hover:bg-gray-50 transition-all cursor-pointer relative`}
                >
                  <input
                    type="file"
                    ref={fileInputRef}
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    multiple
                    onChange={(e) => {
                      if (!e.target.files) return;
                      const selected = Array.from(e.target.files);
                      const allowedMime = ["image/jpeg", "image/png", "image/webp", "application/pdf"];
                      const maxBytes = 10 * 1024 * 1024;
                      if (selected.length > 5) {
                        setFieldErrors((prev) => ({ ...prev, files: "Możesz załączyć maksymalnie 5 plików." }));
                        return;
                      }
                      const invalidFile = selected.find(
                        (f) => (!allowedMime.includes(f.type) && !/\.(jpe?g|png|webp|pdf)$/i.test(f.name)) || f.size > maxBytes
                      );
                      if (invalidFile) {
                        setFieldErrors((prev) => ({
                          ...prev,
                          files: "Dozwolone są wyłącznie pliki JPG, PNG, WEBP lub PDF o rozmiarze do 10 MB każdy.",
                        }));
                        return;
                      }
                      clearFieldError("files");
                      setEmailFiles(selected);
                    }}
                    className="hidden"
                  />
                  <div className="space-y-2 pointer-events-none">
                    <div className="text-xs font-medium text-gray-600">
                      {emailFiles.length > 0 ? (
                        <span className="text-[#E0115F] font-bold">
                          ✓ Wybrano {emailFiles.length} {emailFiles.length === 1 ? 'plik' : emailFiles.length < 5 ? 'pliki' : 'plików'}: {emailFiles.map(f => f.name).join(", ")}
                        </span>
                      ) : (
                        <span>Kliknij, by załączyć zdjęcia lub szkice (maks. 5 plików po 10 MB)</span>
                      )}
                    </div>
                    <p className="text-xs text-gray-600">Twoje dane są w pełni bezpieczne.</p>
                  </div>
                </div>
                {fieldErrors.files && (
                  <p className="text-xs text-red-600">{fieldErrors.files}</p>
                )}
              </div>
              <div className="pt-4 space-y-4">
                {formErrors.length > 0 && (
                  <div role="alert" className="p-3.5 rounded-xl bg-red-50 text-red-700 text-xs font-sans leading-relaxed border border-red-200">
                    <p className="font-semibold mb-1">Sprawdź formularz przed wysłaniem:</p>
                    <ul className="list-disc pl-4 space-y-0.5">
                      {formErrors.map((err, idx) => (
                        <li key={idx}>{err}</li>
                      ))}
                    </ul>
                  </div>
                )}
                {errorMessage && (
                  <div role="alert" className="p-3.5 rounded-xl bg-red-50 text-red-700 text-xs font-sans leading-relaxed border border-red-200">
                    {errorMessage}
                  </div>
                )}
                
                <button
                  type="submit"
                  disabled={isSubmitting}
                  className="button button--full cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <div className="button__blobs">
                    <div></div>
                    <div></div>
                    <div></div>
                  </div>
                  <div className="button__text">
                    {isSubmitting ? (
                      <span className="animate-pulse">Wysyłanie...</span>
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
                <p className="text-xs text-gray-600 font-sans text-center leading-relaxed font-normal">
                  Wysyłając wiadomość, akceptujesz przetwarzanie danych osobowych w celu obsługi zapytania zgodnie z <a href="/polityka-prywatnosci" className="underline hover:text-[#E0115F] transition-colors">Polityką prywatności</a>.
                </p>
              </div>
            </form>
          </div>
        </div>
      </section>

      <ProductSlider />

      <FaqSection />

      {isMounted && lightbox.isOpen && createPortal(
        <div
          className="fixed inset-0 w-full h-[100dvh] z-50 flex flex-col md:flex-row bg-neutral-950 text-white transition-opacity duration-300 overflow-hidden"
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
            className="flex-grow flex items-center justify-center p-6 md:p-12 relative h-[60dvh] md:h-[100dvh] bg-black"
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
            className="w-full md:w-[380px] lg:w-[420px] shrink-0 bg-neutral-900 border-t md:border-t-0 md:border-l border-white/10 p-6 md:p-10 flex flex-col justify-between h-[40dvh] md:h-[100dvh] relative z-55 overflow-y-auto"
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
