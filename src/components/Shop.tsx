import React, { useState, useEffect, useMemo } from "react";
import { createPortal } from "react-dom";
import { SHOP_PRODUCTS } from "../data";
import type { Product } from "../types";
import { parseLocalizedString } from "../utils/localization";
import { ArrowRight, X, SlidersHorizontal } from "lucide-react";

function normalizeProductCategory(rawSlug?: string, rawName?: string): { slug: string; label?: string } {
  const normalizedSlug = (rawSlug || "").trim().toLowerCase();
  const parsedName = rawName ? parseLocalizedString(rawName) : "";

  if (normalizedSlug === "akwarela" || normalizedSlug === "watercolor") {
    return { slug: "akwarela", label: parsedName || "Akwarela" };
  }
  if (normalizedSlug === "rysunek" || normalizedSlug === "drawing") {
    return { slug: "rysunek", label: parsedName || "Rysunek" };
  }
  if (normalizedSlug !== "") {
    return { slug: normalizedSlug, label: parsedName || rawSlug };
  }

  return { slug: "akwarela", label: parsedName || "Akwarela" };
}

function mapProduct(apiProduct: any): Product {
  const originalVariant = apiProduct.variants?.find((v: any) => v.sku?.endsWith('-OR'));
  const printVariant = apiProduct.variants?.find((v: any) => v.sku?.endsWith('-PR'));

  const firstCat = apiProduct.categories?.[0];
  const { slug: category, label: categoryLabel } = normalizeProductCategory(firstCat?.slug, firstCat?.name);

  const imageUrl = apiProduct.featured_image_url || '/images/placeholder.png';
  const rawDescription = apiProduct.description !== undefined && apiProduct.description !== null && String(parseLocalizedString(apiProduct.description)).trim() !== ""
    ? parseLocalizedString(apiProduct.description)
    : parseLocalizedString(apiProduct.short_description);
  const cleanDescription = typeof rawDescription === "string"
    ? rawDescription.replace(/<[^>]*>/g, "").trim()
    : "";

  const originalActiveGrosze = originalVariant
    ? (originalVariant.current_price_amount ?? originalVariant.sale_price_amount ?? originalVariant.regular_price_amount)
    : (apiProduct.current_price_amount ?? apiProduct.sale_price_amount ?? apiProduct.regular_price_amount);
  const originalRegularGrosze = originalVariant
    ? originalVariant.regular_price_amount
    : apiProduct.regular_price_amount;

  const defaultPrintGrosze = category === 'akwarela' ? 3000 : 2000;
  const printActiveGrosze = printVariant
    ? (printVariant.current_price_amount ?? printVariant.sale_price_amount ?? printVariant.regular_price_amount)
    : defaultPrintGrosze;
  const printRegularGrosze = printVariant ? printVariant.regular_price_amount : defaultPrintGrosze;

  return {
    id: apiProduct.slug,
    title: parseLocalizedString(apiProduct.name),
    year: apiProduct.metadata?.year || "2022",
    category,
    categoryLabel,
    originalPrice: originalActiveGrosze / 100,
    originalRegularPrice: originalRegularGrosze ? originalRegularGrosze / 100 : undefined,
    printPrice: printActiveGrosze / 100,
    printRegularPrice: printRegularGrosze ? printRegularGrosze / 100 : undefined,
    isOriginalAvailable: originalVariant ? (originalVariant.is_active !== false && originalVariant.stock_quantity > 0) : false,
    imageUrl,
    description: cleanDescription,
    originalVariantId: originalVariant?.id,
    printVariantId: printVariant?.id,
    technique: parseLocalizedString(apiProduct.metadata?.technique) || undefined,
    format: parseLocalizedString(apiProduct.metadata?.format) || undefined,
  };
}

export default function Shop() {
  const [products, setProducts] = useState<Product[]>(SHOP_PRODUCTS);
  const [activeCategory, setActiveCategory] = useState<string>("all");
  const [isFilterDrawerOpen, setIsFilterDrawerOpen] = useState(false);
  const [isMounted, setIsMounted] = useState(false);

  const categoryFilters = useMemo(() => {
    const map = new Map<string, string>();
    products.forEach((p) => {
      const slug = (p.category || "").toLowerCase();
      if (!slug || slug === "custom_portrait") {
        return;
      }
      if (!map.has(slug)) {
        if (slug === "akwarela") {
          map.set(slug, "Oryginały (akwarele)");
        } else if (slug === "rysunek") {
          map.set(slug, "Oryginały (rysunki)");
        } else {
          map.set(slug, p.categoryLabel || slug);
        }
      }
    });

    const list: { id: string; label: string }[] = [{ id: "all", label: "Wszystkie prace" }];
    map.forEach((label, id) => {
      list.push({ id, label });
    });
    list.push({ id: "prints", label: "Wydruki kolekcjonerskie" });

    return list;
  }, [products]);

  useEffect(() => {
    setIsMounted(true);
    
    // Fetch products dynamically from backend API
    const apiBase = import.meta.env.PUBLIC_API_URL || "https://panel.hellokostek.pl/api";
    fetch(`${apiBase}/catalog`)
      .then((res) => {
        if (!res.ok) throw new Error("API response error");
        return res.json();
      })
      .then((data) => {
        const payload = data.data || data;
        const items = Array.isArray(payload) ? payload : (payload.products || []);
        if (Array.isArray(items)) {
          const mapped = items.map(mapProduct);
          setProducts(mapped);
        }
      })
      .catch((err) => {
        console.warn("Could not fetch products from API, falling back to static data:", err);
      });
  }, []);

  useEffect(() => {
    if (isFilterDrawerOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [isFilterDrawerOpen]);

  const filteredProducts = products.filter((product) => {
    if (activeCategory === "prints") {
      return Boolean(product.printPrice);
    }
    if (activeCategory !== "all" && (product.category || "").toLowerCase() !== activeCategory) {
      return false;
    }

    return true;
  });

  const basePath = "";

  return (
    <div className="animate-fadeIn pt-5 sm:pt-8 md:pt-14 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 content-container space-y-6 md:space-y-12 xl:space-y-16 font-sans">
      {/* Page Title & SEO Introductory block */}
      <header className="pb-2 md:pb-6 xl:pb-12 max-w-none space-y-3 sm:space-y-4">
        <span className="font-mono text-[10px] sm:text-xs tracking-wider sm:tracking-widest uppercase text-[#E0115F] font-semibold block">
          SELEKCJA DZIEŁ SZTUKI • ORYGINAŁY I LIMITOWANE WYDRUKI
        </span>
        <h1 className="font-display text-4xl sm:text-6xl text-gray-900 tracking-tight font-normal">
          Galeria dzieł oryginalnych
        </h1>
        <p className="text-gray-600 text-base sm:text-lg max-w-4xl leading-relaxed">
          Zbiór autentycznych obrazów olejnych, akwareli oraz rysunków ołówkiem powstałych w mojej pracowni. Każda kompozycja to fizyczne, gotowe dzieło stworzone z zachowaniem klasycznego warsztatu. Wybierz unikatowy oryginał, który zdefiniuje Twoje wnętrze, lub certyfikowany wydruk kolekcjonerski z limitowanej edycji.
        </p>
      </header>

      {/* Mobile/Tablet Filter Trigger (Sticky, floating, transparent) */}
      <div className="sticky top-[48px] sm:top-[58px] md:top-[68px] xl:hidden z-30 flex justify-between items-center w-full py-2.5 sm:py-3 pointer-events-none">
        <button
          onClick={() => setIsFilterDrawerOpen(true)}
          className="pointer-events-auto flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-200 bg-white text-gray-800 text-xs font-semibold uppercase tracking-wider hover:bg-gray-55 hover:border-gray-300 transition-all cursor-pointer shadow-md active:scale-[0.98]"
        >
          <SlidersHorizontal className="w-4 h-4 text-[#E0115F]" />
          Filtruj sklep
          {activeCategory !== "all" && (
            <span className="ml-1 px-1.5 py-0.5 rounded-full bg-[#E0115F] text-white text-[10px] font-bold">
              1
            </span>
          )}
        </button>

        {/* Active filters clear */}
        {activeCategory !== "all" && (
          <button
            onClick={() => {
              setActiveCategory("all");
            }}
            className="pointer-events-auto text-xs text-gray-700 hover:text-[#E0115F] font-semibold transition-colors cursor-pointer bg-white px-4 py-2.5 rounded-xl border border-gray-200 shadow-sm"
          >
            Wyczyść filtry
          </button>
        )}
      </div>

      {/* Separator Line (Static, mobile/tablet only) */}
      <div className="xl:hidden border-b border-gray-150 pb-2" />

      {/* Desktop Filter Rail (Static, desktop only) */}
      <div className="hidden xl:flex xl:flex-row items-center justify-between gap-6 border-b border-gray-150 pb-6">
        <div className="flex flex-wrap items-center justify-start gap-3 w-auto">
          {categoryFilters.map((cat) => (
            <button
              key={cat.id}
              onClick={() => setActiveCategory(cat.id)}
              className={`px-4 py-2.5 rounded-lg text-xs font-semibold tracking-wider transition-all uppercase cursor-pointer whitespace-nowrap ${
                activeCategory === cat.id
                  ? "bg-gray-950 text-white shadow-sm"
                  : "bg-gray-50 text-gray-600 border border-transparent hover:border-gray-200 hover:bg-white"
              }`}
            >
              {cat.label}
            </button>
          ))}
        </div>
      </div>

      {/* Asymmetric Elegant Art Grid of Products */}
      {filteredProducts.length === 0 ? (
        <div className="text-center py-24 bg-gray-50 rounded-2xl border border-dashed border-gray-200 text-gray-500 text-sm">
          Brak prac w wybranej kategorii.
        </div>
      ) : (
        <>
          <div className="columns-1 md:columns-2 lg:columns-3 gap-6 md:gap-8 lg:gap-10 xl:gap-16 pt-2 md:pt-4 xl:pt-6 [column-fill:_balance]">
            {filteredProducts.map((product, index) => {
              const printPriceVal = product.printPrice || (product.category === "akwarela" ? 30 : 20);
              const categoryText = (
                product.categoryLabel ||
                (product.category === "akwarela"
                  ? "Akwarela"
                  : product.category === "rysunek"
                    ? "Rysunek"
                    : product.category)
              ).toUpperCase();
              const badgeLabel = product.isOriginalAvailable
                ? `${categoryText} / WYDRUK`
                : "WYDRUK";

              return (
                <a
                  key={product.id}
                  href={`${basePath}/sklep/${product.id}`}
                  className="break-inside-avoid mb-6 md:mb-8 lg:mb-10 xl:mb-16 group flex flex-col justify-between space-y-4 cursor-pointer transition-all duration-500 bg-white border border-gray-55 hover:border-gray-200 p-4 rounded-[28px] hover:shadow-lg block"
                >
                  {/* Image Wrapper */}
                  <div className="relative rounded-2xl overflow-hidden bg-gray-50 border border-gray-100 w-full">
                    <img
                      src={product.imageUrl}
                      alt={product.title}
                      loading={index < 3 ? "eager" : "lazy"}
                      decoding="async"
                      referrerPolicy="no-referrer"
                      className="w-full h-auto block transition-transform duration-700 ease-out group-hover:scale-105"
                    />
                    
                    {/* Category Stamp */}
                    <span className="absolute top-4 left-4 bg-white/90 backdrop-blur-md text-gray-905 text-xs font-mono font-bold px-3 py-1.5 rounded-lg uppercase tracking-widest border border-gray-100 shadow-xs">
                      {badgeLabel}
                    </span>

                    {/* Year Tag */}
                    <span className="absolute bottom-4 right-4 bg-gray-950 text-white text-xs font-mono px-3 py-1.5 rounded-lg font-medium tracking-widest uppercase">
                      {product.year}
                    </span>
                  </div>

                  {/* Info block */}
                  <div className="space-y-2 px-1">
                    <div className="flex flex-col gap-1">
                      <h2 className="font-display text-lg sm:text-xl text-gray-900 group-hover:text-[#E0115F] transition-colors leading-snug">
                        {product.title}
                      </h2>
                      <div className="font-mono text-xs sm:text-sm text-[#E0115F] font-bold flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                        {product.isOriginalAvailable ? (
                          <>
                            <span>
                              Oryginał: {product.originalPrice} zł
                              {product.originalRegularPrice && product.originalRegularPrice > product.originalPrice && (
                                <span className="ml-1.5 text-gray-600 line-through font-normal text-xs">
                                  {product.originalRegularPrice} zł
                                </span>
                              )}
                            </span>
                            <span aria-hidden="true">•</span>
                            <span>
                              Wydruk: {printPriceVal} zł
                              {product.printRegularPrice && product.printRegularPrice > printPriceVal && (
                                <span className="ml-1.5 text-gray-600 line-through font-normal text-xs">
                                  {product.printRegularPrice} zł
                                </span>
                              )}
                            </span>
                          </>
                        ) : (
                          <span>
                            Wydruk: {printPriceVal} zł
                            {product.printRegularPrice && product.printRegularPrice > printPriceVal && (
                              <span className="ml-1.5 text-gray-600 line-through font-normal text-xs">
                                {product.printRegularPrice} zł
                              </span>
                            )}{" "}
                            <span className="text-gray-500 font-normal">(Oryginał sprzedany)</span>
                          </span>
                        )}
                      </div>
                    </div>
                    
                    {product.description && (
                      <p className="text-sm text-gray-500 line-clamp-2 leading-relaxed">
                        {product.description}
                      </p>
                    )}

                    <div className="inline-flex items-center gap-1.5 pt-2 text-xs font-mono font-bold uppercase tracking-widest text-gray-600 group-hover:text-[#E0115F] transition-colors">
                      Szczegóły 
                      <ArrowRight className="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform" />
                    </div>
                  </div>
                </a>
              );
            })}
          </div>

          {/* Informacja o wariantach prac */}
          <div className="bg-stone-50/80 border border-stone-200/80 rounded-2xl p-6 text-center space-y-2">
            <p className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-bold">
              Informacja o wariantach prac
            </p>
            <p className="text-sm sm:text-base text-gray-700 leading-relaxed">
              Każda praca dostępna jest na karcie produktu w wersji <strong className="text-gray-950">oryginalnej (unikat 1/1)</strong> lub jako <strong className="text-gray-950">sygnowany wydruk kolekcjonerski</strong> na papierze archiwalnym.
            </p>
          </div>
        </>
      )}
      {/* Filter Drawer (Off-Canvas Menu) */}
      {isMounted && createPortal(
        <div
          inert={!isFilterDrawerOpen || undefined}
          aria-hidden={!isFilterDrawerOpen}
          className={`fixed inset-0 z-50 overflow-hidden transition-all duration-300 ${isFilterDrawerOpen ? "pointer-events-auto" : "pointer-events-none"}`}
        >
          {/* Backdrop */}
          <div 
            className={`absolute inset-0 bg-black transition-opacity duration-300 ${
              isFilterDrawerOpen ? "opacity-50" : "opacity-0"
            }`} 
            onClick={() => setIsFilterDrawerOpen(false)}
          />
          
          {/* Sliding Panel */}
          <div className="absolute inset-y-0 left-0 max-w-full flex">
            <div className={`w-screen max-w-md bg-white text-gray-905 shadow-2xl flex flex-col justify-between h-full transform transition-transform duration-300 ease-in-out ${
              isFilterDrawerOpen ? "translate-x-0" : "-translate-x-full"
            }`}>
              {/* Header */}
              <div className="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <h2 className="text-lg font-display font-semibold text-gray-950">Filtrowanie sklepu</h2>
                <button 
                  onClick={() => setIsFilterDrawerOpen(false)}
                  className="p-2 -mr-2 text-gray-400 hover:text-gray-900 rounded-full hover:bg-gray-100 transition-colors cursor-pointer"
                  aria-label="Zamknij filtry"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {/* Content (Scrollable) */}
              <div className="flex-1 overflow-y-auto px-6 py-6 space-y-8">
                {/* Category filter group */}
                <div className="space-y-4">
                  <h3 className="text-xs font-mono uppercase tracking-widest text-[#E0115F] font-bold">Kategoria prac</h3>
                  <div className="flex flex-col gap-2">
                    {categoryFilters.map((cat) => (
                      <button
                        key={cat.id}
                        onClick={() => setActiveCategory(cat.id)}
                        className={`w-full text-left px-4 py-3.5 rounded-xl text-xs font-semibold tracking-wider transition-all uppercase cursor-pointer flex justify-between items-center ${
                          activeCategory === cat.id
                            ? "bg-gray-950 text-white"
                            : "bg-gray-55 text-gray-600 border border-transparent hover:border-gray-200 hover:bg-white"
                        }`}
                      >
                        {cat.label}
                        {activeCategory === cat.id && <span className="w-1.5 h-1.5 rounded-full bg-[#C4F013]" />}
                      </button>
                    ))}
                  </div>
                </div>
              </div>

              {/* Footer */}
              <div className="px-6 py-5 border-t border-gray-100 bg-gray-50 flex gap-4">
                {activeCategory !== "all" && (
                  <button
                    onClick={() => {
                      setActiveCategory("all");
                    }}
                    className="flex-1 px-4 py-3.5 rounded-xl border border-gray-200 text-gray-550 bg-white text-xs font-semibold uppercase tracking-wider hover:bg-gray-100 hover:text-gray-950 transition-all cursor-pointer"
                  >
                    Reset
                  </button>
                )}
                <button
                  onClick={() => setIsFilterDrawerOpen(false)}
                  className="flex-1 bg-gray-950 text-white px-4 py-3.5 rounded-xl text-xs font-semibold uppercase tracking-wider hover:bg-gray-900 transition-all cursor-pointer text-center font-bold"
                >
                  Pokaż ({filteredProducts.length})
                </button>
              </div>
            </div>
          </div>
        </div>,
        document.body
      )}

      {/* Sekcja CTA na dole */}
      <section className="bg-stone-50 rounded-[32px] p-8 sm:p-12 border border-neutral-100 flex flex-col md:flex-row items-center justify-between gap-8 mt-24">
        <div className="space-y-2 text-center md:text-left max-w-2xl">
          <h2 className="font-display text-3xl text-gray-900 font-normal">
            Szukasz czegoś wyjątkowego?
          </h2>
          <p className="text-stone-600 text-sm sm:text-base leading-relaxed">
            Zamów ręcznie malowany tradycyjny portret olejny ze zdjęcia. Idealny prezent i pamiątka na pokolenia.
          </p>
        </div>
        <a
          href={`${basePath}/#kontakt-sekcja`}
          className="button shrink-0 text-center"
        >
          <div className="button__blobs">
            <div></div>
            <div></div>
            <div></div>
          </div>
          <div className="button__text">
            Zamów swój portret <ArrowRight className="w-4 h-4 ml-1.5" />
          </div>
        </a>
      </section>

      {/* Grid margin spacing padding for offset items */}
      <div className="h-16 hidden lg:block" />
    </div>
  );
}
