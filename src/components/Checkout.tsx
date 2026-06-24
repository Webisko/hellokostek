import { CartItem } from "../types";
import { X, Trash2, ShieldCheck, CreditCard, Lock, CheckCircle, Smartphone } from "lucide-react";
import React, { useState } from "react";

interface CheckoutProps {
  isOpen: boolean;
  onClose: () => void;
  cart: CartItem[];
  removeFromCart: (cartId: string) => void;
  updateQuantity: (cartId: string, delta: number) => void;
  clearCart: () => void;
}

export default function Checkout({
  isOpen,
  onClose,
  cart,
  removeFromCart,
  updateQuantity,
  clearCart,
}: CheckoutProps) {
  const [showStripeModal, setShowStripeModal] = useState(false);
  const [paymentSuccess, setPaymentSuccess] = useState(false);
  
  // Form states
  const [email, setEmail] = useState("");
  const [fullName, setFullName] = useState("");
  const [cardNumber, setCardNumber] = useState("");
  const [expiry, setExpiry] = useState("");
  const [cvc, setCvc] = useState("");
  const [postalCode, setPostalCode] = useState("");

  if (!isOpen) return null;

  const subtotal = cart.reduce((total, item) => total + item.price * item.quantity, 0);
  const shippingFee = cart.reduce((max, item) => Math.max(max, item.shippingPrice || 0), 0);
  const grandTotal = subtotal + shippingFee;

  const handleCheckoutSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setPaymentSuccess(true);
    setTimeout(() => {
      setPaymentSuccess(false);
      setShowStripeModal(false);
      clearCart();
      onClose();
    }, 4000);
  };

  return (
    <>
      {/* Sliding Drawer Overlay */}
      <div 
        className="fixed inset-0 bg-off-black/60 backdrop-blur-xs z-50 flex justify-end animate-fadeIn"
        onClick={onClose}
      >
        <div 
          className="bg-off-white w-full max-w-md h-full shadow-2xl flex flex-col justify-between"
          onClick={(e) => e.stopPropagation()}
        >
          {/* Header */}
          <div className="p-6 border-b border-off-black/10 flex items-center justify-between bg-white">
            <h3 className="font-display text-xl text-off-black font-semibold flex items-center gap-2">
              Twój Koszyk
              <span className="text-xs font-mono bg-stone-100 px-2 py-0.5 rounded-full text-stone-500">
                {cart.reduce((sum, item) => sum + item.quantity, 0)} szt.
              </span>
            </h3>
            <button 
              onClick={onClose}
              className="p-2 hover:bg-stone-100 rounded-full text-stone-400 hover:text-off-black transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Cart item list */}
          <div className="flex-grow overflow-y-auto p-6 space-y-4 custom-scroll">
            {cart.length === 0 ? (
              <div className="text-center py-24 space-y-4 text-stone-500">
                <p className="font-sans text-sm">Twój koszyk jest pusty.</p>
                <button
                  onClick={onClose}
                  className="px-6 py-2.5 bg-off-black text-white hover:bg-lime-accent hover:text-off-black active:bg-magenta-accent active:text-white transition-all text-xs font-semibold rounded-lg uppercase tracking-wide cursor-pointer"
                >
                  Kontynuuj zakupy
                </button>
              </div>
            ) : (
              cart.map((item) => (
                <div 
                  key={item.cartId}
                  className="flex gap-4 bg-white p-4 rounded-xl border border-off-black/5 items-center justify-between"
                >
                  <div className="space-y-1">
                    <span className="text-xs font-mono uppercase bg-stone-100 text-stone-600 px-2 py-0.5 rounded-md inline-block">
                      {item.purchaseType === "original" ? "Oryginał" : "Reprodukcja (Wydruk)"}
                    </span>
                    <h4 className="font-display font-medium text-off-black text-sm">{item.title}</h4>
                    <span className="text-xs font-mono font-bold text-magenta-accent block">
                      {item.price} zł
                    </span>
                  </div>

                  {/* Quantity controls & Delete */}
                  <div className="flex items-center gap-3">
                    <div className="flex items-center gap-2 border bg-stone-50 rounded-lg p-1">
                      <button
                        onClick={() => updateQuantity(item.cartId, -1)}
                        className="w-6 h-6 rounded flex items-center justify-center text-xs font-bold hover:bg-white text-stone-500 transition-colors"
                      >
                        -
                      </button>
                      <span className="font-mono text-xs font-semibold w-4 text-center">{item.quantity}</span>
                      <button
                        onClick={() => updateQuantity(item.cartId, 1)}
                        className="w-6 h-6 rounded flex items-center justify-center text-xs font-bold hover:bg-white text-stone-500 transition-colors"
                      >
                        +
                      </button>
                    </div>

                    <button
                      onClick={() => removeFromCart(item.cartId)}
                      className="p-1.5 bg-stone-50 hover:bg-red-50 text-stone-400 hover:text-red-500 rounded-lg transition-colors"
                      title="Usuń z koszyka"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Footer with totals */}
          {cart.length > 0 && (
            <div className="p-6 bg-white border-t border-off-black/10 space-y-4">
              <div className="space-y-2 text-sm text-stone-600">
                <div className="flex justify-between">
                  <span>Suma częściowa</span>
                  <span className="font-mono">{subtotal} zł</span>
                </div>
                <div className="flex justify-between">
                  <span>Wysyłka</span>
                  {shippingFee === 0 ? (
                    <span className="font-sans text-green-700 font-semibold uppercase text-xs">Bezpłatna</span>
                  ) : (
                    <span className="font-mono">{shippingFee} zł</span>
                  )}
                </div>
                <div className="flex justify-between border-t border-stone-100 pt-2 text-off-black font-semibold">
                  <span>Do zapłaty:</span>
                  <span className="text-lg font-bold text-magenta-accent font-mono">{grandTotal} zł</span>
                </div>
              </div>

              <div className="space-y-2 pt-2">
                <button
                  onClick={() => setShowStripeModal(true)}
                  className="w-full py-4 bg-off-black text-white hover:bg-lime-accent hover:text-off-black active:bg-magenta-accent active:text-white transition-all duration-300 font-bold text-sm tracking-wide rounded-xl flex items-center justify-center gap-2 cursor-pointer shadow-sm"
                >
                  <CreditCard className="w-4 h-4" />
                  Przejdź do płatności Stripe
                </button>
                <div className="flex justify-center items-center gap-1 text-xs text-stone-400 font-sans">
                  <Lock className="w-3.5 h-3.5 text-stone-400" />
                  Transakcja szyfrowana SSL • Certyfikat Stripe
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* STRIPE CHECKOUT HIGH-FIDELITY OVERLAY */}
      {showStripeModal && (
        <div className="fixed inset-0 bg-off-black/80 backdrop-blur-md z-[60] flex items-center justify-center p-4">
          <div 
            className="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-off-black/10 flex flex-col animate-scaleIn"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header / Stripe Brand representation */}
            <div className="bg-[#635BFF] py-6 px-8 text-white flex items-center justify-between">
              <div className="flex items-center gap-2">
                <div className="bg-white text-[#635BFF] p-1.5 rounded-lg">
                  <span className="font-bold text-base tracking-tighter uppercase font-sans">S</span>
                </div>
                <div>
                  <h4 className="text-xs uppercase tracking-widest opacity-85 font-mono">Płatność Bezpieczna</h4>
                  <span className="font-bold text-sm font-sans">Stripe Secure checkout</span>
                </div>
              </div>
              <button 
                onClick={() => setShowStripeModal(false)}
                className="text-white/80 hover:text-white p-1 hover:bg-white/10 rounded-full"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Payment success visual state */}
            {paymentSuccess ? (
              <div className="p-8 sm:p-12 text-center space-y-6 flex flex-col items-center justify-center min-h-[400px]">
                <div className="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center shrink-0">
                  <CheckCircle className="w-10 h-10 animate-scaleIn" />
                </div>
                <div className="space-y-2">
                  <h3 className="font-display text-2xl font-bold text-off-black">Płatność Zakończona Sukcesem!</h3>
                  <p className="font-sans text-sm text-stone-600 max-w-xs mx-auto">
                    Dziękujemy za zamówienie. Potwierdzenie zakupu oraz instrukcję wysyłki wyślemy na podany adres e-mail w przeciągu kilku minut.
                  </p>
                </div>
                <p className="font-mono text-xs text-stone-400">ID płatności: ch_3N5b5Z2eZvKYlo2C1g9gHqKl</p>
              </div>
            ) : (
              <form onSubmit={handleCheckoutSubmit} className="p-6 sm:p-8 space-y-6">
                
                {/* Order Summary banner */}
                <div className="bg-stone-50 p-4 rounded-xl border flex justify-between items-center text-xs">
                  <div>
                    <span className="font-mono text-stone-500 uppercase tracking-wider block text-xs">Zamówienie hellokostek.pl</span>
                    <span className="font-sans font-semibold text-off-black">{cart.length} gotowych prac/wydruków</span>
                  </div>
                  <span className="text-base font-bold font-mono text-magenta-accent">{grandTotal} zł</span>
                </div>

                {/* Form fields */}
                <div className="space-y-4 font-sans text-xs">
                  <div className="space-y-1">
                    <label className="font-bold text-stone-600 uppercase tracking-wider text-xs">Twój Adres E-mail *</label>
                    <input
                      type="email"
                      required
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="email@domena.pl"
                      className="w-full p-3 bg-stone-50/50 border border-stone-200 rounded-xl"
                    />
                  </div>

                  <div className="space-y-1">
                    <label className="font-bold text-stone-600 uppercase tracking-wider text-xs">Imię i nazwisko na karcie *</label>
                    <input
                      type="text"
                      required
                      value={fullName}
                      onChange={(e) => setFullName(e.target.value)}
                      placeholder="Katarzyna Kowalska"
                      className="w-full p-3 bg-stone-50/50 border border-stone-200 rounded-xl"
                    />
                  </div>

                  {/* Card Credentials box */}
                  <div className="space-y-1">
                    <label className="font-bold text-stone-600 uppercase tracking-wider text-xs">Dane Karty Kredytowej *</label>
                    <div className="border border-stone-200 bg-stone-50/30 rounded-xl overflow-hidden">
                      <div className="flex items-center gap-2 p-3 bg-white">
                        <CreditCard className="w-5 h-5 text-stone-400" />
                        <input
                          type="text"
                          required
                          value={cardNumber}
                          onChange={(e) => setCardNumber(e.target.value)}
                          placeholder="4242 4242 4242 4242"
                          maxLength={19}
                          className="w-full font-mono bg-transparent outline-none border-none p-0 text-sm"
                        />
                      </div>
                      <div className="grid grid-cols-3 border-t bg-white">
                        <input
                          type="text"
                          required
                          value={expiry}
                          onChange={(e) => setExpiry(e.target.value)}
                          placeholder="MM / RR"
                          maxLength={7}
                          className="p-3 text-center border-r font-mono outline-none text-xs"
                        />
                        <input
                          type="text"
                          required
                          value={cvc}
                          onChange={(e) => setCvc(e.target.value)}
                          placeholder="CVC"
                          maxLength={4}
                          className="p-3 text-center border-r font-mono outline-none text-xs"
                        />
                        <input
                          type="text"
                          required
                          value={postalCode}
                          onChange={(e) => setPostalCode(e.target.value)}
                          placeholder="Kod pocztowy"
                          maxLength={10}
                          className="p-3 text-center font-mono outline-none text-xs"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                {/* Submit triggers */}
                <div className="space-y-3 pt-2">
                  <button
                    type="submit"
                    className="button button--full"
                  >
                    <div className="button__blobs">
                      <div></div>
                      <div></div>
                      <div></div>
                    </div>
                    <div className="button__text">
                      <ShieldCheck className="w-4 h-4" />
                      Zapłać bezpiecznie {grandTotal} zł
                    </div>
                  </button>
                  <p className="text-xs text-stone-400 font-sans text-center leading-normal">
                    Płatności są przetwarzane przez Stripe Inc. zgodnie z normami bezpieczeństwa PCI-DSS Level 1. Żadne poufne dane płatnicze nie są zapisywane na naszych serwerach.
                  </p>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </>
  );
}
