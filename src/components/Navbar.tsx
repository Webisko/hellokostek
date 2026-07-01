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
      <div className="max-w-[1600px] mx-auto flex items-center justify-between px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 transition-all duration-500 ease-in-out py-3 lg:py-0">
        
        {/* Left Side (Desktop: Links, Mobile/Tablet: Hamburger) */}
        <div className="w-5/12 flex items-center justify-start">
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
          
          {/* Mobile/Tablet Hamburger */}
          <div className="xl:hidden flex items-center">
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

        {/* Centered Logo (Daniel Arthury inspired) - Magnified and animated */}
        <div className="flex justify-center items-center flex-grow xl:flex-none xl:w-2/12 text-center">
          <a
            href={`${basePath}/`}
            className="flex flex-col items-center group focus:outline-none"
            aria-label="Strona główna hellokostek"
          >
            <div className={`flex items-center justify-center overflow-hidden transition-all duration-500 ease-in-out ${
              isScrolled 
                ? "w-[120px] h-[36px] sm:w-[140px] sm:h-[42px] md:w-[160px] md:h-[48px] lg:w-[200px] lg:h-[60px] xl:w-[230px] xl:h-[69px] 2xl:w-[260px] 2xl:h-[78px]" 
                : "w-[160px] h-[48px] sm:w-[200px] sm:h-[60px] md:w-[240px] md:h-[72px] lg:w-[320px] lg:h-[96px] xl:w-[450px] xl:h-[135px] 2xl:w-[600px] 2xl:h-[180px]"
            }`}>
              <img
                src={`${basePath}/images/logo-animation-30fps-v-2.gif`}
                alt="hellokostek logo"
                width={600}
                height={180}
                referrerPolicy="no-referrer"
                className="max-w-full max-h-full object-contain mix-blend-multiply transition-all duration-500"
              />
            </div>
          </a>
        </div>

        {/* Right Side (Desktop: Full CTA, Mobile/Tablet: Responsive CTA) */}
        <div className="w-5/12 flex items-center justify-end">
          {/* Desktop CTA */}
          <div className="hidden xl:flex items-center justify-end">
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
          
          {/* Mobile/Tablet Responsive CTA */}
          <div className="xl:hidden flex items-center justify-end">
            <a
              href={isHome ? "#kontakt-sekcja" : `${basePath}/#kontakt-sekcja`}
              className={`button button--nav button--sm sm:button--nav ${(isHome && !isHeroScrolledPast) ? "button--secondary" : ""} !min-w-0 !h-[36px] sm:!h-[42px] !px-3 sm:!px-6`}
            >
              <div className="button__blobs">
                <div></div>
                <div></div>
                <div></div>
              </div>
              <div className="button__text !text-[10px] sm:!text-[11px] !gap-1">
                <span className="hidden sm:inline">Zamów portret</span>
                <span className="sm:hidden">Zamów</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </div>
            </a>
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
    </nav>
  );
}
