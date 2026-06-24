import { PageId, CartItem } from "../types";
import { Menu, X, ArrowRight } from "lucide-react";
import React, { useState, useEffect } from "react";

interface NavbarProps {
  currentPage: PageId;
  setCurrentPage: (page: PageId) => void;
  cart: CartItem[];
  setIsCartOpen: (open: boolean) => void;
}

export default function Navbar({
  currentPage,
  setCurrentPage,
  cart,
  setIsCartOpen,
}: NavbarProps) {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const [isHeroScrolledPast, setIsHeroScrolledPast] = useState(false);

  useEffect(() => {
    const handleScroll = () => {
      const scrollY = window.scrollY;
      
      setIsScrolled((prev) => {
        if (scrollY > 100) {
          return true;
        } else if (scrollY < 20) {
          return false;
        }
        return prev;
      });

      // Check if scrolled past the hero section (approx 700px on desktop)
      if (scrollY > 700) {
        setIsHeroScrolledPast(true);
      } else {
        setIsHeroScrolledPast(false);
      }
    };

    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  const handleJakZamowic = () => {
    setIsMobileMenuOpen(false);
    if (currentPage !== "home") {
      setCurrentPage("home");
      setTimeout(() => {
        const element = document.getElementById("jak-zamowic-sekcja");
        if (element) {
          element.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      }, 100);
    } else {
      const element = document.getElementById("jak-zamowic-sekcja");
      if (element) {
        element.scrollIntoView({ behavior: "smooth", block: "start" });
      }
    }
  };

  return (
    <nav className="sticky top-0 z-50 bg-white border-b border-neutral-100 transition-all duration-500 ease-in-out">
      <div className={`max-w-[1600px] mx-auto flex items-center justify-between px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 transition-all duration-500 ease-in-out ${
        isScrolled ? "py-0.5 md:py-1" : "py-1.5 md:py-2"
      }`}>
        
        {/* Left Side Navigation (Desktop) - Links */}
        <div className="hidden md:flex items-center space-x-4 lg:space-x-6 w-5/12">
          <button
            onClick={() => {
              setCurrentPage("home");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`font-sans text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              currentPage === "home" ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            Portrety
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              currentPage === "home" ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </button>
          
          <button
            onClick={() => {
              setCurrentPage("gallery");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`font-sans text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              currentPage === "gallery" ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            Galeria
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              currentPage === "gallery" ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </button>
          
          <button
            onClick={() => {
              setCurrentPage("shop");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`font-sans text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              currentPage === "shop" ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            Sklep
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              currentPage === "shop" ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </button>

          <button
            onClick={() => {
              setCurrentPage("about");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`font-sans text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              currentPage === "about" ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            O mnie
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              currentPage === "about" ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </button>
          
          <button
            onClick={() => {
              setCurrentPage("contact");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`font-sans text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              currentPage === "contact" ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            Kontakt
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              currentPage === "contact" ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </button>
        </div>

        {/* Centered Logo (Daniel Arthury inspired) - Magnified and animated */}
        <div className="flex justify-center items-center w-2/12 text-center">
          <button
            onClick={() => {
              setCurrentPage("home");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className="flex flex-col items-center group focus:outline-none"
            aria-label="Strona główna hellokostek"
          >
            <div className={`flex items-center justify-center overflow-hidden transition-all duration-500 ease-in-out p-1 ${
              isScrolled 
                ? "w-[150px] h-[45px] md:w-[210px] md:h-[63px]" 
                : "w-[240px] h-[72px] md:w-[370px] md:h-[111px]"
            }`}>
              <img
                src="https://hellokostek.pl/wp-content/uploads/2021/05/logo-animation-30fps-v-2.gif"
                alt="hellokostek logo"
                referrerPolicy="no-referrer"
                className="max-w-full max-h-full object-contain mix-blend-multiply transition-all duration-500"
              />
            </div>
          </button>
        </div>

        {/* Right Side Navigation (Desktop) - CTA Button */}
        <div className="hidden md:flex items-center justify-end w-5/12">
          <button
            onClick={() => {
              if (currentPage !== "home") {
                setCurrentPage("home");
                setTimeout(() => {
                  const formSection = document.getElementById("kontakt-sekcja");
                  if (formSection) formSection.scrollIntoView({ behavior: "smooth" });
                }, 100);
              } else {
                const formSection = document.getElementById("kontakt-sekcja");
                if (formSection) formSection.scrollIntoView({ behavior: "smooth" });
              }
            }}
            className={`button button--nav ${(currentPage === "home" && !isHeroScrolledPast) ? "button--secondary" : ""}`}
          >
            <div className="button__blobs">
              <div></div>
              <div></div>
              <div></div>
            </div>
            <div className="button__text">
              Zamów portret
              <ArrowRight className="w-4 h-4" />
            </div>
          </button>
        </div>

        {/* Mobile controls */}
        <div className="flex md:hidden items-center">
          <button
            onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            className="p-2 text-off-black bg-stone-100 hover:bg-stone-200 transition-colors rounded-full"
            aria-label="Główne Menu"
          >
            {isMobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>
      </div>

      {/* Mobile Menu panel */}
      {isMobileMenuOpen && (
        <div className="md:hidden mt-4 pt-4 border-t border-neutral-100 flex flex-col space-y-4 pb-4 animate-fadeIn px-6">
          <button
            onClick={() => {
              setCurrentPage("home");
              setIsMobileMenuOpen(false);
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`text-left text-xs uppercase tracking-widest font-sans py-2 border-l-2 pl-3 transition-colors ${
              currentPage === "home" ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            Portrety
          </button>
          
          <button
            onClick={() => {
              setCurrentPage("gallery");
              setIsMobileMenuOpen(false);
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`text-left text-xs uppercase tracking-widest font-sans py-2 border-l-2 pl-3 transition-colors ${
              currentPage === "gallery" ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            Galeria
          </button>
          
          <button
            onClick={() => {
              setCurrentPage("shop");
              setIsMobileMenuOpen(false);
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`text-left text-xs uppercase tracking-widest font-sans py-2 border-l-2 pl-3 transition-colors ${
              currentPage === "shop" ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            Sklep
          </button>

          <button
            onClick={() => {
              setCurrentPage("about");
              setIsMobileMenuOpen(false);
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`text-left text-xs uppercase tracking-widest font-sans py-2 border-l-2 pl-3 transition-colors ${
              currentPage === "about" ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            O mnie
          </button>

          <button
            onClick={() => {
              setCurrentPage("contact");
              setIsMobileMenuOpen(false);
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            className={`text-left text-xs uppercase tracking-widest font-sans py-2 border-l-2 pl-3 transition-colors ${
              currentPage === "contact" ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            Kontakt
          </button>
        </div>
      )}
    </nav>
  );
}
