import React, { useState, useEffect, useCallback } from "react";
import { createPortal } from "react-dom";
import { PageId } from "../types";
import { GALLERY_ARTWORKS } from "../data/gallery";
import { 
  X, 
  ArrowLeft, 
  ArrowRight, 
  Search, 
  SlidersHorizontal,
  ExternalLink,
  MessageSquare
} from "lucide-react";

interface GalleryProps {
  setCurrentPage: (page: PageId) => void;
  handleNavigateToContact: (subject: string) => void;
}

type FilterType = "all" | "2024" | "2023" | "2022" | "older";

export default function Gallery({ setCurrentPage, handleNavigateToContact }: GalleryProps) {
  const [activeFilter, setActiveFilter] = useState<FilterType>("all");
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedImageIndex, setSelectedImageIndex] = useState<number | null>(null);

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

    // 2. Search query filter
    if (searchQuery.trim() !== "") {
      const query = searchQuery.toLowerCase();
      return (
        artwork.title.toLowerCase().includes(query) ||
        artwork.year.includes(query)
      );
    }

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

  return (
    <div className="animate-fadeIn pt-12 md:pt-20 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 max-w-[1600px] mx-auto space-y-16 font-sans">
      
      {/* Header section (Serif design) */}
      <header className="pb-12 max-w-4xl space-y-4">
        <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block">
          PORTFOLIO ZLECEŃ • GALERIA PORTRETÓW ZE ZDJĘCIA
        </span>
        <h1 className="font-display text-5xl md:text-6xl text-gray-900 tracking-tight font-normal">
          Malarstwo Portretowe
        </h1>
        <p className="text-gray-600 text-base sm:text-lg max-w-2xl leading-relaxed">
          Kolekcja obrazów olejnych stworzonych ręcznie na płótnie bawełnianym na podstawie fotografii powierzonych mi przez klientów. Każda praca to oddzielna historia, emocje i unikalna pamiątka pokoleniowa. Zobacz przekrój moich realizacji na przestrzeni lat.
        </p>
      </header>

      {/* Filter and Search Bar */}
      <div className="flex flex-col md:flex-row gap-6 items-start md:items-center justify-between border-b border-gray-150 pb-6">
        {/* Filter categories */}
        <div className="flex flex-wrap items-center gap-3">
          {[
            { id: "all", label: "Wszystkie lata" },
            { id: "2024", label: "2024 rok" },
            { id: "2023", label: "2023 rok" },
            { id: "2022", label: "2022 rok" },
            { id: "older", label: "Starsze prace" }
          ].map((filter) => (
            <button
              key={filter.id}
              onClick={() => {
                setActiveFilter(filter.id as FilterType);
                setSelectedImageIndex(null);
              }}
              className={`px-4 py-2.5 rounded-lg text-xs font-semibold tracking-wider transition-all uppercase ${
                activeFilter === filter.id
                  ? "bg-gray-950 text-white shadow-sm"
                  : "bg-gray-50 text-gray-600 border border-transparent hover:border-gray-200 hover:bg-white"
              }`}
            >
              {filter.label}
            </button>
          ))}
        </div>

        {/* Search Input */}
        <div className="relative w-full md:w-80">
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => {
              setSearchQuery(e.target.value);
              setSelectedImageIndex(null);
            }}
            placeholder="Szukaj portretu..."
            className="w-full py-3 pl-10 pr-4 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 placeholder:text-gray-400 focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all outline-none"
          />
          <Search className="absolute left-3.5 top-3.5 w-4 h-4 text-gray-400" />
        </div>
      </div>

      {/* Masonry Artwork Grid */}
      {filteredArtworks.length === 0 ? (
        <div className="text-center py-24 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-gray-500 text-sm">
          Brak realizacji spełniających kryteria. Spróbuj zmienić parametry filtrów.
        </div>
      ) : (
        <div className="columns-1 md:columns-2 lg:columns-3 gap-16 pt-6 [column-fill:_balance]">
          {filteredArtworks.map((artwork, index) => (
            <article
              key={artwork.id}
              onClick={() => setSelectedImageIndex(index)}
              className="break-inside-avoid mb-16 group flex flex-col justify-between space-y-4 cursor-pointer transition-all duration-500 bg-white border border-gray-50 hover:border-gray-200 p-4 rounded-[28px] hover:shadow-lg"
            >
              {/* Image Container */}
              <div className="relative rounded-2xl overflow-hidden bg-gray-50 border border-gray-100 w-full">
                <img
                  src={artwork.imageUrl}
                  alt={artwork.title}
                  loading="lazy"
                  referrerPolicy="no-referrer"
                  className="w-full h-auto block transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                />
                
                {/* Year Label overlay */}
                <span className="absolute bottom-4 right-4 bg-gray-950/80 backdrop-blur-md text-white text-xs font-mono px-3 py-1.5 rounded-lg font-medium tracking-widest uppercase border border-white/10">
                  {artwork.year}
                </span>
              </div>

              {/* Title & details block */}
              <div className="px-1 py-1">
                <h3 className="font-display text-lg text-gray-900 group-hover:text-[#E0115F] transition-colors leading-snug font-medium">
                  {artwork.title}
                </h3>
                <p className="text-xs text-gray-400 font-mono mt-1 uppercase tracking-wider">
                  Malarstwo olejone na płótnie
                </p>
              </div>
            </article>
          ))}
        </div>
      )}

      {/* Lightbox Modal (True Full-Screen Responsive Layout) */}
      {selectedImageIndex !== null && currentArtwork && createPortal(
        <div 
          className="fixed inset-0 w-screen h-screen z-50 flex flex-col md:flex-row bg-neutral-950 text-white transition-opacity duration-300 overflow-hidden"
          onClick={handleCloseLightbox}
        >
          {/* Main Image Area (Left/Center) */}
          <div 
            className="flex-grow flex items-center justify-center p-6 md:p-12 relative h-[60vh] md:h-screen bg-black"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Image */}
            <img
              src={currentArtwork.imageUrl}
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
            {/* Close button top right of sidebar on desktop */}
            <button 
              onClick={handleCloseLightbox}
              className="absolute top-6 right-6 text-neutral-400 hover:text-white bg-white/5 hover:bg-white/10 p-2.5 rounded-full transition-all duration-300"
              aria-label="Zamknij podgląd"
            >
              <X className="w-5 h-5" />
            </button>

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
                <p>• Tradycyjna technika olejna na płótnie bawełnianym</p>
                <p>• Naciąg na krosna sosnowe</p>
                <p>• Zabezpieczony werniksem końcowym</p>
                <p>• Wykonany ręcznie na zamówienie ze zdjęcia</p>
              </div>
            </div>

            {/* Actions Section */}
            <div className="pt-6 border-t border-white/5">
              <button
                onClick={() => {
                  setCurrentPage("home");
                  handleCloseLightbox();
                  setTimeout(() => {
                    const element = document.getElementById("kontakt-sekcja");
                    if (element) {
                      element.scrollIntoView({ behavior: "smooth", block: "start" });
                    }
                  }, 100);
                }}
                className="button button--full cursor-pointer"
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
              </button>
            </div>
          </div>
        </div>,
        document.body
      )}



      {/* bottom CTA card */}
      <section className="bg-stone-50 rounded-[32px] p-8 sm:p-12 border border-neutral-100 flex flex-col md:flex-row items-center justify-between gap-8 mt-24">
        <div className="space-y-2 text-center md:text-left max-w-2xl">
          <h2 className="font-display text-3xl text-gray-900 font-normal">
            Podoba Ci się mój warsztat malarski?
          </h2>
          <p className="text-stone-600 text-sm sm:text-base leading-relaxed">
            Możesz zamówić ręcznie malowany tradycyjny portret olejny z własnego zdjęcia. Zobacz ofertę i dostępne opcje personalizacji.
          </p>
        </div>
        <div className="flex flex-col sm:flex-row gap-4 shrink-0 w-full sm:w-auto">
          <button
            onClick={() => {
              setCurrentPage("home");
              setTimeout(() => {
                const element = document.getElementById("jak-zamowic-sekcja");
                if (element) {
                  element.scrollIntoView({ behavior: "smooth", block: "start" });
                }
              }, 100);
            }}
            className="button shrink-0"
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
          </button>
        </div>
      </section>

      {/* Grid spacing bottom */}
      <div className="h-16 hidden lg:block" />
    </div>
  );
}
