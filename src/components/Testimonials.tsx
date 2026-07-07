import React, { useState, useEffect, useCallback, useRef } from "react";
import { ArrowLeft, ArrowRight } from "lucide-react";
import { TESTIMONIALS } from "../data";

interface TestimonialCardProps {
  testimonial: typeof TESTIMONIALS[0];
  reviewsPerPage: number;
}

function TestimonialCard({ testimonial, reviewsPerPage }: TestimonialCardProps) {
  const [isExpanded, setIsExpanded] = useState(false);
  const [isTransitioning, setIsTransitioning] = useState(false);
  const [visualExpanded, setVisualExpanded] = useState(false);
  
  const [expandedTextHeight, setExpandedTextHeight] = useState(0);
  const [collapsedTextHeight, setCollapsedTextHeight] = useState(0);

  const textContainerRef = useRef<HTMLDivElement>(null);
  const contentRef = useRef<HTMLDivElement>(null);

  const shouldTruncate = testimonial.text.length > 150;

  // Mierzymy złożoną wysokość początkową kontenera tekstu na starcie i przy zmianie szerokości okna
  useEffect(() => {
    if (contentRef.current && !isExpanded && !isTransitioning) {
      setCollapsedTextHeight(contentRef.current.clientHeight);
    }
  }, [reviewsPerPage, isExpanded, isTransitioning]);

  const handleToggle = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (!contentRef.current || !textContainerRef.current) return;

    if (!isExpanded) {
      // Pobieramy pełną wysokość tekstu przy zdjętej klasie line-clamp z tekstu
      contentRef.current.classList.remove('line-clamp-4');
      const scrollH = contentRef.current.scrollHeight;
      contentRef.current.classList.add('line-clamp-4');
      setExpandedTextHeight(scrollH);
    }
    
    setIsTransitioning(true);
    setIsExpanded(!isExpanded);
  };

  useEffect(() => {
    if (isTransitioning) {
      const timer = setTimeout(() => {
        setIsTransitioning(false);
        setVisualExpanded(isExpanded);
      }, 500);
      return () => clearTimeout(timer);
    }
  }, [isExpanded, isTransitioning]);

  // Wysokość kontenera tekstu dla animacji transition
  const textHeightStyle = isExpanded
    ? `${expandedTextHeight}px`
    : (collapsedTextHeight > 0 ? `${collapsedTextHeight}px` : undefined);

  // Klasa dla tekstu (line-clamp-4 jest nakładane wyłącznie w spoczynku złożonym)
  const textClass = shouldTruncate
    ? ((!isTransitioning && !isExpanded) ? "line-clamp-4 overflow-hidden" : "")
    : "";

  return (
    <div 
      className={`bg-white border border-gray-150 rounded-3xl p-6 sm:p-8 shadow-[0_4px_20px_rgba(0,0,0,0.01)] hover:shadow-[0_8px_30px_rgba(224,17,95,0.02)] flex flex-col justify-between w-full overflow-hidden h-auto ${
        isTransitioning || isExpanded ? "" : "h-[25em] sm:h-[26em] lg:h-[24.5em]"
      }`}
    >
      <div className="space-y-4 mb-4 flex-1 flex flex-col justify-between">
        <div>
          <div className="text-2xl mb-4">
            {testimonial.emoji}
          </div>
          
          {/* Animowany kontener tekstu */}
          <div 
            ref={textContainerRef}
            style={textHeightStyle ? { height: textHeightStyle } : {}}
            className="relative transition-[height] duration-500 ease-in-out overflow-hidden mb-4"
          >
            <div 
              ref={contentRef}
              className={textClass}
            >
              <p className="font-sans text-gray-700 italic leading-relaxed text-base sm:text-lg">
                {testimonial.text}
              </p>
            </div>
          </div>

          {/* Przycisk leżący pod tekstem */}
          {shouldTruncate && (
            <div className="pt-2">
              <button
                onClick={handleToggle}
                className="text-xs font-mono font-bold uppercase tracking-widest text-[#E0115F] hover:text-[#C4F013] transition-colors focus:outline-none cursor-pointer"
              >
                {visualExpanded ? "Zwiń ▲" : "Rozwiń ▼"}
              </button>
            </div>
          )}
        </div>
      </div>
      <div className="pt-6 border-t border-gray-100 mt-4">
        <div>
          <h4 className="font-mono text-xs uppercase tracking-wider text-gray-900 font-bold">{testimonial.author}</h4>
          <p className="text-xs font-mono text-gray-400 uppercase tracking-widest mt-1">{testimonial.meta}</p>
        </div>
      </div>
    </div>
  );
}

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
      <div className="content-container space-y-12">
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
            className={`overflow-hidden w-full py-4 px-[4px] -my-4 -mx-[4px] select-none ${dragStart !== null ? "cursor-grabbing" : "cursor-grab"}`}
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
                  className="shrink-0 px-3 md:px-4 flex items-start"
                  style={{ width: `${100 / reviewsPerPage}%` }}
                >
                  <TestimonialCard 
                    testimonial={t} 
                    reviewsPerPage={reviewsPerPage} 
                  />
                </div>
              ))}
            </div>
          </div>
        </div>

      </div>
    </section>
  );
}
