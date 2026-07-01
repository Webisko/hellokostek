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
      <div className="max-w-[1600px] mx-auto flex items-center justify-between pl-0 pr-6 sm:px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 transition-all duration-500 ease-in-out py-3 lg:py-0 relative">
        
        {/* Left Side (Desktop: Links, Tablet: Hamburger, Mobile: Hidden) */}
        <div className="!hidden sm:!flex sm:absolute sm:left-6 sm:top-1/2 sm:-translate-y-1/2 sm:items-center sm:justify-start sm:z-10 xl:relative xl:left-auto xl:top-auto xl:translate-y-0 xl:w-5/12">
          {/* Desktop Links */}
          <div className="hidden xl:flex items-center space-x-4 xl:space-x-6">
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
          
          {/* Tablet Hamburger */}
          <div className="hidden sm:flex xl:hidden items-center">
            <button
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="p-2 sm:p-2 md:p-2.5 text-off-black bg-stone-100 hover:bg-stone-200 transition-colors rounded-full"
              aria-label="Główne Menu"
            >
              {isMobileMenuOpen ? (
                <X className="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7" />
              ) : (
                <Menu className="w-5 h-5 sm:w-6 sm:h-6 md:w-7 md:h-7" />
              )}
            </button>
          </div>
        </div>

        {/* Logo (Left-aligned on mobile, centered on tablet/desktop) */}
        <div className="flex justify-start sm:justify-center items-center flex-grow sm:flex-none xl:w-2/12 text-left sm:text-center mx-0 sm:mx-auto">
          <a
            href={`${basePath}/`}
            className="flex flex-col items-start sm:items-center group focus:outline-none"
            aria-label="Strona główna hellokostek"
          >
            <div className={`flex items-center justify-start sm:justify-center overflow-hidden transition-all duration-500 ease-in-out ${
              isScrolled 
                ? "w-[130px] h-[39px] sm:w-[140px] sm:h-[42px] md:w-[160px] md:h-[48px] lg:w-[200px] lg:h-[60px] xl:w-[230px] xl:h-[69px] 2xl:w-[260px] 2xl:h-[78px]" 
                : "w-[240px] h-[72px] sm:w-[200px] sm:h-[60px] md:w-[240px] md:h-[72px] lg:w-[320px] lg:h-[96px] xl:w-[450px] xl:h-[135px] 2xl:w-[600px] 2xl:h-[180px]"
            }`}>
              <img
                src={`${basePath}/images/logo-animation-30fps-v-2.gif`}
                alt="hellokostek logo"
                width={600}
                height={180}
                referrerPolicy="no-referrer"
                className="max-w-full max-h-full object-contain mix-blend-multiply transition-all duration-500 -ml-[15%] sm:ml-0"
              />
            </div>
          </a>
        </div>

        {/* Right Side (Desktop/Tablet: CTA, Mobile: Hamburger) */}
        <div className="flex items-center justify-end sm:absolute sm:right-6 sm:top-1/2 sm:-translate-y-1/2 sm:z-10 xl:relative xl:right-auto xl:top-auto xl:translate-y-0 xl:w-5/12">
          {/* Desktop/Tablet CTA */}
          <div className="hidden sm:flex items-center justify-end">
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
          
          {/* Mobile Hamburger */}
          <div className="sm:hidden flex items-center">
            <button
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="p-2 text-off-black bg-stone-100 hover:bg-stone-200 transition-colors rounded-full"
              aria-label="Główne Menu"
            >
              {isMobileMenuOpen ? (
                <X className="w-5 h-5" />
              ) : (
                <Menu className="w-5 h-5" />
              )}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Menu panel */}
      <div 
        className={`xl:hidden flex flex-col px-6 transition-all duration-300 ease-in-out overflow-hidden border-neutral-100 ${
          isMobileMenuOpen 
            ? "max-h-[400px] opacity-100 mt-4 pt-4 pb-4 space-y-4 border-t" 
            : "max-h-0 opacity-0 mt-0 pt-0 pb-0 space-y-0 border-t-0 pointer-events-none"
        }`}
      >
        <a
          href={`${basePath}/`}
          className={`text-center text-xs uppercase tracking-widest font-mono py-2 transition-colors ${
            isHome ? "text-[#E0115F] font-semibold" : "text-off-black/60"
          }`}
        >
          hellokostek
        </a>
        
        <a
          href={`${basePath}/galeria`}
          className={`text-center text-xs uppercase tracking-widest font-mono py-2 transition-colors ${
            isGallery ? "text-[#E0115F] font-semibold" : "text-off-black/60"
          }`}
        >
          Galeria
        </a>
        
        <a
          href={`${basePath}/sklep`}
          className={`text-center text-xs uppercase tracking-widest font-mono py-2 transition-colors ${
            isShop ? "text-[#E0115F] font-semibold" : "text-off-black/60"
          }`}
        >
          Sklep
        </a>

        <a
          href={`${basePath}/o-mnie`}
          className={`text-center text-xs uppercase tracking-widest font-mono py-2 transition-colors ${
            isAbout ? "text-[#E0115F] font-semibold" : "text-off-black/60"
          }`}
        >
          O mnie
        </a>

        <a
          href={`${basePath}/kontakt`}
          className={`text-center text-xs uppercase tracking-widest font-mono py-2 transition-colors ${
            isContact ? "text-[#E0115F] font-semibold" : "text-off-black/60"
          }`}
        >
          Kontakt
        </a>
        <div className="pt-4 pb-2 sm:hidden flex justify-center">
          <a
            href={isHome ? "#kontakt-sekcja" : `${basePath}/#kontakt-sekcja`}
            onClick={() => setIsMobileMenuOpen(false)}
            className="button button--sm text-center"
          >
            <div className="button__blobs">
              <div></div>
              <div></div>
              <div></div>
            </div>
            <div className="button__text font-bold">
              Zamów portret
              <ArrowRight className="w-4 h-4" />
            </div>
          </a>
        </div>
      </div>
    </nav>
  );
}
