import { Menu, X, ArrowRight } from "lucide-react";
import React, { useState, useEffect } from "react";

interface NavbarProps {
  currentPath: string;
}

export default function Navbar({ currentPath }: NavbarProps) {
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

  // Normalizing current path for comparison (handling trailing slashes and base path)
  const normPath = currentPath.endsWith('/') && currentPath.length > 1 ? currentPath.slice(0, -1) : currentPath;
  const basePath = "/hellokostek";

  const isHome = normPath === basePath || normPath === `${basePath}/` || normPath === '/' || normPath === '';
  const isGallery = normPath === `${basePath}/galeria` || normPath === '/galeria';
  const isShop = normPath.startsWith(`${basePath}/sklep`) || normPath.startsWith('/sklep');
  const isAbout = normPath === `${basePath}/o-mnie` || normPath === '/o-mnie';
  const isContact = normPath === `${basePath}/kontakt` || normPath === '/kontakt';

  return (
    <nav className="sticky top-0 z-50 bg-white border-b border-neutral-100 transition-all duration-500 ease-in-out">
      <div className="max-w-[1600px] mx-auto flex items-center justify-between px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 transition-all duration-500 ease-in-out py-0">
        
        {/* Left Side Navigation (Desktop) - Links */}
        <div className="hidden md:flex items-center space-x-4 lg:space-x-6 w-5/12">
          <a
            href={`${basePath}/`}
            className={`font-mono text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              isHome ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            hellokostek
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              isHome ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </a>
          
          <a
            href={`${basePath}/galeria`}
            className={`font-mono text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              isGallery ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            Galeria
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              isGallery ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </a>
          
          <a
            href={`${basePath}/sklep`}
            className={`font-mono text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              isShop ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            Sklep
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              isShop ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </a>

          <a
            href={`${basePath}/o-mnie`}
            className={`font-mono text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              isAbout ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            O mnie
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              isAbout ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </a>
          
          <a
            href={`${basePath}/kontakt`}
            className={`font-mono text-[13px] uppercase tracking-widest transition-all duration-300 hover:text-lime-accent relative py-1 group ${
              isContact ? "text-magenta-accent font-semibold" : "text-off-black/60"
            }`}
          >
            Kontakt
            <span className={`absolute bottom-0 left-0 w-full h-[1px] transition-transform duration-300 origin-left ${
              isContact ? "bg-magenta-accent scale-x-100" : "bg-lime-accent scale-x-0 group-hover:scale-x-100"
            }`} />
          </a>
        </div>

        {/* Centered Logo (Daniel Arthury inspired) - Magnified and animated */}
        <div className="flex justify-start md:justify-center items-center flex-1 md:flex-none md:w-2/12 text-left md:text-center">
          <a
            href={`${basePath}/`}
            className="flex flex-col items-center md:items-center group focus:outline-none"
            aria-label="Strona główna hellokostek"
          >
            <div className={`flex items-center justify-start md:justify-center overflow-hidden transition-all duration-500 ease-in-out ${
              isScrolled 
                ? "w-[180px] h-[54px] md:w-[260px] md:h-[78px]" 
                : "w-[320px] h-[96px] md:w-[600px] md:h-[180px]"
            }`}>
              <img
                src="https://hellokostek.pl/wp-content/uploads/2021/05/logo-animation-30fps-v-2.gif"
                alt="hellokostek logo"
                referrerPolicy="no-referrer"
                className="max-w-full max-h-full object-contain mix-blend-multiply transition-all duration-500"
              />
            </div>
          </a>
        </div>

        {/* Right Side Navigation (Desktop) - CTA Button */}
        <div className="hidden md:flex items-center justify-end w-5/12">
          <a
            href={isHome ? "#kontakt-sekcja" : `${basePath}/#kontakt-sekcja`}
            className={`button button--nav ${(isHome && !isHeroScrolledPast) ? "button--secondary" : ""}`}
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
          </a>
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
          <a
            href={`${basePath}/`}
            className={`text-left text-xs uppercase tracking-widest font-mono py-2 border-l-2 pl-3 transition-colors ${
              isHome ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            hellokostek
          </a>
          
          <a
            href={`${basePath}/galeria`}
            className={`text-left text-xs uppercase tracking-widest font-mono py-2 border-l-2 pl-3 transition-colors ${
              isGallery ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            Galeria
          </a>
          
          <a
            href={`${basePath}/sklep`}
            className={`text-left text-xs uppercase tracking-widest font-mono py-2 border-l-2 pl-3 transition-colors ${
              isShop ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            Sklep
          </a>

          <a
            href={`${basePath}/o-mnie`}
            className={`text-left text-xs uppercase tracking-widest font-mono py-2 border-l-2 pl-3 transition-colors ${
              isAbout ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            O mnie
          </a>

          <a
            href={`${basePath}/kontakt`}
            className={`text-left text-xs uppercase tracking-widest font-mono py-2 border-l-2 pl-3 transition-colors ${
              isContact ? "border-[#E0115F] text-[#E0115F] font-semibold" : "border-transparent text-off-black/60"
            }`}
          >
            Kontakt
          </a>
        </div>
      )}
    </nav>
  );
}
