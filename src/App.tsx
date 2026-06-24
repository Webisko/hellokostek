import { useState, useEffect } from "react";
import { PageId, CartItem, Product } from "./types";
import Navbar from "./components/Navbar";
import Home from "./components/Home";
import Shop from "./components/Shop";
import AboutMe from "./components/AboutMe";
import Contact from "./components/Contact";
import Checkout from "./components/Checkout";
import ProductDetail from "./components/ProductDetail";
import Success from "./components/Success";
import PrivacyPolicy from "./components/PrivacyPolicy";
import Terms from "./components/Terms";
import Gallery from "./components/Gallery";
import { Heart, Mail, Info, ArrowRight, ShieldCheck, Sparkles, X, ShoppingBag, Instagram, Facebook, ArrowUp } from "lucide-react";

export default function App() {
  const [currentPage, setCurrentPage] = useState<PageId>("home");
  const [cart, setCart] = useState<CartItem[]>([]);
  const [selectedProduct, setSelectedProduct] = useState<Product | null>(null);
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [showCookies, setShowCookies] = useState(true);
  const [initialContactSubject, setInitialContactSubject] = useState<string>("");
  const [showScrollTop, setShowScrollTop] = useState(false);
  const [lastOrder, setLastOrder] = useState<{
    orderNumber: string;
    productTitle: string;
    purchaseType: "original" | "print";
    price: number;
  } | null>(null);

  useEffect(() => {
    const handleScroll = () => {
      setShowScrollTop(window.scrollY > 400);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  useEffect(() => {
    if (currentPage !== "contact") {
      setInitialContactSubject("");
    }
  }, [currentPage]);

  const handleNavigateToContact = (subject: string) => {
    setInitialContactSubject(subject);
    setCurrentPage("contact");
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  // Load cart from localStorage upon component mounting
  useEffect(() => {
    const savedCart = localStorage.getItem("hellokostek_cart");
    if (savedCart) {
      try {
        setCart(JSON.parse(savedCart));
      } catch (e) {
        console.error("Failed to load cart state", e);
      }
    }
    
    // Check if cookies accepted in previous session
    const cookiesAccepted = localStorage.getItem("hellokostek_cookies");
    if (cookiesAccepted === "true") {
      setShowCookies(false);
    }
  }, []);

  // Save cart to localStorage
  const saveCartToStorage = (newCart: CartItem[]) => {
    setCart(newCart);
    localStorage.setItem("hellokostek_cart", JSON.stringify(newCart));
  };

  const addToCart = (product: Product, buyType: "original" | "print", quantityToAdd: number = 1) => {
    const cartId = `${product.id}-${buyType}`;
    const price = buyType === "original" ? product.originalPrice : (product.printPrice || 0);
    
    const existing = cart.find((item) => item.cartId === cartId);
    if (existing) {
      // If it's an original, don't allow duplicate quantity since there is only 1 original work
      if (buyType === "original") return;
      
      const updated = cart.map((item) =>
        item.cartId === cartId ? { ...item, quantity: item.quantity + quantityToAdd } : item
      );
      saveCartToStorage(updated);
    } else {
      const newItem: CartItem = {
        cartId,
        productId: product.id,
        title: product.title,
        category: product.category,
        purchaseType: buyType,
        price,
        quantity: quantityToAdd,
      };
      saveCartToStorage([...cart, newItem]);
    }
  };

  const removeFromCart = (cartId: string) => {
    const filtered = cart.filter((item) => item.cartId !== cartId);
    saveCartToStorage(filtered);
  };

  const updateQuantity = (cartId: string, delta: number) => {
    const item = cart.find((i) => i.cartId === cartId);
    if (!item) return;
    
    // Don't modify quantity of originals above 1 as there's only 1 original work
    if (item.purchaseType === "original" && delta > 0) return;

    const newQuantity = item.quantity + delta;
    if (newQuantity <= 0) {
      removeFromCart(cartId);
    } else {
      const updated = cart.map((i) =>
        i.cartId === cartId ? { ...i, quantity: newQuantity } : i
      );
      saveCartToStorage(updated);
    }
  };

  const clearCart = () => {
    saveCartToStorage([]);
  };

  const acceptCookies = () => {
    localStorage.setItem("hellokostek_cookies", "true");
    setShowCookies(false);
  };

  // Render correct sub-page based on active tab state
  const renderPage = () => {
    switch (currentPage) {
      case "home":
        return (
          <Home 
            setCurrentPage={setCurrentPage} 
            onSelectProduct={(product) => {
              setSelectedProduct(product);
              setCurrentPage("product-detail");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }} 
          />
        );
      case "gallery":
        return (
          <Gallery 
            setCurrentPage={setCurrentPage} 
            handleNavigateToContact={handleNavigateToContact} 
          />
        );
      case "shop":
        return (
          <Shop 
            addToCart={addToCart} 
            cart={cart} 
            onSelectProduct={(product) => {
              setSelectedProduct(product);
              setCurrentPage("product-detail");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
            setCurrentPage={setCurrentPage}
          />
        );
      case "about":
        return (
          <AboutMe 
            setCurrentPage={setCurrentPage}
            onSelectProduct={(product) => {
              setSelectedProduct(product);
              setCurrentPage("product-detail");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
          />
        );
      case "contact":
        return <Contact initialSubject={initialContactSubject} setCurrentPage={setCurrentPage} />;
      case "product-detail":
        return selectedProduct ? (
          <ProductDetail
            product={selectedProduct}
            addToCart={addToCart}
            cart={cart}
            setCurrentPage={setCurrentPage}
            setIsCartOpen={setIsCartOpen}
            onPurchaseSuccess={(order) => {
              setLastOrder(order);
              setCurrentPage("success-purchase");
            }}
          />
        ) : (
          <Shop 
            addToCart={addToCart} 
            cart={cart} 
            setCurrentPage={setCurrentPage}
            onSelectProduct={(product) => {
              setSelectedProduct(product);
              setCurrentPage("product-detail");
              window.scrollTo({ top: 0, behavior: "smooth" });
            }}
          />
        );
      case "success-purchase":
        return (
          <Success 
            mode="purchase"
            setCurrentPage={setCurrentPage} 
            clearCart={clearCart} 
            orderDetails={lastOrder}
          />
        );
      case "success-contact":
        return (
          <Success 
            mode="contact"
            setCurrentPage={setCurrentPage} 
          />
        );
      case "privacy":
        return <PrivacyPolicy onNavigateToContact={handleNavigateToContact} />;
      case "terms":
        return <Terms onNavigateToContact={handleNavigateToContact} />;
      default:
        return (
          <Home 
            setCurrentPage={setCurrentPage} 
            onSelectProduct={(product) => {
              setSelectedProduct(product);
              setCurrentPage("product-detail");
            }} 
          />
        );
    }
  };

  const isSuccessPage = currentPage === "success-purchase" || currentPage === "success-contact";

  return (
    <div className="min-h-screen bg-white text-off-black selection:bg-lime-accent selection:text-off-black flex flex-col justify-between">
      
      {/* Centered Navigation */}
      {!isSuccessPage && (
        <Navbar
          currentPage={currentPage}
          setCurrentPage={setCurrentPage}
          cart={cart}
          setIsCartOpen={setIsCartOpen}
        />
      )}

      {/* Main content body */}
      <main className="flex-grow">
        {renderPage()}
      </main>

      {/* FOOTER - Minimal, elegant and respectful */}
      {!isSuccessPage && (
        <footer className="bg-neutral-50 border-t border-neutral-200 pb-16 pt-20">
          <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 grid grid-cols-1 md:grid-cols-12 gap-12">
            {/* Column 1 - Brand Summary */}
            <div className="md:col-span-5 space-y-2">
              <div className="w-full max-w-[560px] h-44 md:h-64 overflow-hidden flex items-center justify-start py-1">
                <img
                  src="https://hellokostek.pl/wp-content/uploads/2021/05/logo-animation-30fps-v-2.gif"
                  alt="hellokostek"
                  referrerPolicy="no-referrer"
                  className="max-w-full max-h-full object-contain mix-blend-multiply"
                />
              </div>
              <p className="font-sans text-xs sm:text-sm text-stone-500 max-w-md leading-relaxed">
                Pracownia Artystyczna <strong className="font-bold">hellokostek</strong> to miejsce, gdzie tradycyjne malarstwo olejne, rysunek i sztuka tworzona na podstawie zdjęć łączą się w harmonijną całość. Tworzę z myślą o tych, którzy cenią ciepło, miłość i rodzinne pamiątki.
              </p>
            </div>

            {/* Column 2 - Oferta & Odkryj (Stacked) */}
            <div className="md:col-span-3 space-y-10 font-sans text-xs sm:text-sm">
              {/* Oferta */}
              <div className="space-y-4">
                <span className="font-mono text-xs uppercase text-stone-400 tracking-wider block font-bold">Oferta</span>
                <ul className="space-y-2 text-stone-600">
                  <li>
                    <button 
                      onClick={() => { setCurrentPage("home"); window.scrollTo({ top: 0, behavior: "smooth" }); }} 
                      className="hover:text-magenta-accent transition-colors cursor-pointer text-left"
                    >
                      Portrety ze zdjęcia na zamówienie
                    </button>
                  </li>
                  <li>
                    <button 
                      onClick={() => { setCurrentPage("shop"); window.scrollTo({ top: 0, behavior: "smooth" }); }} 
                      className="hover:text-magenta-accent transition-colors cursor-pointer text-left"
                    >
                      Sklep z gotowymi dziełami
                    </button>
                  </li>
                  <li>
                    <button 
                      onClick={() => { setCurrentPage("gallery"); window.scrollTo({ top: 0, behavior: "smooth" }); }} 
                      className="hover:text-magenta-accent transition-colors cursor-pointer text-left"
                    >
                      Galeria portretów
                    </button>
                  </li>
                </ul>
              </div>

              {/* Odkryj */}
              <div className="space-y-4">
                <span className="font-mono text-xs uppercase text-stone-400 tracking-wider block font-bold">Odkryj</span>
                <ul className="space-y-2 text-stone-600">
                  <li>
                    <button 
                      onClick={() => { setCurrentPage("about"); window.scrollTo({ top: 0, behavior: "smooth" }); }} 
                      className="hover:text-magenta-accent transition-colors cursor-pointer text-left"
                    >
                      O mnie
                    </button>
                  </li>
                  <li>
                    <button 
                      onClick={() => { setCurrentPage("contact"); window.scrollTo({ top: 0, behavior: "smooth" }); }} 
                      className="hover:text-magenta-accent transition-colors cursor-pointer text-left"
                    >
                      Kontakt
                    </button>
                  </li>
                </ul>
              </div>
            </div>

            {/* Column 3 - Direct contact block details */}
            <div className="md:col-span-4 space-y-4 font-sans text-xs sm:text-sm">
              <span className="font-mono text-xs uppercase text-stone-400 tracking-wider block font-bold">Kontakt</span>
              <p className="text-stone-600 leading-normal">
                Masz pytania? Chcesz skonsultować kompozycję?<br />
                <a href="mailto:kontakt@hellokostek.pl" className="font-bold text-off-black hover:text-magenta-accent transition-colors block mt-2">
                  kontakt@hellokostek.pl
                </a>
                <a href="tel:+48662707153" className="font-bold text-off-black hover:text-magenta-accent transition-colors block mt-1">
                  tel. 662 707 153
                </a>
              </p>
              <div className="flex gap-3 pt-1">
                <a
                  href="https://www.instagram.com/hellokostek/"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-8 h-8 rounded-full bg-neutral-100 flex items-center justify-center text-stone-650 hover:bg-magenta-accent hover:text-white transition-all duration-300"
                  aria-label="Instagram"
                >
                  <Instagram className="w-4 h-4" />
                </a>
                <a
                  href="https://www.facebook.com/hellokostek/"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="w-8 h-8 rounded-full bg-neutral-100 flex items-center justify-center text-stone-650 hover:bg-[#1877F2] hover:text-white transition-all duration-300"
                  aria-label="Facebook"
                >
                  <Facebook className="w-4 h-4" />
                </a>
              </div>
              <div className="text-xs font-mono text-stone-400 mt-8 pt-4 border-t border-stone-200/40 space-y-1">
                <div className="font-bold text-xs uppercase tracking-wider text-stone-400">hellokostek Maciej Kosteczka</div>
                <div className="flex gap-2 font-bold">
                  <span>NIP: 625-236-36-56</span> • <span>REGON: 527158196</span>
                </div>
              </div>
            </div>
          </div>

          {/* Bottom Rights Bar */}
          <div className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 border-t border-stone-150 mt-12 pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-stone-400 font-sans text-center md:text-left">
            {/* Left: Realization */}
            <div className="order-3 md:order-1">
              <span>Realizacja: <a href="https://webisko.pl/" target="_blank" rel="noopener noreferrer" className="font-bold hover:text-magenta-accent transition-colors text-stone-500">Webisko.pl</a></span>
            </div>

            {/* Middle: Copyright */}
            <div className="order-1 md:order-2">
              <p>© {new Date().getFullYear()} hellokostek.pl. Wszelkie prawa zastrzeżone. Rękodzieło i malarstwo artystyczne.</p>
            </div>

            {/* Right: Formal Links */}
            <div className="order-2 md:order-3 flex gap-4">
              <button 
                onClick={() => { setCurrentPage("privacy"); window.scrollTo({ top: 0, behavior: "smooth" }); }} 
                className="hover:text-magenta-accent transition-colors cursor-pointer"
              >
                Polityka prywatności i plików cookies
              </button>
              <span>•</span>
              <button 
                onClick={() => { setCurrentPage("terms"); window.scrollTo({ top: 0, behavior: "smooth" }); }} 
                className="hover:text-magenta-accent transition-colors cursor-pointer"
              >
                Regulamin sklepu
              </button>
            </div>
          </div>
        </footer>
      )}

      {/* Floating Cart Badge disabled per user request */}

      {/* Subtle Back to Top Button */}
      {!isSuccessPage && showScrollTop && (
        <button
          onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
          className="fixed bottom-6 right-6 bg-white/90 backdrop-blur-md text-stone-600 border border-stone-200 p-3.5 rounded-full shadow-lg hover:border-magenta-accent hover:text-magenta-accent hover:scale-105 active:scale-95 transition-all duration-300 z-40 flex items-center justify-center cursor-pointer"
          aria-label="Cofnij do góry"
        >
          <ArrowUp className="w-5 h-5" />
        </button>
      )}

      {/* Cart Drawer Sliding Component disabled per user request */}

      {/* COOKIE CONSENT AGREEMENT (Requested: custom button colors - Magenta or Lime) */}
      {showCookies && (
        <div className="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:max-w-md bg-white border border-neutral-200 p-6 rounded-2xl shadow-xl z-50 animate-fadeIn font-sans space-y-4">
          <div className="flex items-start gap-3">
            <Info className="w-5 h-5 text-off-black shrink-0 mt-0.5" />
            <div className="text-xs text-stone-600 leading-relaxed">
              <strong className="text-off-black font-semibold block text-sm mb-1">Dbamy o Twoją prywatność</strong>
              Ta strona korzysta z plików cookies w celu zapewnienia prawidłowego działania koszyka zakupowego, bezpiecznych płatności Stripe oraz analizowania ruchu. Klikając „Zgadzam się”, pozwalasz nam na pełną dbałość o Twoje doświadczenia zakupowe.
            </div>
          </div>
          
          <div className="flex justify-between items-center gap-3">
            <button 
              onClick={() => { setCurrentPage("privacy"); window.scrollTo({ top: 0, behavior: "smooth" }); }} 
              className="text-xs text-stone-400 hover:text-magenta-accent hover:underline cursor-pointer text-left"
            >
              Więcej w polityce prywatności
            </button>
            <div className="flex gap-2">
              <button
                onClick={acceptCookies}
                className="px-4 py-2.5 bg-off-black text-white hover:bg-lime-accent hover:text-off-black active:bg-magenta-accent active:text-white transition-all duration-300 rounded-lg text-xs font-mono uppercase tracking-wider leading-none cursor-pointer"
              >
                Zgadzam się
              </button>
            </div>
          </div>
        </div>
      )}
      {/* SVG Goo Filters */}
      <svg xmlns="http://www.w3.org/2000/svg" version="1.1" style={{ display: "none" }}>
        <defs>
          <filter id="goo">
            <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur"></feGaussianBlur>
            <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7" result="goo"></feColorMatrix>
            <feBlend in="SourceGraphic" in2="goo"></feBlend>
          </filter>
        </defs>
      </svg>
    </div>
  );
}
