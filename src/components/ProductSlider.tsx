import React, { useState, useEffect, useCallback, useRef } from "react";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { SHOP_PRODUCTS } from "../data";

export default function ProductSlider() {
  const [prodIndex, setProdIndex] = useState(SHOP_PRODUCTS.length);
  const [prodTransitionEnabled, setProdTransitionEnabled] = useState(true);
  const [isPaused, setIsPaused] = useState(false);

  const [dragStart, setDragStart] = useState<number | null>(null);
  const [draggedDistance, setDraggedDistance] = useState(0);
  const [isDragging, setIsDragging] = useState(false);

  const containerRef = useRef<HTMLDivElement>(null);

  const handleDragStart = (clientX: number) => {
    setDragStart(clientX);
    setDraggedDistance(0);
    setIsDragging(false);
  };

  const handleDragMove = (clientX: number) => {
    if (dragStart === null) return;
    const distance = dragStart - clientX;
    setDraggedDistance(distance);
    if (Math.abs(distance) > 10) {
      setIsDragging(true);
    }
  };

  const handleDragEnd = () => {
    if (dragStart === null) return;
    if (draggedDistance > 50) {
      nextSlide();
    } else if (draggedDistance < -50) {
      prevSlide();
    }
    setDragStart(null);
    setDraggedDistance(0);
    setTimeout(() => {
      setIsDragging(false);
    }, 50);
  };

  const itemsPerPage = 1;

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
    setProdIndex((prev) => prev + 1);
  }, [prodTransitionEnabled]);

  const prevSlide = useCallback(() => {
    if (!prodTransitionEnabled) return;
    setProdIndex((prev) => prev - 1);
  }, [prodTransitionEnabled]);

  useEffect(() => {
    if (isPaused) return;
    const timer = setInterval(() => {
      nextSlide();
    }, 4000);
    return () => clearInterval(timer);
  }, [isPaused, nextSlide]);

  const extendedProducts = [...SHOP_PRODUCTS, ...SHOP_PRODUCTS, ...SHOP_PRODUCTS];
  const basePath = "/hellokostek";

  const containerWidth = containerRef.current ? containerRef.current.clientWidth : 1;
  const dragPercent = dragStart !== null ? (draggedDistance / containerWidth) * 100 : 0;
  const finalTranslate = (prodIndex * (100 / itemsPerPage)) + dragPercent;

  return (
    <section className="py-20 md:py-28 lg:py-24 xl:py-20 2xl:py-32">
      <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 space-y-12">
        <div className="flex flex-col items-center text-center max-w-4xl mx-auto">
          <div className="space-y-4 w-full max-w-3xl">
            <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block">
              Prace autorskie
            </span>
            <h2 className="font-display text-4xl sm:text-5xl text-gray-900 tracking-tight font-normal md:whitespace-nowrap">
              Nie tylko portrety olejne
            </h2>
            <p className="font-sans text-gray-600 text-base leading-relaxed max-w-[530px] mx-auto">
              Zajrzyj do sklepu internetowego hellokostek, gdzie oryginalne, rysowane ołówkiem i malowane akwarelą dzieła kupisz od ręki.
            </p>
          </div>
        </div>

        {/* Navigation Arrows - placed close to the slider, far from the header */}
        <div className="flex gap-3 justify-center mt-10 mb-10">
          <button
            onClick={prevSlide}
            aria-label="Poprzednie produkty"
            className="w-12 h-12 rounded-full bg-white hover:bg-gray-55 text-gray-900 border border-gray-200 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E0115F] cursor-pointer"
          >
            <ArrowLeft className="w-5 h-5" />
          </button>
          <button
            onClick={nextSlide}
            aria-label="Następne produkty"
            className="w-12 h-12 rounded-full bg-white hover:bg-gray-55 text-gray-900 border border-gray-200 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E0115F] cursor-pointer"
          >
            <ArrowRight className="w-5 h-5" />
          </button>
        </div>

        {/* Interactive gallery slider - full section width */}
        <div 
          className="relative w-full pt-0 group/slider"
          onMouseEnter={() => setIsPaused(true)}
          onMouseLeave={() => {
            setIsPaused(false);
            handleDragEnd();
          }}
          onFocus={() => setIsPaused(true)}
          onBlur={() => setIsPaused(false)}
        >
          {/* Slider Viewport */}
          <div 
            ref={containerRef}
            className={`overflow-hidden w-full py-4 -my-4 select-none ${dragStart !== null ? "cursor-grabbing" : "cursor-grab"}`}
            onTouchStart={(e) => handleDragStart(e.touches[0].clientX)}
            onTouchMove={(e) => handleDragMove(e.touches[0].clientX)}
            onTouchEnd={handleDragEnd}
            onMouseDown={(e) => handleDragStart(e.clientX)}
            onMouseMove={(e) => handleDragMove(e.clientX)}
            onMouseUp={handleDragEnd}
          >
            <div 
              className={`flex -mx-3 md:-mx-4 ${
                dragStart !== null 
                  ? "transition-none" 
                  : prodTransitionEnabled 
                    ? "transition-transform duration-500 ease-in-out" 
                    : "transition-none"
              }`}
              style={{ transform: `translateX(-${finalTranslate}%)` }}
              onTransitionEnd={handleProdTransitionEnd}
            >
              {extendedProducts.map((p, idx) => (
                <div 
                  key={`${p.id}-${idx}`}
                  className="shrink-0 px-3 md:px-4 animate-fadeIn"
                  style={{ width: `${100 / itemsPerPage}%` }}
                >
                  <a 
                    href={`${basePath}/sklep/${p.id}`}
                    onDragStart={(e) => e.preventDefault()}
                    onClick={(e) => {
                      if (isDragging) {
                        e.preventDefault();
                      }
                    }}
                    className="group cursor-pointer relative flex items-center justify-center mx-auto h-[280px] sm:h-[400px] md:h-[450px] lg:h-[550px] xl:h-[680px] w-fit max-w-full bg-white border border-gray-55 hover:border-gray-200 p-4 rounded-[28px] transition-all duration-500 hover:shadow-lg block"
                  >
                    <div className="relative rounded-2xl overflow-hidden bg-gray-50 border border-gray-100 h-full w-fit max-w-full flex items-center justify-center">
                      <img
                        src={p.imageUrl}
                        alt={p.title}
                        referrerPolicy="no-referrer"
                        draggable="false"
                        className="h-full max-h-full w-auto max-w-full block object-contain transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                      />
                    </div>
                  </a>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="pt-8 text-center">
          <a
            href={`${basePath}/sklep`}
            className="button"
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
          </a>
        </div>
      </div>
    </section>
  );
}
