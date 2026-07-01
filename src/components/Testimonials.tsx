import React, { useState, useEffect, useCallback, useRef } from "react";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { TESTIMONIALS } from "../data";

export default function Testimonials() {
  const [windowWidth, setWindowWidth] = useState(1200);
  const [reviewsIndex, setReviewsIndex] = useState(TESTIMONIALS.length * 2);
  const [reviewsTransitionEnabled, setReviewsTransitionEnabled] = useState(true);
  const [isReviewsPaused, setIsReviewsPaused] = useState(false);
  const [isTransitioning, setIsTransitioning] = useState(false);

  const [dragStart, setDragStart] = useState<number | null>(null);
  const [draggedDistance, setDraggedDistance] = useState(0);

  const containerRef = useRef<HTMLDivElement>(null);

  const handleDragStart = (clientX: number) => {
    if (isTransitioning) return;
    setDragStart(clientX);
    setDraggedDistance(0);
  };

  const handleDragMove = (clientX: number) => {
    if (dragStart === null) return;
    const distance = dragStart - clientX;
    setDraggedDistance(distance);
  };

  const handleDragEnd = () => {
    if (dragStart === null) return;
    if (draggedDistance > 50) {
      nextReviewsSlide();
    } else if (draggedDistance < -50) {
      prevReviewsSlide();
    }
    setDragStart(null);
    setDraggedDistance(0);
  };

  useEffect(() => {
    setWindowWidth(window.innerWidth);
    const handleResize = () => setWindowWidth(window.innerWidth);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const reviewsPerPage = windowWidth >= 1280 ? 3 : windowWidth >= 768 ? 2 : 1;

  useEffect(() => {
    if (!reviewsTransitionEnabled) {
      const raf = requestAnimationFrame(() => {
        setReviewsTransitionEnabled(true);
        setIsTransitioning(false);
      });
      return () => cancelAnimationFrame(raf);
    }
  }, [reviewsTransitionEnabled]);

  const handleReviewsTransitionEnd = () => {
    const N = TESTIMONIALS.length;
    let didWrap = false;

    if (reviewsIndex >= 3 * N) {
      setReviewsTransitionEnabled(false);
      setReviewsIndex(reviewsIndex - 2 * N);
      didWrap = true;
    } else if (reviewsIndex < N) {
      setReviewsTransitionEnabled(false);
      setReviewsIndex(reviewsIndex + 2 * N);
      didWrap = true;
    }

    if (!didWrap) {
      setIsTransitioning(false);
    }
  };

  const nextReviewsSlide = useCallback(() => {
    if (isTransitioning) return;
    setIsTransitioning(true);
    setReviewsIndex((prev) => prev + reviewsPerPage);
  }, [reviewsPerPage, isTransitioning]);

  const prevReviewsSlide = useCallback(() => {
    if (isTransitioning) return;
    setIsTransitioning(true);
    setReviewsIndex((prev) => prev - reviewsPerPage);
  }, [reviewsPerPage, isTransitioning]);

  useEffect(() => {
    if (isReviewsPaused) return;
    const timer = setInterval(() => {
      nextReviewsSlide();
    }, 5000);
    return () => clearInterval(timer);
  }, [isReviewsPaused, nextReviewsSlide]);

  const extendedTestimonials = [
    ...TESTIMONIALS,
    ...TESTIMONIALS,
    ...TESTIMONIALS,
    ...TESTIMONIALS,
  ];

  const containerWidth = containerRef.current ? containerRef.current.clientWidth : 1;
  const dragPercent = dragStart !== null ? (draggedDistance / containerWidth) * 100 : 0;
  const finalTranslate = (reviewsIndex * (100 / reviewsPerPage)) + dragPercent;

  return (
    <section className="bg-stone-50 border-y border-gray-100 py-24">
      <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 space-y-12">
        <div className="flex flex-col items-center text-center max-w-4xl mx-auto">
          <div className="space-y-3 w-full max-w-3xl mx-auto">
            <span className="font-mono text-xs text-[#E0115F] uppercase tracking-widest block font-bold">REKOMENDACJE</span>
            <h2 className="font-display text-5xl text-gray-950 font-normal md:whitespace-nowrap">Opinie moich klientów</h2>
            <p className="font-sans text-gray-600 text-base leading-relaxed">
              Poznaj historie osób, dla których miałem przyjemność namalować obraz.
            </p>
          </div>
        </div>

        {/* Navigation Arrows - placed close to the reviews, far from the header */}
        <div className="flex gap-3 justify-center mt-10 mb-10">
          <button
            onClick={prevReviewsSlide}
            aria-label="Poprzednie opinie"
            className="w-12 h-12 rounded-full bg-white hover:bg-gray-55 text-gray-900 border border-gray-200 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E0115F] cursor-pointer"
          >
            <ArrowLeft className="w-5 h-5" />
          </button>
          <button
            onClick={nextReviewsSlide}
            aria-label="Następne opinie"
            className="w-12 h-12 rounded-full bg-white hover:bg-gray-55 text-gray-900 border border-gray-200 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E0115F] cursor-pointer"
          >
            <ArrowRight className="w-5 h-5" />
          </button>
        </div>

        {/* Testimonials Slider */}
        <div 
          className="relative w-full pt-0 group/reviews-slider"
          onMouseEnter={() => setIsReviewsPaused(true)}
          onMouseLeave={() => {
            setIsReviewsPaused(false);
            handleDragEnd();
          }}
          onFocus={() => setIsReviewsPaused(true)}
          onBlur={() => setIsReviewsPaused(false)}
        >
          <div 
            ref={containerRef}
            className={`overflow-hidden w-full py-4 px-2 -my-4 -mx-2 select-none ${dragStart !== null ? "cursor-grabbing" : "cursor-grab"}`}
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
                  : reviewsTransitionEnabled 
                    ? "transition-transform duration-500 ease-in-out" 
                    : "transition-none"
              }`}
              style={{ transform: `translateX(-${finalTranslate}%)` }}
              onTransitionEnd={handleReviewsTransitionEnd}
            >
              {extendedTestimonials.map((t, idx) => (
                <div 
                  key={`${t.id}-${idx}`}
                  className="shrink-0 px-3 md:px-4 flex"
                  style={{ width: `${100 / reviewsPerPage}%` }}
                >
                  <div className="bg-white border border-gray-150 rounded-3xl p-6 sm:p-8 space-y-6 shadow-[0_4px_20px_rgba(0,0,0,0.01)] hover:shadow-[0_8px_30px_rgba(224,17,95,0.02)] transition-all duration-300 flex flex-col justify-between w-full min-h-0 sm:min-h-[220px]">
                    <div className="space-y-4">
                      <div className="text-2xl">
                        {t.emoji}
                      </div>
                      <p className="font-sans text-gray-700 italic leading-relaxed text-base sm:text-lg">
                        {t.text}
                      </p>
                    </div>
                    <div className="pt-6 border-t border-gray-100">
                      <div>
                        <h4 className="font-mono text-xs uppercase tracking-wider text-gray-900 font-bold">{t.author}</h4>
                        <p className="text-xs font-mono text-gray-400 uppercase tracking-widest mt-1">{t.meta}</p>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

      </div>
    </section>
  );
}
