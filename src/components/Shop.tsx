import React, { useState } from "react";
import { SHOP_PRODUCTS } from "../data";
import { ArrowRight } from "lucide-react";

export default function Shop() {
  const [activeCategory, setActiveCategory] = useState<"all" | "watercolor" | "drawing" | "prints">("all");

  const filteredProducts = SHOP_PRODUCTS.filter((product) => {
    // Category filter logic
    if (activeCategory === "watercolor" && product.category !== "watercolor") return false;
    if (activeCategory === "drawing" && product.category !== "drawing") return false;
    if (activeCategory === "prints" && !product.printPrice) return false;

    return true;
  });

  const basePath = "/hellokostek";

  return (
    <div className="animate-fadeIn pt-12 md:pt-20 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 max-w-[1600px] mx-auto space-y-16 font-sans">
      {/* Page Title & SEO Introductory block */}
      <header className="pb-12 max-w-none space-y-4">
        <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block">
          SELEKCJA DZIEŁ SZTUKI • ORYGINAŁY I LIMITOWANE WYDRUKI
        </span>
        <h1 className="font-display text-4xl sm:text-6xl text-gray-900 tracking-tight font-normal">
          Galeria Dzieł Oryginalnych
        </h1>
        <p className="text-gray-600 text-base sm:text-lg max-w-4xl leading-relaxed">
          Zbiór autentycznych obrazów olejnych, akwareli oraz rysunków ołówkiem powstałych w mojej pracowni. Każda kompozycja to fizyczne, gotowe dzieło stworzone z zachowaniem klasycznego warsztatu. Wybierz unikatowy oryginał, który zdefiniuje Twoje wnętrze, lub certyfikowany wydruk kolekcjonerski z limitowanej edycji.
        </p>
      </header>

      {/* Modern Filter Rail & Search Interface (Sainer.org Editorial Style) */}
      <div className="flex flex-col md:flex-row gap-6 items-start md:items-center justify-between border-b border-gray-150 pb-6">
        <div className="flex flex-wrap items-center gap-3">
          {[
            { id: "all", label: "Wszystkie Archiwa" },
            { id: "watercolor", label: "Oryginalne Akwarele (300 zł)" },
            { id: "drawing", label: "Rysunki Ołówkiem (200 zł)" },
            { id: "prints", label: "Wydruki Kolekcjonerskie (20-30 zł)" }
          ].map((cat) => (
            <button
              key={cat.id}
              onClick={() => setActiveCategory(cat.id as any)}
              className={`px-4 py-2.5 rounded-lg text-xs font-semibold tracking-wider transition-all uppercase cursor-pointer ${
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
        <div className="columns-1 md:columns-2 lg:columns-3 gap-16 pt-6 [column-fill:_balance]">
          {filteredProducts.map((product) => {
            return (
              <a
                key={product.id}
                href={`${basePath}/sklep/${product.id}`}
                className="break-inside-avoid mb-8 md:mb-16 group flex flex-col justify-between space-y-4 cursor-pointer transition-all duration-500 bg-white border border-gray-55 hover:border-gray-200 p-4 rounded-[28px] hover:shadow-lg block"
              >
                {/* Image Wrapper */}
                <div className="relative rounded-2xl overflow-hidden bg-gray-50 border border-gray-100 w-full">
                  <img
                    src={product.imageUrl}
                    alt={product.title}
                    referrerPolicy="no-referrer"
                    className="w-full h-auto block transition-transform duration-700 ease-out group-hover:scale-105"
                  />
                  
                  {/* Category Stamp */}
                  <span className="absolute top-4 left-4 bg-white/90 backdrop-blur-md text-gray-905 text-xs font-mono font-bold px-3 py-1.5 rounded-lg uppercase tracking-widest border border-gray-100 shadow-xs">
                    {product.category === "watercolor" ? "Akwarela" : "Rysunek"}
                  </span>

                  {/* Year Tag */}
                  <span className="absolute bottom-4 right-4 bg-gray-950 text-white text-xs font-mono px-3 py-1.5 rounded-lg font-medium tracking-widest uppercase">
                    {product.year}
                  </span>
                </div>

                {/* Info block */}
                <div className="space-y-2 px-1">
                  <div className="flex justify-between items-baseline gap-4">
                    <h3 className="font-display text-lg sm:text-xl text-gray-900 group-hover:text-[#E0115F] transition-colors leading-snug">
                      {product.title}
                    </h3>
                    <span className="font-mono text-sm text-[#E0115F] font-bold shrink-0">
                      od {product.category === "watercolor" ? "30 zł" : "20 zł"}
                    </span>
                  </div>
                  
                  <p className="text-sm text-gray-500 line-clamp-2 leading-relaxed">
                    {product.description}
                  </p>

                  <div className="inline-flex items-center gap-1.5 pt-2 text-xs font-mono font-bold uppercase tracking-widest text-gray-450 group-hover:text-[#E0115F] transition-colors">
                    Szczegóły 
                    <ArrowRight className="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform" />
                  </div>
                </div>
              </a>
            );
          })}
        </div>
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
