import React, { useState, useRef } from "react";
import { Mail, Send, MessageSquare, Check, MapPin, Instagram, Facebook, ChevronDown } from "lucide-react";

import { PageId } from "../types";

interface ContactProps {
  initialSubject?: string;
  setCurrentPage?: (page: PageId) => void;
}

export default function Contact({ initialSubject = "", setCurrentPage }: ContactProps) {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    subject: initialSubject,
    message: "",
    shape: "rectangle",
    size: "30x40"
  });

  const [files, setFiles] = useState<File[]>([]);
  const [isSent, setIsSent] = useState(false);
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const subjects = [
    { value: "portrait_commission", label: "Chcę zlecić ręcznie malowany portret ze zdjęcia" },
    { value: "shop_delivery", label: "Interesuje mnie zakup oryginalnej akwareli lub rysunku" },
    { value: "other_question", label: "Zapytanie o dostępność prac / własny format / inne" }
  ];

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      setFiles(Array.from(e.target.files));
    }
  };

  const getMessageFieldDetails = () => {
    switch (formData.subject) {
      case "portrait_commission":
        return {
          label: "Opisz swoją wizję lub przeznaczenie obrazu *",
          placeholder: "Napisz dla kogo powstaje obraz, czy to pamiątka rodzinna, prezent na rocznicę i czy masz już wybrane ujęcia zdjęciowe..."
        };
      case "shop_delivery":
        return {
          label: "Która praca z galerii / sklepu Cię interesuje? *",
          placeholder: "Wpisz tytuł akwareli lub rysunku, który chcesz kupić, albo zadaj pytanie o tę konkretną pracę..."
        };
      default:
        return {
          label: "Treść wiadomości *",
          placeholder: "Wpisz tutaj swoją wiadomość, pytania lub uwagi..."
        };
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.subject) {
      setIsDropdownOpen(true);
      return;
    }
    
    if (setCurrentPage) {
      setCurrentPage("success-contact");
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  };



  return (
    <div className="animate-fadeIn pt-12 md:pt-20 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 max-w-[1600px] mx-auto space-y-24">
      <header className="border-b border-gray-100 pb-12 w-full">
        <div className="max-w-4xl">
          <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block mb-4">
            Kontakt ze mną
          </span>
          <h1 className="font-display text-6xl text-gray-900 tracking-tight leading-none font-normal">
            Utrwalmy Twoje wspomnienia
          </h1>
          <p className="font-sans text-gray-600 text-base sm:text-lg max-w-2xl mt-4 leading-relaxed">
            Niezależnie od tego, czy masz już gotowe zdjęcia, czy potrzebujesz pomocy w wyborze kompozycji i tła – napisz do mnie. Każdy projekt zaczyna się od rozmowy. Chętnie pomogę Ci stworzyć coś wyjątkowego!
          </p>
        </div>
      </header>

      {/* Main Asymmetric Split Section */}
      <section className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
        {/* Left Side: Bold Local Contact & Delivery Copy */}
        <div className="lg:col-span-5 space-y-10">
          <div className="space-y-8">
            <div className="flex gap-4 items-start">
              <div className="w-12 h-12 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-center text-[#E0115F] shrink-0 mt-0.5">
                <Mail className="w-5 h-5" />
              </div>
              <div className="text-base font-sans">
                <span className="block text-gray-400 font-mono text-xs uppercase tracking-widest font-semibold">Kontakt</span>
                <a href="mailto:kontakt@hellokostek.pl" className="text-gray-900 hover:text-[#E0115F] font-bold block mt-1 transition-colors text-lg">
                  kontakt@hellokostek.pl
                </a>
                <span className="text-gray-500 block text-sm mt-1">Odpowiadam w ciągu 24 godzin, podając wycenę Twojego zamówienia.</span>
              </div>
            </div>

            <div className="flex gap-4 items-start">
              <div className="w-12 h-12 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-center text-[#E0115F] shrink-0 mt-0.5">
                <MapPin className="w-5 h-5" />
              </div>
              <div className="text-base font-sans">
                <span className="block text-gray-400 font-mono text-xs uppercase tracking-widest font-semibold">Lokalizacja</span>
                <span className="text-gray-900 font-bold block mt-1 text-lg">Łódź, Śródmieście, Polska</span>
                <span className="text-gray-500 block text-sm mt-1">Oferuję darmowy dowóz gotowego obrazu na terenie Łodzi oraz bezpieczną wysyłkę w całej Polsce.</span>
              </div>
            </div>

            <div className="flex gap-4 items-start">
              <div className="w-12 h-12 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-center text-[#E0115F] shrink-0 mt-0.5">
                <MessageSquare className="w-5 h-5" />
              </div>
              <div className="text-base font-sans w-full">
                <span className="block text-gray-400 font-mono text-xs uppercase tracking-widest font-semibold">Media społecznościowe</span>
                <div className="flex flex-wrap gap-3 mt-1.5">
                  <a
                    href="https://www.instagram.com/hellokostek/"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 border border-gray-200 hover:border-[#C4F013] transition-all rounded-xl text-sm font-sans text-gray-800 hover:text-black font-medium"
                  >
                    <Instagram className="w-4 h-4 text-[#E0115F]" />
                    <span>Instagram</span>
                  </a>
                  <a
                    href="https://www.facebook.com/hellokostek/"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 border border-gray-200 hover:border-[#C4F013] transition-all rounded-xl text-sm font-sans text-gray-800 hover:text-black font-medium"
                  >
                    <Facebook className="w-4 h-4 text-[#1877F2]" />
                    <span>Facebook</span>
                  </a>
                </div>
                <span className="text-gray-500 block text-sm mt-2">Zobacz, jak powstają moje prace, i śledź moją codzienność na żywo.</span>
              </div>
            </div>
          </div>
        </div>

        {/* Right Side: Contact Form (5/12 width) */}
        <div className="lg:col-span-7 bg-white rounded-3xl border border-gray-200 p-6 sm:p-10 space-y-6">
          <h3 className="font-display text-2xl text-gray-900 border-b border-gray-100 pb-4">Wypełnij formularz kontaktowy</h3>

          <form onSubmit={handleSubmit} className="space-y-6 font-sans">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div className="space-y-1">
                <label className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Imię i Nazwisko *</label>
                <input
                  type="text"
                  name="name"
                  required
                  value={formData.name}
                  onChange={handleInputChange}
                  placeholder="np. Anna Kowalska"
                  className="w-full p-3 bg-gray-50 border border-gray-200 focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-sm transition-all"
                />
              </div>

              <div className="space-y-1">
                <label className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Twój Adres E-mail *</label>
                <input
                  type="email"
                  name="email"
                  required
                  value={formData.email}
                  onChange={handleInputChange}
                  placeholder="np. anna.kowalska@gmail.com"
                  className="w-full p-3 bg-gray-50 border border-gray-200 focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-sm transition-all"
                />
              </div>
            </div>

            <div className="space-y-1">
              <label className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Czego dotyczy Twoja wizja?</label>
              <div className="relative">
                <button
                  type="button"
                  onClick={() => setIsDropdownOpen(!isDropdownOpen)}
                  className={`w-full p-3 pr-10 bg-gray-50 border border-gray-200 focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-sm transition-all cursor-pointer text-left flex justify-between items-center ${
                    formData.subject ? "text-gray-950" : "text-gray-400"
                  }`}
                >
                  <span>
                    {formData.subject 
                      ? subjects.find(s => s.value === formData.subject)?.label 
                      : "Wybierz temat..."
                    }
                  </span>
                  <ChevronDown className={`w-4 h-4 text-gray-400 transition-transform ${isDropdownOpen ? 'rotate-180' : ''}`} />
                </button>

                {isDropdownOpen && (
                  <>
                    <div 
                      className="fixed inset-0 z-10" 
                      onClick={() => setIsDropdownOpen(false)}
                    />
                    <div className="absolute left-0 right-0 mt-2 bg-white border border-gray-200 rounded-2xl shadow-lg z-20 py-1 overflow-hidden animate-scaleIn origin-top">
                      {subjects.map((subj) => (
                        <button
                          key={subj.value}
                          type="button"
                          onClick={() => {
                            setFormData(prev => ({ ...prev, subject: subj.value }));
                            setIsDropdownOpen(false);
                          }}
                          className={`w-full text-left px-4 py-3 text-sm transition-colors hover:bg-gray-50 ${
                            formData.subject === subj.value 
                              ? "bg-gray-50/80 text-[#E0115F] font-semibold" 
                              : "text-gray-700"
                          }`}
                        >
                          {subj.label}
                        </button>
                      ))}
                    </div>
                  </>
                )}
              </div>
            </div>

            {/* Custom parameters fields appearing with high-padding for custom portraits option */}
            {formData.subject === "portrait_commission" && (
              <div className="animate-scaleIn space-y-6">
                <div className="space-y-1">
                  <label className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Kształt podobrazia</label>
                  <div className="flex gap-3">
                    <label className={`flex-1 flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                      formData.shape === "rectangle" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-500 hover:bg-white"
                    }`}>
                      <input
                        type="radio"
                        name="shape"
                        value="rectangle"
                        checked={formData.shape === "rectangle"}
                        onChange={handleInputChange}
                        className="sr-only"
                      />
                      Prostokątne
                    </label>
                    <label className={`flex-1 flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                      formData.shape === "oval" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-500 hover:bg-white"
                    }`}>
                      <input
                        type="radio"
                        name="shape"
                        value="oval"
                        checked={formData.shape === "oval"}
                        onChange={handleInputChange}
                        className="sr-only"
                      />
                      Owalne
                    </label>
                  </div>
                </div>

                <div className="space-y-1">
                  <label className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Wybierz wstępny rozmiar</label>
                  <div className="grid grid-cols-2 gap-3">
                    <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                      formData.size === "30x40" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-500 hover:bg-white"
                    }`}>
                      <input
                        type="radio"
                        name="size"
                        value="30x40"
                        checked={formData.size === "30x40"}
                        onChange={handleInputChange}
                        className="sr-only"
                      />
                      Standardowy (30 x 40 cm)
                    </label>
                    <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                      formData.size === "40x55" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-500 hover:bg-white"
                    }`}>
                      <input
                        type="radio"
                        name="size"
                        value="40x55"
                        checked={formData.size === "40x55"}
                        onChange={handleInputChange}
                        className="sr-only"
                      />
                      Średni (40 x 55 cm)
                    </label>
                    <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                      formData.size === "50x70" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-500 hover:bg-white"
                    }`}>
                      <input
                        type="radio"
                        name="size"
                        value="50x70"
                        checked={formData.size === "50x70"}
                        onChange={handleInputChange}
                        className="sr-only"
                      />
                      Wielki (50 x 70 cm)
                    </label>
                    <label className={`flex items-center justify-center p-3 border rounded-xl cursor-pointer hover:border-[#C4F013] text-sm font-sans transition-all text-center ${
                      formData.size === "custom" ? "border-[#C4F013] ring-1 ring-[#C4F013] bg-white shadow-xs font-semibold text-gray-950" : "border-gray-200 bg-gray-50 text-gray-500 hover:bg-white"
                    }`}>
                      <input
                        type="radio"
                        name="size"
                        value="custom"
                        checked={formData.size === "custom"}
                        onChange={handleInputChange}
                        className="sr-only"
                      />
                      Niestandardowy (własny format)
                    </label>
                  </div>
                </div>
              </div>
            )}

            <div className="space-y-1">
              <label className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">
                {getMessageFieldDetails().label}
              </label>
              <textarea
                name="message"
                required
                rows={4}
                value={formData.message}
                onChange={handleInputChange}
                placeholder={getMessageFieldDetails().placeholder}
                className="w-full p-3 bg-gray-50 border border-gray-200 focus:border-[#C4F013] focus:ring-1 focus:ring-[#C4F013] focus:bg-white outline-none rounded-xl text-sm transition-all resize-none leading-relaxed"
              />
            </div>

            {formData.subject !== "shop_delivery" && (
              <div className="space-y-2 animate-fadeIn">
                <span className="text-xs font-mono font-bold uppercase tracking-wider text-gray-400 block">Dołącz poglądowe zdjęcia (JPG, PNG)</span>
                <div
                  onClick={() => fileInputRef.current?.click()}
                  className="border border-dashed border-gray-200 rounded-xl p-6 text-center bg-gray-50/50 hover:bg-gray-50 transition-all cursor-pointer relative"
                >
                  <input
                    type="file"
                    ref={fileInputRef}
                    multiple
                    onChange={handleFileChange}
                    className="hidden"
                  />
                  <div className="space-y-2 pointer-events-none">
                    <div className="text-xs font-medium text-gray-500">
                      {files.length > 0 ? (
                        <span className="text-[#E0115F] font-bold">
                          ✓ Wybrano {files.length} {files.length === 1 ? 'plik' : files.length < 5 ? 'pliki' : 'plików'}: {files.map(f => f.name).join(", ")}
                        </span>
                      ) : (
                        <span>Kliknij, by załączyć klatki zdjęciowe (możesz też wysłać je bezpośrednio w mailu)</span>
                      )}
                    </div>
                    <p className="text-xs text-gray-400">Twoje dane są w pełni bezpieczne.</p>
                  </div>
                </div>
              </div>
            )}

            <div className="pt-4">
              <button
                type="submit"
                className="button button--full"
              >
                <div className="button__blobs">
                  <div></div>
                  <div></div>
                  <div></div>
                </div>
                <div className="button__text">
                  <Send className="w-4 h-4" />
                  Wyślij zapytanie
                </div>
              </button>
              <p className="text-xs text-gray-550 font-sans text-center mt-4 leading-relaxed">
                Wiadomość zostanie przesłana bezpośrednio do mojej Pracowni Artystycznej. Odpowiedź wraz z propozycją kompozycji otrzymasz na podany adres e-mail.
              </p>
            </div>
          </form>
        </div>
      </section>
    </div>
  );
}
