import React, { useState, useEffect } from "react";
import { Product, CartItem, PageId } from "../types";
import { ChevronLeft, Shield, Sparkles, ArrowRight, CornerDownRight, X } from "lucide-react";

interface ProductDetailProps {
  product: Product;
  addToCart: (
    product: Product, 
    buyType: "original" | "print", 
    quantityToAdd?: number,
    shippingMethod?: string,
    shippingPrice?: number
  ) => void;
  setCurrentPage: (page: PageId) => void;
  cart: CartItem[];
  setIsCartOpen: (isOpen: boolean) => void;
  onPurchaseSuccess?: (order: {
    orderNumber: string;
    productTitle: string;
    purchaseType: "original" | "print";
    price: number;
  }) => void;
}

export default function ProductDetail({
  product,
  addToCart,
  setCurrentPage,
  cart,
  setIsCartOpen,
  onPurchaseSuccess
}: ProductDetailProps) {
  const [selectedType, setSelectedType] = useState<"original" | "print">(
    product.isOriginalAvailable ? "original" : "print"
  );
  const [activeThumbnail, setActiveThumbnail] = useState<"front" | "frame" | "detail">("front");

  const [isLightboxOpen, setIsLightboxOpen] = useState(false);
  const [quantity, setQuantity] = useState(1);

  const deliveryOptions = selectedType === "original" 
    ? [
        { id: "free_courier", name: "Ubezpieczona przesyłka kurierska", price: 0 }
      ]
    : [
        { id: "inpost", name: "InPost Paczkomat 24/7", price: 15 },
        { id: "orlen", name: "Orlen Paczka", price: 10 },
        { id: "courier", name: "Kurier DPD / InPost", price: 20 }
      ];

  const [selectedDelivery, setSelectedDelivery] = useState(deliveryOptions[0].id);

  useEffect(() => {
    setSelectedDelivery(selectedType === "original" ? "free_courier" : "inpost");
  }, [selectedType]);

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        setIsLightboxOpen(false);
      }
    };
    if (isLightboxOpen) {
      window.addEventListener("keydown", handleKeyDown);
    }
    return () => {
      window.removeEventListener("keydown", handleKeyDown);
    };
  }, [isLightboxOpen]);

  const currentPrice = selectedType === "original" ? product.originalPrice : (product.printPrice || 20);

  const handleKupTeraz = () => {
    if (onPurchaseSuccess) {
      const orderNum = "HK-" + Math.floor(10000 + Math.random() * 90000);
      onPurchaseSuccess({
        orderNumber: orderNum,
        productTitle: product.title,
        purchaseType: selectedType,
        price: currentPrice
      });
    } else {
      setCurrentPage("success-purchase");
    }
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  // Extra thumbnails to represent different contexts for editorial minimalism
  const thumbnails = {
    front: product.imageUrl,
    frame: "https://images.unsplash.com/photo-1513519245088-0e12902e5a38?q=80&w=800&auto=format&fit=crop", // Simulated luxury frame setup
    detail: "https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?q=80&w=800&auto=format&fit=crop" // Close-up watercolor/drawing texture
  };

  const currentThumbnailUrl = thumbnails[activeThumbnail] || product.imageUrl;

  return (
    <div className="animate-fadeIn pt-12 md:pt-20 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 max-w-[1600px] mx-auto space-y-16">
      {/* Back to Gallery Path Link */}
      <button
        onClick={() => setCurrentPage("shop")}
        className="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-off-black hover:text-magenta-accent active:text-magenta-accent transition-all duration-300 cursor-pointer"
      >
        <ChevronLeft className="w-4 h-4" />
        Cofnij do galerii prac
      </button>

      {/* Main Container Dual-Column Layout (50/50 split) */}
      <section className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
        {/* Left Column: Media & Thumbnails Context (50% width) */}
        <div className="lg:col-span-6 space-y-6">
          <div 
            className="aspect-[3/4] relative overflow-hidden rounded-[32px] border border-gray-100 bg-gray-50/50 cursor-zoom-in group/img"
            onClick={() => setIsLightboxOpen(true)}
          >
            <img
              src={currentThumbnailUrl}
              alt={`${product.title} w ujęciu ${activeThumbnail}`}
              referrerPolicy="no-referrer"
              className="w-full h-full object-cover transition-transform duration-500 ease-out select-none group-hover/img:scale-102"
            />
            
            {/* Visual Guide Overlay */}
            <div className="absolute top-4 right-4 bg-off-black/85 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-xs font-mono tracking-wider z-10 pointer-events-none opacity-0 group-hover/img:opacity-100 transition-opacity duration-300">
              KLIKNIJ ABY POWIĘKSZYĆ
            </div>
            
            {activeThumbnail === "detail" && (
              <span className="absolute bottom-4 left-4 bg-lime-accent/90 text-off-black px-3 py-1.5 rounded-xl text-xs font-semibold font-sans uppercase tracking-wide tracking-wider z-10 pointer-events-none">
                ZBLIŻENIE NA SYGNATURĘ I STRUKTURĘ
              </span>
            )}
          </div>

          {/* Symmetrical Secondary Thumbnails */}
          <div className="grid grid-cols-3 gap-4">
            <button
              onClick={() => setActiveThumbnail("front")}
              className={`aspect-square rounded-2xl overflow-hidden border p-1 transition-all ${
                activeThumbnail === "front" ? "border-[#E0115F] bg-gray-50/50" : "border-gray-150 hover:bg-gray-50"
              }`}
            >
              <img
                src={thumbnails.front}
                alt="Widok z przodu"
                referrerPolicy="no-referrer"
                className="w-full h-full object-cover rounded-xl"
              />
            </button>
            <button
              onClick={() => setActiveThumbnail("frame")}
              className={`aspect-square rounded-2xl overflow-hidden border p-1 transition-all ${
                activeThumbnail === "frame" ? "border-[#E0115F] bg-gray-50/50" : "border-gray-150 hover:bg-gray-50"
              }`}
            >
              <img
                src={thumbnails.frame}
                alt="Kontekst ramy i passe-partout"
                referrerPolicy="no-referrer"
                className="w-full h-full object-cover rounded-xl"
              />
            </button>
            <button
              onClick={() => setActiveThumbnail("detail")}
              className={`aspect-square rounded-2xl overflow-hidden border p-1 transition-all ${
                activeThumbnail === "detail" ? "border-[#E0115F] bg-gray-50/50" : "border-gray-150 hover:bg-gray-50"
              }`}
            >
              <img
                src={thumbnails.detail}
                alt="Materiały i zbliżenie"
                referrerPolicy="no-referrer"
                className="w-full h-full object-cover rounded-xl"
              />
            </button>
          </div>
        </div>

        {/* Right Column: Title, Parameters Selector and wide Purchase Controls (50% width) */}
        <div className="lg:col-span-6 space-y-8 font-sans">
          <div className="space-y-4">
            <span className="font-mono text-xs uppercase tracking-widest text-gray-400 font-bold block">
              Dostępne w Pracowni Artystycznej • {product.year}
            </span>
            <h1 className="font-display text-4.5xl leading-tight text-gray-950 font-normal">
              {product.title}
            </h1>
            <div className="flex gap-4 items-baseline">
              <span className="text-3xl font-bold font-mono text-[#E0115F]">
                {currentPrice} zł
              </span>
              {selectedType === "original" && (
                <span className="text-xs font-mono text-gray-500 uppercase tracking-widest">
                  Zawiera bezpłatną dostawę
                </span>
              )}
            </div>
          </div>

          <div className="border-t border-gray-100 pt-6 space-y-3">
            <p className="text-gray-600 text-base leading-relaxed">
              {product.description}
            </p>
          </div>

          {/* Minimalist Selection Menu: Original vs. Print */}
          <div className="space-y-3">
            <span className="font-mono text-xs uppercase tracking-widest text-gray-500 font-bold block">
              Wybierz Wariant Pracy
            </span>
            <div className="space-y-2">
              {product.isOriginalAvailable ? (
                <label
                  onClick={() => { setSelectedType("original"); setQuantity(1); }}
                  className={`flex items-center justify-between p-4 rounded-xl border cursor-pointer transition-all ${
                    selectedType === "original"
                      ? "border-magenta-accent bg-gray-50/40 font-medium"
                      : "border-gray-200 bg-white hover:border-lime-accent hover:bg-gray-50"
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <input
                      type="radio"
                      name="wariant"
                      checked={selectedType === "original"}
                      readOnly
                      className="text-gray-900 focus:ring-gray-900 w-4 h-4"
                    />
                    <div>
                      <span className="font-semibold block text-gray-900 text-sm">Oryginał ręcznie malowany</span>
                      <span className="text-xs text-gray-500 block">Autentyczne dzieło, tylko 1 sztuka • {product.originalPrice} zł</span>
                    </div>
                  </div>
                  <span className="bg-[#E0115F] text-white text-[12px] font-mono px-2 py-0.5 rounded uppercase">Dostępny</span>
                </label>
              ) : (
                <div className="flex items-center justify-between p-4 rounded-xl border border-gray-150 bg-gray-50 opacity-65">
                  <div className="flex items-center gap-3">
                    <div className="w-4 h-4 rounded-full border border-gray-300 bg-gray-100" />
                    <div>
                      <span className="font-semibold block text-gray-600 text-sm">Oryginał ręcznie malowany</span>
                      <span className="text-xs text-gray-400 block">Sprzedany do kolekcji prywatnej</span>
                    </div>
                  </div>
                  <span className="bg-gray-200 text-gray-500 text-[12px] font-mono px-2 py-0.5 rounded uppercase">Wyprzedany</span>
                </div>
              )}

              {product.printPrice && (
                <label
                  onClick={() => { setSelectedType("print"); setQuantity(1); }}
                  className={`flex items-center justify-between p-4 rounded-xl border cursor-pointer transition-all ${
                    selectedType === "print"
                      ? "border-magenta-accent bg-gray-50/40 font-medium"
                      : "border-gray-200 bg-white hover:border-lime-accent hover:bg-gray-50"
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <input
                      type="radio"
                      name="wariant"
                      checked={selectedType === "print"}
                      readOnly
                      className="text-gray-900 focus:ring-gray-900 w-4 h-4"
                    />
                    <div>
                      <span className="font-semibold block text-gray-900 text-sm">Wydruk kolekcjonerski</span>
                      <span className="text-xs text-gray-500 block">Sygnowana reprodukcja fakturowa na papierze archiwalnym • {product.printPrice} zł</span>
                    </div>
                  </div>
                  <span className="bg-gray-900 text-white text-[12px] font-mono px-2 py-0.5 rounded uppercase">Dostępny</span>
                </label>
              )}
            </div>
          </div>

          {/* Primary Purchase Triggers: Direct Stripe and Cart Insertion */}
          <div className="space-y-6 pt-2">
            {selectedType === "print" && (
              <div className="flex items-center gap-4 text-xs font-sans">
                <span className="font-semibold text-gray-500 uppercase tracking-wider">Ilość:</span>
                <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-white">
                  <button
                    type="button"
                    onClick={() => setQuantity(Math.max(1, quantity - 1))}
                    className="w-7 h-7 flex items-center justify-center text-gray-600 hover:bg-gray-50 hover:text-magenta-accent active:bg-gray-100 transition-colors font-bold cursor-pointer text-xs"
                  >
                    -
                  </button>
                  <span className="w-8 text-center font-mono font-bold text-gray-900 text-xs">
                    {quantity}
                  </span>
                  <button
                    type="button"
                    onClick={() => setQuantity(quantity + 1)}
                    className="w-7 h-7 flex items-center justify-center text-gray-600 hover:bg-gray-50 hover:text-magenta-accent active:bg-gray-100 transition-colors font-bold cursor-pointer text-xs"
                  >
                    +
                  </button>
                </div>
              </div>
            )}

            {/* Delivery Selection */}
            <div className="space-y-2.5">
              <span className="font-semibold text-gray-500 uppercase tracking-wider text-xs block">Opcja dostawy:</span>
              <div className="space-y-2">
                {deliveryOptions.map((opt) => (
                  <label
                    key={opt.id}
                    onClick={() => setSelectedDelivery(opt.id)}
                    className={`flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all text-xs ${
                      selectedDelivery === opt.id
                        ? "border-magenta-accent bg-gray-50/40 font-medium"
                        : "border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50"
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="radio"
                        name="delivery"
                        checked={selectedDelivery === opt.id}
                        readOnly
                        className="text-gray-900 focus:ring-gray-900 w-3.5 h-3.5"
                      />
                      <span className="text-gray-800">{opt.name}</span>
                    </div>
                    <span className="font-mono font-bold text-gray-900 shrink-0 ml-2">
                      {opt.price === 0 ? "Bezpłatnie" : `${opt.price} zł`}
                    </span>
                  </label>
                ))}
              </div>
            </div>

            <button
              onClick={handleKupTeraz}
              className="button button--full"
            >
              <div className="button__blobs">
                <div></div>
                <div></div>
                <div></div>
              </div>
              <div className="button__text">
                Kup teraz
                <ArrowRight className="w-4 h-4" />
              </div>
            </button>
          </div>

          {/* Real technical parameters listed as raw prose/text to maintain the minimal aesthetic */}
          <table className="w-full text-xs text-gray-600 border-t border-gray-100 pt-6">
            <tbody>
              <tr className="border-b border-gray-50">
                <td className="py-2.5 font-semibold text-gray-900 w-1/3">Nośnik bazowy</td>
                <td className="py-2.5">
                  {product.category === "watercolor" 
                    ? "Gruby papier bawełniany Arches 300g/m²" 
                    : "Wysokiej jakości papier graficzny Canson 220g/m²"}
                </td>
              </tr>
              <tr className="border-b border-gray-50">
                <td className="py-2.5 font-semibold text-gray-900">Format fizyczny</td>
                <td className="py-2.5">Standardowy 30x40 cm (możliwość zamówienia passe-partout)</td>
              </tr>
              <tr className="border-b border-gray-50">
                <td className="py-2.5 font-semibold text-gray-900">Sygnatura</td>
                <td className="py-2.5">Ręczny podpis autora ołówkiem u dołu pracy oraz pieczęć sucha lakowa</td>
              </tr>
              <tr>
                <td className="py-2.5 font-semibold text-gray-900">Czas wysyłki</td>
                <td className="py-2.5">Darmowa ubezpieczona paczka w przeciągu 2 dni roboczych (Polska)</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      {/* Lightbox Modal */}
      {isLightboxOpen && (
        <div 
          className="fixed inset-0 bg-off-black/90 backdrop-blur-md z-50 flex items-center justify-center p-4 cursor-zoom-out animate-fadeIn"
          onClick={() => setIsLightboxOpen(false)}
        >
          <style>{`
            @keyframes scaleIn {
              from { transform: scale(0.95); opacity: 0; }
              to { transform: scale(1); opacity: 1; }
            }
            .animate-scaleIn {
              animation: scaleIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            }
          `}</style>
          
          <button 
            className="absolute top-6 right-6 text-white hover:text-magenta-accent transition-colors p-2 cursor-pointer z-51"
            onClick={() => setIsLightboxOpen(false)}
            aria-label="Zamknij"
          >
            <X className="w-8 h-8" />
          </button>
          
          <img
            src={currentThumbnailUrl}
            alt={product.title}
            referrerPolicy="no-referrer"
            className="max-w-full max-h-[90vh] object-contain rounded-lg shadow-2xl select-none animate-scaleIn"
            onClick={(e) => e.stopPropagation()}
          />
        </div>
      )}
    </div>
  );
}
