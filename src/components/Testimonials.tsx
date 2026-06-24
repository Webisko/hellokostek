import React, { useState, useEffect, useCallback } from "react";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { TESTIMONIALS } from "../data";

export default function Testimonials() {
  const [windowWidth, setWindowWidth] = useState(typeof window !== "undefined" ? window.innerWidth : 1200);
  const [reviewsIndex, setReviewsIndex] = useState(TESTIMONIALS.length);
  const [reviewsTransitionEnabled, setReviewsTransitionEnabled] = useState(true);
  const [isReviewsPaused, setIsReviewsPaused] = useState(false);

  useEffect(() => {
    const handleResize = () => setWindowWidth(window.innerWidth);
    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, []);

  const reviewsPerPage = windowWidth >= 1024 ? 3 : windowWidth >= 640 ? 2 : 1;

  useEffect(() => {
    if (!reviewsTransitionEnabled) {
      const raf = requestAnimationFrame(() => {
        setReviewsTransitionEnabled(true);
      });
      return () => cancelAnimationFrame(raf);
    }
  }, [reviewsTransitionEnabled]);

  const handleReviewsTransitionEnd = () => {
    const N = TESTIMONIALS.length;
    if (reviewsIndex >= 2 * N) {
      setReviewsTransitionEnabled(false);
      setReviewsIndex(reviewsIndex - N);
    } else if (reviewsIndex < N) {
      setReviewsTransitionEnabled(false);
      setReviewsIndex(reviewsIndex + N);
    }
  };

  const nextReviewsSlide = useCallback(() => {
    if (!reviewsTransitionEnabled) return;
    setReviewsIndex((prev) => prev + reviewsPerPage);
  }, [reviewsPerPage, reviewsTransitionEnabled]);

  const prevReviewsSlide = useCallback(() => {
    if (!reviewsTransitionEnabled) return;
    setReviewsIndex((prev) => prev - reviewsPerPage);
  }, [reviewsPerPage, reviewsTransitionEnabled]);

  useEffect(() => {
    if (isReviewsPaused) return;
    const timer = setInterval(() => {
      nextReviewsSlide();
    }, 5000);
    return () => clearInterval(timer);
  }, [isReviewsPaused, nextReviewsSlide]);

  const extendedTestimonials = [...TESTIMONIALS, ...TESTIMONIALS, ...TESTIMONIALS];

  return (
    <section className="bg-stone-50/40 border-b border-gray-100 py-24">
      <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 space-y-12">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div className="max-w-2xl space-y-3">
            <span className="font-mono text-xs text-[#E0115F] uppercase tracking-widest block font-bold">REKOMENDACJE</span>
            <h2 className="font-display text-5xl text-gray-950 font-normal">Opinie moich klientów</h2>
            <p className="font-sans text-gray-600 text-base leading-relaxed">
              Poznaj historie osób, dla których miałem przyjemność uwiecznić najważniejsze chwile na tradycyjnym płótnie malarskim.
            </p>
          </div>
          <div className="flex gap-3 shrink-0 self-start md:self-end pb-1">
            <button
              onClick={prevReviewsSlide}
              aria-label="Poprzednie opinie"
              className="w-12 h-12 rounded-full bg-white hover:bg-gray-50 text-gray-900 border border-gray-200 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-[0_0_15px_rgba(196,240,19,0.45)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E0115F] cursor-pointer"
            >
              <ArrowLeft className="w-5 h-5" />
            </button>
            <button
              onClick={nextReviewsSlide}
              aria-label="Następne opinie"
              className="w-12 h-12 rounded-full bg-white hover:bg-gray-50 text-gray-900 border border-gray-200 shadow-sm flex items-center justify-center transition-all duration-300 hover:scale-110 hover:text-[#E0115F] hover:shadow-[0_0_15px_rgba(196,240,19,0.45)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#E0115F] cursor-pointer"
            >
              <ArrowRight className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Testimonials Slider */}
        <div 
          className="relative w-full pt-4 group/reviews-slider"
          onMouseEnter={() => setIsReviewsPaused(true)}
          onMouseLeave={() => setIsReviewsPaused(false)}
          onFocus={() => setIsReviewsPaused(true)}
          onBlur={() => setIsReviewsPaused(false)}
        >
          <div className="overflow-hidden w-full py-4 px-2 -my-4 -mx-2">
            <div 
              className={`flex -mx-3 md:-mx-4 ${reviewsTransitionEnabled ? "transition-transform duration-500 ease-in-out" : "transition-none"}`}
              style={{ transform: `translateX(-${reviewsIndex * (100 / reviewsPerPage)}%)` }}
              onTransitionEnd={handleReviewsTransitionEnd}
            >
              {extendedTestimonials.map((t, idx) => (
                <div 
                  key={`${t.id}-${idx}`}
                  className="shrink-0 px-3 md:px-4 flex"
                  style={{ width: `${100 / reviewsPerPage}%` }}
                >
                  <div className="bg-white border border-gray-150 rounded-3xl p-8 space-y-6 shadow-[0_4px_20px_rgba(0,0,0,0.01)] hover:shadow-[0_8px_30px_rgba(224,17,95,0.02)] transition-all duration-300 flex flex-col justify-between w-full min-h-[220px]">
                    <div className="space-y-4">
                      <div className="flex gap-1 text-[#E0115F]">
                        {[...Array(t.stars)].map((_, i) => (
                          <span key={i} className="text-sm">★</span>
                        ))}
                      </div>
                      <p className="font-sans text-gray-700 italic leading-relaxed text-sm sm:text-base">
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
