import React, { useState, useEffect, useCallback } from "react";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { SHOP_PRODUCTS } from "../data";
import { PageId } from "../types";

interface ProductSliderProps {
  setCurrentPage: (page: PageId) => void;
  onSelectProduct: (product: any) => void;
}

export default function ProductSlider({ setCurrentPage, onSelectProduct }: ProductSliderProps) {
  const [windowWidth, setWindowWidth] = useState(typeof window !== "undefined" ? window.innerWidth : 1200);
  const [prodIndex, setProdIndex] = useState(SHOP_PRODUCTS.length);
  const [prodTransitionEnabled, setProdTransitionEnabled] = useState(true);
  const [isPaused, setIsPaused] = useState(false);

  useEffect(() => {
    const handleResize = () => setWindowWidth(window.innerWidth);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const itemsPerPage = windowWidth >= 1024 ? 4 : windowWidth >= 640 ? 2 : 1;

  useEffect(() => {
    if (!prodTransitionEnabled) {
      const raf = requestAnimationFrame(() => {
        setProdTransitionEnabled(true);
      });
      return () => cancelAnimationFrame(raf);
    }
  }, [prodTransitionEnabled]);

  const handleProdTransitionEnd = () => {
    const N = SHOP_PRODUCTS.length;
    if (prodIndex >= 2 * N) {
      setProdTransitionEnabled(false);
      setProdIndex(prodIndex - N);
    } else if (prodIndex < N) {
      setProdTransitionEnabled(false);
      setProdIndex(prodIndex + N);
    }
  };

  const nextSlide = useCallback(() => {
    if (!prodTransitionEnabled) return;
    setProdIndex((prev) => prev + itemsPerPage);
  }, [itemsPerPage, prodTransitionEnabled]);

  const prevSlide = useCallback(() => {
    if (!prodTransitionEnabled) return;
    setProdIndex((prev) => prev - itemsPerPage);
  }, [itemsPerPage, prodTransitionEnabled]);

  useEffect(() => {
    if (isPaused) return;
    const timer = setInterval(() => {
      nextSlide();
    }, 4000);
    return () => clearInterval(timer);
  }, [isPaused, nextSlide]);

  const extendedProducts = [...SHOP_PRODUCTS, ...SHOP_PRODUCTS, ...SHOP_PRODUCTS];

  return (
    <section className="py-20 md:py-28 lg:py-24 xl:py-20 2xl:py-32">
      <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 space-y-12">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="max-w-2xl space-y-4">
          <span className="font-mono text-xs text-[#E0115F] uppercase tracking-widest block font-bold">KOLEKCJA MOICH DZIEŁ</span>
          <h2 className="font-display text-5xl text-gray-950 font-normal leading-[1.1]">
            Oryginalne dzieła z duszą <br className="hidden sm:inline" /> i charakterem
          </h2>
          <p className="font-sans text-gray-600 text-base leading-relaxed">
            Nie szukasz portretu na zamówienie? Przejrzyj moje oryginalne akwarele o chłodnych tonach oraz precyzyjne rysunki ołókiem gotowe do wysyłki od zaraz.
          </p>
        </div>
        <div className="flex gap-3 shrink-0 self-start md:self-end pb-1">
          <button
            onClick={prevSlide}
            aria-label="Poprzednie produkty"
            className="w-12 h-12 rounded-full bg-white hover:bg-gray-50 text-gray-900 border border-gray-200 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-[0_0_15px_rgba(196,240,19,0.45)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E0115F] cursor-pointer"
          >
            <ArrowLeft className="w-5 h-5" />
          </button>
          <button
            onClick={nextSlide}
            aria-label="Następne produkty"
            className="w-12 h-12 rounded-full bg-white hover:bg-gray-50 text-gray-900 border border-gray-200 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-[0_0_15px_rgba(196,240,19,0.45)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E0115F] cursor-pointer"
          >
            <ArrowRight className="w-5 h-5" />
          </button>
        </div>
      </div>

      {/* Interactive gallery slider - full section width */}
      <div 
        className="relative w-full pt-4 group/slider"
        onMouseEnter={() => setIsPaused(true)}
        onMouseLeave={() => setIsPaused(false)}
        onFocus={() => setIsPaused(true)}
        onBlur={() => setIsPaused(false)}
      >
        {/* Slider Viewport */}
        <div className="overflow-hidden w-full py-4 -my-4">
          <div 
            className={`flex -mx-3 md:-mx-4 ${prodTransitionEnabled ? "transition-transform duration-500 ease-in-out" : "transition-none"}`}
            style={{ transform: `translateX(-${prodIndex * (100 / itemsPerPage)}%)` }}
            onTransitionEnd={handleProdTransitionEnd}
          >
            {extendedProducts.map((p, idx) => (
              <div 
                key={`${p.id}-${idx}`}
                className="shrink-0 px-3 md:px-4"
                style={{ width: `${100 / itemsPerPage}%` }}
              >
                <div 
                  onClick={() => onSelectProduct(p)}
                  className="group cursor-pointer overflow-hidden rounded-[24px] border border-gray-150 hover:border-lime-accent bg-gray-50 aspect-[3/4] relative shadow-sm hover:shadow-md transition-all duration-300"
                >
                  <img
                    src={p.imageUrl}
                    alt={p.title}
                    referrerPolicy="no-referrer"
                    className="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                  />
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      <div className="pt-8 text-center">
        <button
          onClick={() => {
            setCurrentPage("shop");
            window.scrollTo({ top: 0, behavior: "smooth" });
          }}
          className="button button--secondary"
        >
          <div className="button__blobs">
            <div></div>
            <div></div>
            <div></div>
          </div>
          <div className="button__text">
            Poznaj gotowe dzieła
            <ArrowRight className="w-4 h-4" />
          </div>
        </button>
      </div>
      </div>
    </section>
  );
}
