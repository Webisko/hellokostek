import React, { useState, useEffect } from "react";
import type { Product } from "../types";
import { ChevronLeft, Shield, ArrowRight, X, Lock } from "lucide-react";

interface ProductDetailProps {
  product: Product;
}

export default function ProductDetail({ product }: ProductDetailProps) {
  const [selectedType, setSelectedType] = useState<"original" | "print">(
    product.isOriginalAvailable ? "original" : "print"
  );

  const [isLightboxOpen, setIsLightboxOpen] = useState(false);
  const [quantity, setQuantity] = useState(1);

  const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
  const [checkoutStep, setCheckoutStep] = useState<1 | 2>(1);
  
  // Checkout form fields
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [fullName, setFullName] = useState("");
  const [streetAddress, setStreetAddress] = useState("");
  const [postalCode, setPostalCode] = useState("");
  const [city, setCity] = useState("");
  const [paczkomatCode, setPaczkomatCode] = useState("");
  
  // Payment fields
  const [paymentMethod, setPaymentMethod] = useState<"blik" | "card" | "transfer">("blik");
  const [blikCode, setBlikCode] = useState("");
  const [cardNumber, setCardNumber] = useState("");
  const [cardExpiry, setCardExpiry] = useState("");
  const [cardCvv, setCardCvv] = useState("");
  
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    if (isCheckoutOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [isCheckoutOpen]);

  const deliveryOptions = selectedType === "original" 
    ? [
        { id: "free_courier", name: "Ubezpieczona przesyłka kurierska", price: 0 }
      ]
    : [
        { id: "inpost", name: "InPost Paczkomat 24/7", price: 15 },
        { id: "orlen", name: "Orlen Paczka", price: 10 },
        { id: "courier", name: "Kurier DPD / InPost", price: 20 }
      ];

  const [selectedDelivery, setSelectedDelivery] = useState(
    selectedType === "original" ? "free_courier" : ""
  );

  useEffect(() => {
    setSelectedDelivery(selectedType === "original" ? "free_courier" : "");
  }, [selectedType]);

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        setIsLightboxOpen(false);
        setIsCheckoutOpen(false);
      }
    };
    if (isLightboxOpen || isCheckoutOpen) {
      window.addEventListener("keydown", handleKeyDown);
    }
    return () => {
      window.removeEventListener("keydown", handleKeyDown);
    };
  }, [isLightboxOpen, isCheckoutOpen]);

  const currentPrice = selectedType === "original" ? product.originalPrice : (product.printPrice || 20);

  const isPointDelivery = selectedDelivery === "inpost" || selectedDelivery === "orlen";

  const isKrok1Valid = () => {
    if (!selectedDelivery) return false;
    if (!email.includes("@") || email.length < 3) return false;
    if (phone.trim().length < 9) return false;
    if (fullName.trim().length < 3) return false;
    if (isPointDelivery) {
      if (paczkomatCode.trim().length < 4) return false;
    } else {
      if (streetAddress.trim().length < 3) return false;
      if (postalCode.trim().length < 5) return false;
      if (city.trim().length < 2) return false;
    }
    return true;
  };

  const isKrok2Valid = () => {
    if (paymentMethod === "blik") {
      return blikCode.length === 6;
    }
    if (paymentMethod === "card") {
      return cardNumber.replace(/\s/g, "").length === 16 && cardExpiry.length === 5 && cardCvv.length === 3;
    }
    return true;
  };

  const handleFinalSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!isKrok2Valid()) return;
    
    setIsSubmitting(true);
    
    // Simulate transaction authorization process for a premium look
    await new Promise((resolve) => setTimeout(resolve, 1800));
    
    setIsSubmitting(false);
    setIsCheckoutOpen(false);
    
    const orderNum = "HK-" + Math.floor(10000 + Math.random() * 90000);
    const deliveryOpt = deliveryOptions.find(opt => opt.id === selectedDelivery) || deliveryOptions[0];
    
    // Redirect to the success page with query parameters representing the purchase
    if (typeof window !== "undefined") {
      window.location.href = `${basePath}/sukces-zakup?orderNumber=${orderNum}&productTitle=${encodeURIComponent(product.title)}&purchaseType=${selectedType}&price=${currentPrice}&shippingMethod=${encodeURIComponent(deliveryOpt.name)}&shippingPrice=${deliveryOpt.price}`;
    }
  };

  const basePath = "/hellokostek";

  return (
    <div className="animate-fadeIn pt-12 md:pt-20 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 max-w-[1600px] mx-auto space-y-16">
      {/* Back to Gallery Path Link */}
      <a
        href={`${basePath}/sklep`}
        className="inline-flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-off-black hover:text-magenta-accent transition-all duration-300"
      >
        <ChevronLeft className="w-4 h-4" />
        Cofnij do galerii prac
      </a>

      {/* Main Container Dual-Column Layout (50/50 split) */}
      <section className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
        {/* Left Column: Media Context (58% width) */}
        <div className="lg:col-span-7 space-y-6">
          <div 
            className="relative overflow-hidden rounded-[32px] border border-gray-100 bg-gray-55 cursor-zoom-in group/img"
            onClick={() => setIsLightboxOpen(true)}
          >
            <img
              src={product.imageUrl}
              alt={product.title}
              referrerPolicy="no-referrer"
              className="w-full h-auto block select-none"
            />
            
            {/* Visual Guide Overlay */}
            <div className="absolute top-4 right-4 bg-off-black/85 backdrop-blur-sm text-white px-3 py-1.5 rounded-full text-xs font-mono tracking-wider z-10 pointer-events-none opacity-0 group-hover/img:opacity-100 transition-opacity duration-300">
              KLIKNIJ ABY POWIĘKSZYĆ
            </div>
          </div>
        </div>

        {/* Right Column: Title, Parameters Selector and wide Purchase Controls (42% width) */}
        <div className="lg:col-span-5 space-y-8 font-sans">
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

          {/* Certificate of Authenticity Card */}
          <div className="bg-stone-50 rounded-2xl p-5 border border-gray-100 space-y-2">
            <h4 className="font-display text-sm text-gray-950 font-semibold flex items-center gap-2">
              <Shield className="w-4 h-4 text-[#E0115F]" />
              Certyfikat Autentyczności
            </h4>
            <p className="text-xs text-gray-650 leading-relaxed font-sans">
              Każdy obraz – czy to rysunek, akwarela, czy portret olejny – otrzymuje ręcznie wypisany i opieczętowany certyfikat, potwierdzający jego autentyczność oraz unikalność.
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
                      ? "border-magenta-accent bg-gray-50/40"
                      : "border-gray-200 bg-white hover:border-lime-accent hover:bg-gray-55"
                  }`}
                >
                  <div>
                    <span className="font-semibold block text-gray-900 text-base">Oryginał ręcznie malowany • {product.originalPrice} zł</span>
                    <span className="text-sm text-gray-500 block mt-0.5">Autentyczne dzieło, tylko 1 sztuka</span>
                  </div>
                  <span className="bg-[#E0115F] text-white text-xs font-mono px-2.5 py-1 rounded font-bold uppercase tracking-wider shrink-0 ml-2">Dostępny</span>
                </label>
              ) : (
                <div className="flex items-center justify-between p-4 rounded-xl border border-gray-150 bg-gray-50 opacity-65">
                  <div>
                    <span className="font-semibold block text-gray-600 text-base">Oryginał ręcznie malowany</span>
                    <span className="text-sm text-gray-400 block mt-0.5">Sprzedany do kolekcji prywatnej</span>
                  </div>
                  <span className="bg-gray-900 text-white text-xs font-mono px-2.5 py-1 rounded font-bold uppercase tracking-wider shrink-0 ml-2">Wyprzedany</span>
                </div>
              )}

              {product.printPrice && (
                <label
                  onClick={() => { setSelectedType("print"); setQuantity(1); }}
                  className={`flex items-center justify-between p-4 rounded-xl border cursor-pointer transition-all ${
                    selectedType === "print"
                      ? "border-magenta-accent bg-gray-50/40"
                      : "border-gray-200 bg-white hover:border-lime-accent hover:bg-gray-55"
                  }`}
                >
                  <div>
                    <span className="font-semibold block text-gray-900 text-base">Wydruk kolekcjonerski • {product.printPrice} zł</span>
                    <span className="text-sm text-gray-500 block mt-0.5">Sygnowana reprodukcja fakturowa na papierze archiwalnym</span>
                  </div>
                  <span className="bg-[#E0115F] text-white text-xs font-mono px-2.5 py-1 rounded font-bold uppercase tracking-wider shrink-0 ml-2">Dostępny</span>
                </label>
              )}
            </div>
            {selectedType === "print" && (
              <div className="pt-3 pb-5">
                <div className="flex items-center gap-4">
                  <span className="font-mono text-xs uppercase tracking-widest text-gray-500 font-bold">Ilość</span>
                  <div className="inline-flex items-center border border-gray-200 rounded-xl overflow-hidden bg-white">
                    <button
                      type="button"
                      onClick={() => setQuantity(Math.max(1, quantity - 1))}
                      className="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-55 hover:text-magenta-accent active:bg-gray-100 transition-colors font-bold cursor-pointer text-base"
                    >
                      -
                    </button>
                    <span className="w-10 text-center font-mono font-bold text-gray-900 text-base">
                      {quantity}
                    </span>
                    <button
                      type="button"
                      onClick={() => setQuantity(quantity + 1)}
                      className="w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-55 hover:text-magenta-accent active:bg-gray-100 transition-colors font-bold cursor-pointer text-base"
                    >
                      +
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Primary Purchase Triggers: Direct Stripe and Cart Insertion */}
          <div className="pt-2">
            <button
              onClick={() => {
                setCheckoutStep(1);
                setIsCheckoutOpen(true);
              }}
              className="button button--full cursor-pointer"
            >
              <div className="button__blobs">
                <div></div>
                <div></div>
                <div></div>
              </div>
              <div className="button__text">
                Przejdź do zamówienia
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
                <td className="py-2.5">Wysyłka w ciągu 2 dni roboczych</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      {/* Checkout Drawer */}
      <div 
        className={`fixed inset-0 z-50 transition-opacity duration-300 ${
          isCheckoutOpen ? "opacity-100 pointer-events-auto" : "opacity-0 pointer-events-none"
        }`}
      >
        {/* Backdrop overlay */}
        <div 
          className="absolute inset-0 bg-off-black/40 backdrop-blur-sm"
          onClick={() => setIsCheckoutOpen(false)}
        />
        
        {/* Drawer content panel */}
        <div 
          className={`absolute right-0 top-0 h-full w-full sm:w-[540px] bg-stone-50 shadow-2xl flex flex-col transition-transform duration-300 ease-out transform ${
            isCheckoutOpen ? "translate-x-0" : "translate-x-full"
          }`}
        >
          {/* Header */}
          <div className="p-6 border-b border-gray-100 flex items-center justify-between bg-white">
            <div>
              <h3 className="font-display text-xl text-gray-950 font-semibold">Zamówienie</h3>
              <p className="text-sm text-gray-500 font-mono mt-0.5 truncate max-w-[320px]">
                {product.title} ({selectedType === "original" ? "Oryginał" : `Wydruk x${quantity}`})
              </p>
            </div>
            <button 
              type="button"
              onClick={() => setIsCheckoutOpen(false)}
              className="text-gray-400 hover:text-off-black transition-colors p-2 cursor-pointer border-none bg-transparent"
              aria-label="Zamknij"
            >
              <X className="w-6 h-6" />
            </button>
          </div>
          
          {/* Form */}
          <form 
            onSubmit={checkoutStep === 1 ? (e) => { e.preventDefault(); if (isKrok1Valid()) setCheckoutStep(2); } : handleFinalSubmit} 
            className="flex-1 flex flex-col min-h-0 bg-white"
          >
            {/* Scrollable form body */}
            <div className="flex-1 overflow-y-auto p-6 space-y-6">
              {checkoutStep === 1 ? (
                <div className="space-y-4">
                  <div className="flex items-center gap-2.5 text-xs font-mono uppercase tracking-wider font-bold">
                    <span className="text-[#E0115F]">Krok 1: Dostawa</span>
                    <span className="text-gray-300 font-sans">→</span>
                    <span className="text-gray-400">Krok 2: Płatność</span>
                  </div>
                  
                  <div className="space-y-4 pt-2">
                    {/* Name */}
                    <div className="space-y-1.5">
                      <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Imię i Nazwisko *</label>
                      <input
                        type="text"
                        required
                        value={fullName}
                        onChange={(e) => setFullName(e.target.value)}
                        className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-sans"
                        placeholder="np. Jan Kowalski"
                      />
                    </div>

                    {/* Email */}
                    <div className="space-y-1.5">
                      <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Adres E-mail *</label>
                      <input
                        type="email"
                        required
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-sans"
                        placeholder="np. jan.kowalski@gmail.com"
                      />
                    </div>
                    
                    {/* Phone */}
                    <div className="space-y-1.5">
                      <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Numer Telefonu *</label>
                      <input
                        type="tel"
                        required
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-sans"
                        placeholder="np. 500 600 700"
                      />
                    </div>

                    {/* Delivery Option Selection inside checkout */}
                    <div className="space-y-2">
                      <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Sposób dostawy</label>
                      <div className="space-y-2">
                        {deliveryOptions.map((opt) => (
                          <label
                            key={opt.id}
                            onClick={() => setSelectedDelivery(opt.id)}
                            className={`flex items-center justify-between p-3.5 rounded-xl border cursor-pointer transition-all text-sm font-sans ${
                              selectedDelivery === opt.id
                                ? "border-magenta-accent bg-gray-50/40"
                                : "border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-55"
                            }`}
                          >
                            <span className="text-gray-800">{opt.name}</span>
                            <span className="font-mono font-bold text-gray-900 shrink-0 ml-2">
                              {opt.price === 0 ? "Bezpłatnie" : `${opt.price} zł`}
                            </span>
                          </label>
                        ))}
                      </div>
                    </div>
                    
                    {/* Conditional Fields based on Paczkomat / Courier */}
                    {selectedDelivery && (
                      isPointDelivery ? (
                        <div className="space-y-1.5">
                          <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Kod Paczkomatu InPost / Orlen Paczki *</label>
                          <input
                            type="text"
                            required
                            value={paczkomatCode}
                            onChange={(e) => setPaczkomatCode(e.target.value.toUpperCase())}
                            className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-sans"
                            placeholder="np. WAW12A"
                          />
                          <p className="text-xs text-gray-400 leading-relaxed font-sans mt-1">
                            Wpisz kod swojego ulubionego Paczkomatu lub Punktu odbioru. Paczkę wyślemy na ten punkt.
                          </p>
                        </div>
                      ) : (
                        <>
                          <div className="space-y-1.5">
                            <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Ulica i numer domu/mieszkania *</label>
                            <input
                              type="text"
                              required
                              value={streetAddress}
                              onChange={(e) => setStreetAddress(e.target.value)}
                              className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-sans"
                              placeholder="np. Piotrkowska 100/5"
                            />
                          </div>
                          <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-1.5">
                              <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Kod pocztowy *</label>
                              <input
                                type="text"
                                required
                                value={postalCode}
                                onChange={(e) => setPostalCode(e.target.value)}
                                className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-sans"
                                placeholder="np. 90-004"
                              />
                            </div>
                            <div className="space-y-1.5">
                              <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Miasto *</label>
                              <input
                                type="text"
                                required
                                value={city}
                                onChange={(e) => setCity(e.target.value)}
                                className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-sans"
                                placeholder="np. Łódź"
                              />
                            </div>
                          </div>
                        </>
                      )
                    )}
                  </div>
                  
                  {/* Order summary info inside step 1 */}
                  <div className="p-5 bg-stone-50 border border-gray-100 rounded-2xl space-y-3 mt-4 font-sans text-base text-gray-700">
                    <div className="flex justify-between">
                      <span>Praca ({selectedType === "original" ? "Oryginał" : `Wydruk x${quantity}`}):</span>
                      <span className="font-semibold text-gray-950">{currentPrice * quantity} zł</span>
                    </div>
                     <div className="flex justify-between">
                      <span>Wysyłka {selectedDelivery ? `(${deliveryOptions.find(opt => opt.id === selectedDelivery)?.name})` : ""}:</span>
                      <span className="font-semibold text-gray-950">
                        {!selectedDelivery 
                          ? "Wybierz sposób dostawy" 
                          : (deliveryOptions.find(opt => opt.id === selectedDelivery)?.price === 0 
                            ? "Bezpłatnie" 
                            : `${deliveryOptions.find(opt => opt.id === selectedDelivery)?.price} zł`)}
                      </span>
                    </div>
                    <div className="flex justify-between border-t border-gray-200/60 pt-3 text-base text-gray-950 font-bold">
                      <span>Razem:</span>
                      <span className="text-magenta-accent font-bold text-lg">
                        {currentPrice * quantity + (deliveryOptions.find(opt => opt.id === selectedDelivery)?.price || 0)} zł
                      </span>
                    </div>
                  </div>
                </div>
              ) : (
                <div className="space-y-4">
                  <div className="flex items-center gap-2.5 text-xs font-mono uppercase tracking-wider font-bold">
                    <span className="text-gray-400">Krok 1: Dostawa</span>
                    <span className="text-gray-300 font-sans">→</span>
                    <span className="text-[#E0115F]">Krok 2: Płatność</span>
                  </div>
                  
                  <div className="space-y-4 pt-2">
                    <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Metoda płatności</label>
                    
                    {/* Method selector */}
                    <div className="grid grid-cols-3 gap-2">
                      <button
                        type="button"
                        onClick={() => setPaymentMethod("blik")}
                        className={`p-3 rounded-xl border text-xs font-bold font-mono tracking-wider transition-all cursor-pointer ${
                          paymentMethod === "blik"
                            ? "border-[#E0115F] bg-gray-50 text-off-black"
                            : "border-gray-200 bg-white text-gray-400 hover:bg-gray-55"
                        }`}
                      >
                        BLIK
                      </button>
                      <button
                        type="button"
                        onClick={() => setPaymentMethod("card")}
                        className={`p-3 rounded-xl border text-xs font-bold font-mono tracking-wider transition-all cursor-pointer ${
                          paymentMethod === "card"
                            ? "border-[#E0115F] bg-gray-50 text-off-black"
                            : "border-gray-200 bg-white text-gray-400 hover:bg-gray-55"
                        }`}
                      >
                        KARTA
                      </button>
                      <button
                        type="button"
                        onClick={() => setPaymentMethod("transfer")}
                        className={`p-3 rounded-xl border text-xs font-bold font-mono tracking-wider transition-all cursor-pointer ${
                          paymentMethod === "transfer"
                            ? "border-[#E0115F] bg-gray-50 text-off-black"
                            : "border-gray-200 bg-white text-gray-400 hover:bg-gray-55"
                        }`}
                      >
                        PRZELEW
                      </button>
                    </div>
                    
                    {/* Method fields */}
                    {paymentMethod === "blik" && (
                      <div className="space-y-1.5 animate-fadeIn">
                        <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Kod BLIK *</label>
                        <input
                          type="text"
                          maxLength={6}
                          required
                          value={blikCode}
                          onChange={(e) => setBlikCode(e.target.value.replace(/\D/g, ""))}
                          className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all tracking-[0.5em] text-center font-mono font-bold bg-white text-gray-900"
                          placeholder="000000"
                        />
                      </div>
                    )}
                    
                    {paymentMethod === "card" && (
                      <div className="space-y-3 animate-fadeIn">
                        <div className="space-y-1.5">
                          <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Numer Karty *</label>
                          <input
                            type="text"
                            required
                            value={cardNumber}
                            onChange={(e) => setCardNumber(e.target.value.replace(/\s?/g, '').replace(/(\d{4})/g, '$1 ').trim())}
                            className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-mono"
                            placeholder="0000 0000 0000 0000"
                          />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                          <div className="space-y-1.5">
                            <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">Termin ważności *</label>
                            <input
                              type="text"
                              required
                              value={cardExpiry}
                              onChange={(e) => setCardExpiry(e.target.value.replace(/\D/g, '').replace(/(\d{2})/, '$1/'))}
                              className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-mono"
                              placeholder="MM/RR"
                            />
                          </div>
                          <div className="space-y-1.5">
                            <label className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold block">CVC / CVV *</label>
                            <input
                              type="text"
                              maxLength={3}
                              required
                              value={cardCvv}
                              onChange={(e) => setCardCvv(e.target.value.replace(/\D/g, ""))}
                              className="w-full px-4 py-3.5 rounded-xl border border-gray-200 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-base transition-all bg-white text-gray-900 font-mono"
                              placeholder="000"
                            />
                          </div>
                        </div>
                      </div>
                    )}
                    
                    {paymentMethod === "transfer" && (
                      <div className="p-4 bg-stone-50 border border-gray-150 rounded-xl space-y-2 animate-fadeIn">
                        <p className="text-xs text-gray-650 leading-relaxed font-sans">
                          Dane do przelewu wyślemy na Twój e-mail po potwierdzeniu zamówienia. Rezerwujemy produkt na 3 dni robocze.
                        </p>
                      </div>
                    )}
                  </div>
                  
                  {/* Order summary info inside step 2 */}
                  <div className="p-5 bg-stone-50 border border-gray-100 rounded-2xl space-y-3 mt-4 font-sans text-base text-gray-700">
                    <div className="flex justify-between">
                      <span>Praca ({selectedType === "original" ? "Oryginał" : `Wydruk x${quantity}`}):</span>
                      <span className="font-semibold text-gray-950">{currentPrice * quantity} zł</span>
                    </div>
                    <div className="flex justify-between">
                      <span>Wysyłka ({deliveryOptions.find(opt => opt.id === selectedDelivery)?.name}):</span>
                      <span className="font-semibold text-gray-950">
                        {deliveryOptions.find(opt => opt.id === selectedDelivery)?.price === 0 
                          ? "Bezpłatnie" 
                          : `${deliveryOptions.find(opt => opt.id === selectedDelivery)?.price} zł`}
                      </span>
                    </div>
                    <div className="flex justify-between border-t border-gray-200/60 pt-3 text-base text-gray-950 font-bold">
                      <span>Razem:</span>
                      <span className="text-magenta-accent font-bold text-lg">
                        {currentPrice * quantity + (deliveryOptions.find(opt => opt.id === selectedDelivery)?.price || 0)} zł
                      </span>
                    </div>
                  </div>
                </div>
              )}
            </div>
            
            {/* Footer (Sticky) */}
            <div className="p-6 border-t border-gray-100 bg-gray-55 space-y-3">
              {checkoutStep === 1 ? (
                <button
                  type="submit"
                  disabled={!isKrok1Valid()}
                  className="button button--full cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed w-full"
                >
                  <div className="button__blobs">
                    <div></div>
                    <div></div>
                    <div></div>
                  </div>
                  <div className="button__text">
                    Przejdź do płatności
                    <ArrowRight className="w-4 h-4" />
                  </div>
                </button>
              ) : (
                <div className="space-y-3 w-full">
                  <button
                    type="submit"
                    disabled={!isKrok2Valid() || isSubmitting}
                    className="button button--full cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed w-full"
                  >
                    <div className="button__blobs">
                      <div></div>
                      <div></div>
                      <div></div>
                    </div>
                    <div className="button__text">
                      {isSubmitting ? (
                        <>
                          <svg className="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                          Przetwarzanie...
                        </>
                      ) : (
                        <>
                          <Lock className="w-3.5 h-3.5" />
                          Zapłać {currentPrice * quantity + (deliveryOptions.find(opt => opt.id === selectedDelivery)?.price || 0)} zł
                        </>
                      )}
                    </div>
                  </button>
                  <button
                    type="button"
                    onClick={() => setCheckoutStep(1)}
                    className="w-full py-2 px-6 text-xs font-mono uppercase tracking-widest text-center text-gray-500 hover:text-off-black transition-colors cursor-pointer border-none bg-transparent"
                  >
                    Wróć do dostawy
                  </button>
                </div>
              )}
              
              <div className="flex items-center justify-center gap-1.5 text-xs text-gray-400 font-sans">
                <Shield className="w-3.5 h-3.5 text-green-600" />
                <span>Bezpieczne szyfrowanie SSL • hellokostek</span>
              </div>
            </div>
          </form>
        </div>
      </div>

      {/* Lightbox Modal */}
      {isLightboxOpen && (
        <div 
          className="fixed inset-0 bg-off-black/90 backdrop-blur-md z-50 flex items-center justify-center p-4 cursor-zoom-out animate-fadeIn"
          onClick={() => setIsLightboxOpen(false)}
        >
          <button 
            className="absolute top-6 right-6 text-white hover:text-magenta-accent transition-colors p-2 cursor-pointer z-51 border-none bg-transparent"
            onClick={() => setIsLightboxOpen(false)}
            aria-label="Zamknij"
          >
            <X className="w-8 h-8" />
          </button>
          
          <img
            src={product.imageUrl}
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
