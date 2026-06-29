import React, { useState, useEffect, useCallback } from "react";
import { createPortal } from "react-dom";
import { GALLERY_ARTWORKS } from "../data/gallery";
import { 
  X, 
  ArrowLeft, 
  ArrowRight
} from "lucide-react";

const getSrc = (img: any): string => (img && typeof img === 'object' && 'src' in img ? img.src : img);

type FilterType = "all" | "2024" | "2023" | "2022" | "older";

const techniqueLabels: Record<string, string> = {
  olej: "Olej",
  akryl: "Akryl",
  akwarela: "Akwarela",
  rysunek: "Rysunek"
};

export default function Gallery() {
  const [activeFilter, setActiveFilter] = useState<FilterType>("all");
  const [activeTechnique, setActiveTechnique] = useState<"all" | "olej" | "akwarela" | "akryl">("all");
  const [selectedImageIndex, setSelectedImageIndex] = useState<number | null>(null);
  const [isMounted, setIsMounted] = useState(false);

  useEffect(() => {
    setIsMounted(true);
  }, []);

  // Block body scroll when lightbox is open
  useEffect(() => {
    if (selectedImageIndex !== null) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [selectedImageIndex]);

  // Filter logic
  const filteredArtworks = GALLERY_ARTWORKS.filter((artwork) => {
    // 1. Year filter
    if (activeFilter === "2024" && artwork.year !== "2024") return false;
    if (activeFilter === "2023" && artwork.year !== "2023") return false;
    if (activeFilter === "2022" && artwork.year !== "2022") return false;
    if (activeFilter === "older" && ["2024", "2023", "2022"].includes(artwork.year)) return false;

    // 2. Technique filter
    if (activeTechnique !== "all" && artwork.technique !== activeTechnique) return false;

    return true;
  });

  // Lightbox navigation functions
  const handleCloseLightbox = () => {
    setSelectedImageIndex(null);
  };

  const handlePrevImage = useCallback((e?: React.MouseEvent) => {
    if (e) e.stopPropagation();
    if (selectedImageIndex === null || filteredArtworks.length === 0) return;
    setSelectedImageIndex((prevIndex) => {
      if (prevIndex === null) return 0;
      return prevIndex === 0 ? filteredArtworks.length - 1 : prevIndex - 1;
    });
  }, [selectedImageIndex, filteredArtworks]);

  const handleNextImage = useCallback((e?: React.MouseEvent) => {
    if (e) e.stopPropagation();
    if (selectedImageIndex === null || filteredArtworks.length === 0) return;
    setSelectedImageIndex((prevIndex) => {
      if (prevIndex === null) return 0;
      return prevIndex === filteredArtworks.length - 1 ? 0 : prevIndex + 1;
    });
  }, [selectedImageIndex, filteredArtworks]);

  // Handle keyboard events (ESC, Left, Right)
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (selectedImageIndex === null) return;
      if (e.key === "Escape") {
        handleCloseLightbox();
      } else if (e.key === "ArrowLeft") {
        handlePrevImage();
      } else if (e.key === "ArrowRight") {
        handleNextImage();
      }
    };

    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [selectedImageIndex, handlePrevImage, handleNextImage]);

  // Currently viewing artwork in lightbox
  const currentArtwork = selectedImageIndex !== null ? filteredArtworks[selectedImageIndex] : null;
  const basePath = "/hellokostek";

  return (
    <div className="animate-fadeIn pt-12 md:pt-20 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 max-w-[1600px] mx-auto space-y-16 font-sans">
      
      {/* Header section (Serif design) */}
      <header className="pb-12 max-w-4xl space-y-4">
        <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block">
          portfolio
        </span>
        <h1 className="font-display text-5xl md:text-6xl text-gray-900 tracking-tight font-normal">
          Malarstwo Portretowe
        </h1>
        <p className="text-gray-600 text-base sm:text-lg max-w-3xl leading-relaxed">
          Prawdziwe malarstwo przetrwa setki lat, a zdjęcie w rolce aparatu Twojego iPhona zniknie za chwilę przy kolejnej zmianie telefonu. Zamień cyfrowe wspomnienia w trwałe, ręcznie malowane dzieło sztuki.
        </p>
      </header>

      {/* Filter Rail (Aesthetic and Responsive) */}
      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-6 border-b border-gray-150 pb-6">
        {/* Technique filters (Left) */}
        <div className="flex flex-wrap items-center gap-3">
          {[
            { id: "all", label: "Wszystkie techniki" },
            { id: "olej", label: "Olej" },
            { id: "akwarela", label: "Akwarela" },
            { id: "akryl", label: "Akryl" }
          ].map((tech) => (
            <button
              key={tech.id}
              onClick={() => {
                setActiveTechnique(tech.id as any);
                setSelectedImageIndex(null);
              }}
              className={`px-4 py-2.5 rounded-lg text-xs font-semibold tracking-wider transition-all uppercase cursor-pointer ${
                activeTechnique === tech.id
                  ? "bg-gray-950 text-white shadow-sm"
                  : "bg-gray-55 text-gray-600 border border-transparent hover:border-gray-200 hover:bg-white"
              }`}
            >
              {tech.label}
            </button>
          ))}
        </div>

        {/* Year filters (Right) */}
        <div className="flex flex-wrap items-center gap-3 lg:justify-end">
          {[
            { id: "all", label: "Wszystkie lata" },
            { id: "2024", label: "2024" },
            { id: "2023", label: "2023" },
            { id: "2022", label: "2022" },
            { id: "older", label: "Starsze prace" }
          ].map((filter) => (
            <button
              key={filter.id}
              onClick={() => {
                setActiveFilter(filter.id as FilterType);
                setSelectedImageIndex(null);
              }}
              className={`px-4 py-2.5 rounded-lg text-xs font-semibold tracking-wider transition-all uppercase cursor-pointer ${
                activeFilter === filter.id
                  ? "bg-gray-950 text-white shadow-sm"
                  : "bg-gray-55 text-gray-600 border border-transparent hover:border-gray-200 hover:bg-white"
              }`}
            >
              {filter.label}
            </button>
          ))}
        </div>
      </div>

      {/* Masonry Artwork Grid */}
      {filteredArtworks.length === 0 ? (
        <div className="text-center py-24 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-gray-500 text-sm">
          Brak realizacji spełniających kryteria. Spróbuj zmienić parametry filtrów.
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 pt-6">
          {filteredArtworks.map((artwork, index) => (
            <article
              key={artwork.id}
              onClick={() => setSelectedImageIndex(index)}
              className="group flex flex-col justify-between cursor-pointer transition-all duration-500 bg-white border border-gray-55 hover:border-gray-200 p-4 rounded-[28px] hover:shadow-lg"
            >
              {/* Image Container */}
              <div className="relative rounded-2xl overflow-hidden bg-gray-50 border border-gray-100 w-full aspect-square">
                <img
                  src={getSrc(artwork.imageUrl)}
                  alt={artwork.title}
                  loading="lazy"
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-cover block transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                />
                
                {/* Technique Label overlay (Left top) */}
                <span className="absolute top-4 left-4 bg-white/90 backdrop-blur-md text-gray-905 text-xs font-mono font-bold px-3 py-1.5 rounded-lg uppercase tracking-widest border border-gray-100 shadow-xs z-10">
                  {techniqueLabels[artwork.technique] || artwork.technique}
                </span>

                {/* Year Label overlay */}
                <span className="absolute bottom-4 right-4 bg-gray-950 text-white text-xs font-mono px-3 py-1.5 rounded-lg font-medium tracking-widest uppercase z-10">
                  {artwork.year}
                </span>
              </div>
            </article>
          ))}
        </div>
      )}

      {/* Lightbox Modal (True Full-Screen Responsive Layout) */}
      {isMounted && selectedImageIndex !== null && currentArtwork && createPortal(
        <div 
          className="fixed inset-0 w-screen h-screen z-50 flex flex-col md:flex-row bg-neutral-950 text-white transition-opacity duration-300 overflow-hidden"
          onClick={handleCloseLightbox}
        >
          {/* Global Close Button (Float top right, highly accessible) */}
          <button
            onClick={handleCloseLightbox}
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
              src={getSrc(currentArtwork.imageUrl)}
              alt={currentArtwork.title}
              referrerPolicy="no-referrer"
              className="max-w-full max-h-full object-contain rounded-lg shadow-2xl select-none"
            />

            {/* Navigation buttons overlaid on the image area (matching slider styling) */}
            <button
              onClick={handlePrevImage}
              className="absolute left-4 md:left-8 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white hover:bg-gray-50 text-gray-900 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-[0_0_15px_rgba(196,240,19,0.45)] focus:outline-none z-55 cursor-pointer border-none"
              aria-label="Poprzednie zdjęcie"
            >
              <ArrowLeft className="w-5 h-5" />
            </button>

            <button
              onClick={handleNextImage}
              className="absolute right-4 md:right-8 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white hover:bg-gray-50 text-gray-900 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-[0_0_15px_rgba(196,240,19,0.45)] focus:outline-none z-55 cursor-pointer border-none"
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
                <span>Rok powstania: {currentArtwork.year}</span>
                <span>Praca {selectedImageIndex + 1} z {filteredArtworks.length}</span>
              </div>
              
              <h2 className="font-display text-2xl md:text-3xl font-normal tracking-tight text-white leading-tight">
                {currentArtwork.title}
              </h2>

              <div className="space-y-2.5 text-xs text-neutral-400 leading-relaxed">
                {currentArtwork.technique === "olej" && (
                  <>
                    <p>• Tradycyjna technika olejna na płótnie bawełnianym</p>
                    <p>• Naciąg na krosna sosnowe</p>
                    <p>• Zabezpieczony werniksem końcowym</p>
                    <p>• Wykonany ręcznie na zamówienie ze zdjęcia</p>
                  </>
                )}
                {currentArtwork.technique === "akryl" && (
                  <>
                    <p>• Malarstwo akrylowe na płótnie</p>
                    <p>• Naciąg na krosna sosnowe</p>
                    <p>• Zabezpieczony werniksem końcowym</p>
                    <p>• Wykonany ręcznie</p>
                  </>
                )}
                {currentArtwork.technique === "rysunek" && (
                  <>
                    <p>• Tradycyjny rysunek na papierze</p>
                    <p>• Wykonany ołówkiem lub suchym pastelem</p>
                    <p>• Utrwalony fiksatywą ochronną</p>
                    <p>• Wykonany ręcznie</p>
                  </>
                )}
                {currentArtwork.technique === "akwarela" && (
                  <>
                    <p>• Akwarela na wysokiej jakości papierze bawełnianym</p>
                    <p>• Unikalne, płynne przejścia barwne</p>
                    <p>• Wykonany ręcznie</p>
                  </>
                )}
              </div>
            </div>

            {/* Actions Section */}
            <div className="pt-6 border-t border-white/5">
              <a
                href={`${basePath}/#kontakt-sekcja`}
                className="button button--full cursor-pointer text-center"
              >
                <div className="button__blobs">
                  <div></div>
                  <div></div>
                  <div></div>
                </div>
                <div className="button__text font-bold">
                  Chcę podobny portret
                  <ArrowRight className="w-4 h-4 ml-1.5" />
                </div>
              </a>
            </div>
          </div>
        </div>,
        document.body
      )}

      {/* bottom CTA card */}
      <section className="bg-stone-50 rounded-[32px] p-8 sm:p-12 border border-neutral-100 flex flex-col md:flex-row items-center justify-between gap-8 mt-24">
        <div className="space-y-2 text-center md:text-left max-w-2xl">
          <h2 className="font-display text-3xl text-gray-900 font-normal">
            Podoba Ci się?
          </h2>
          <p className="text-stone-600 text-sm sm:text-base leading-relaxed max-w-[530px]">
            Możesz zamówić ręcznie malowany portret olejny z własnego zdjęcia. Zobacz ofertę i dostępne opcje personalizacji.
          </p>
        </div>
        <div className="flex flex-col sm:flex-row gap-4 shrink-0 w-full sm:w-auto">
          <a
            href={`${basePath}/#jak-zamowic-sekcja`}
            className="button shrink-0 text-center"
          >
            <div className="button__blobs">
              <div></div>
              <div></div>
              <div></div>
            </div>
            <div className="button__text font-bold">
              Oferta portretów ze zdjęcia
              <ArrowRight className="w-4 h-4 ml-1.5" />
            </div>
          </a>
        </div>
      </section>

      {/* Grid spacing bottom */}
      <div className="h-16 hidden lg:block" />
    </div>
  );
}
