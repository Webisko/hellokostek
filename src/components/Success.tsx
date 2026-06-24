import { CheckCircle2, ChevronRight, Mail, CalendarCheck, Home, FileText } from "lucide-react";
import { PageId } from "../types";

interface SuccessProps {
  mode: "purchase" | "contact";
  setCurrentPage: (page: PageId) => void;
  clearCart?: () => void;
  orderDetails?: {
    orderNumber: string;
    productTitle: string;
    purchaseType: "original" | "print";
    price: number;
  } | null;
}

export default function Success({ mode, setCurrentPage, clearCart, orderDetails }: SuccessProps) {
  const isPurchase = mode === "purchase";

  return (
    <div className="min-h-[85vh] flex flex-col justify-center items-center">
      <div className="animate-fadeIn py-12 px-6 max-w-xl w-full text-center space-y-10">
      {/* Premium Visual Celebration Icon */}
      <div className="flex justify-center">
        <div className="w-20 h-20 rounded-full bg-green-50 flex items-center justify-center text-green-600 border border-green-100 animate-scaleIn">
          <CheckCircle2 className="w-10 h-10" />
        </div>
      </div>

      <div className="space-y-4">
        <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block">
          {isPurchase ? "Dziękujemy za zamówienie • hellokostek" : "Dziękujemy za zaufanie • hellokostek"}
        </span>
        <h1 className="font-display text-4.5xl text-gray-900 tracking-tight leading-none font-normal">
          {isPurchase ? "Płatność powiodła się!" : "Wiadomość wysłana!"}
        </h1>
        <p className="font-sans text-gray-600 text-base leading-relaxed">
          {isPurchase
            ? "Twoja transakcja została pomyślnie autoryzowana przez Stripe. Wirtualne zamówienie uwieczniające wyjątkowe kadry zostało przekazane do mojej Pracowni Artystycznej w Łodzi."
            : "Twój formularz kontaktowy został pomyślnie wysłany i dostarczony. Wkrótce zapoznam się z Twoim zapytaniem i wrócę z odpowiedzią."}
        </p>
      </div>

      {/* Symmetrical Informational Bullet Axis / Order Summary */}
      <div className="space-y-4">
        {isPurchase && orderDetails && (
          <div className="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-left space-y-3 font-sans text-gray-750">
            <div className="flex items-center gap-2 border-b border-gray-150 pb-3">
              <FileText className="w-4 h-4 text-[#E0115F]" />
              <span className="font-mono text-xs uppercase tracking-wider text-gray-400 font-bold">Podsumowanie transakcji</span>
            </div>
            <div className="grid grid-cols-2 gap-y-2 text-xs sm:text-sm">
              <span className="text-gray-500">Numer transakcji:</span>
              <strong className="text-gray-900 font-mono text-right">{orderDetails.orderNumber}</strong>

              <span className="text-gray-500">Zamówiona praca:</span>
              <strong className="text-gray-900 text-right truncate" title={orderDetails.productTitle}>{orderDetails.productTitle}</strong>

              <span className="text-gray-500">Typ zakupu:</span>
              <strong className="text-gray-900 text-right">
                {orderDetails.purchaseType === "original" ? "Oryginał" : "Reprodukcja (Wydruk)"}
              </strong>

              <span className="text-gray-500">Kwota płatności:</span>
              <strong className="text-magenta-accent font-bold font-mono text-right">{orderDetails.price} zł</strong>
            </div>
          </div>
        )}

        <div className="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-left space-y-4 text-xs sm:text-sm font-sans text-gray-700">
          <div className="flex gap-3">
            <Mail className="w-5 h-5 text-[#E0115F] shrink-0 mt-0.5" />
            <div>
              <strong className="text-gray-900 font-semibold block">Skrzynka Odbiorcza</strong>
              <span>
                {isPurchase
                  ? "Potwierdzenie zakupu wraz z podsumowaniem parametrów wysłaliśmy na Twój adres e-mail."
                  : "Potwierdzenie wysłania formularza wraz z kopią wiadomości zostało wysłane na podany adres e-mail."}
              </span>
            </div>
          </div>

          <div className="flex gap-3 border-t border-gray-150 pt-4">
            <CalendarCheck className="w-5 h-5 text-[#E0115F] shrink-0 mt-0.5" />
            <div>
              <strong className="text-gray-900 font-semibold block">
                {isPurchase ? "Czas realizacji i wysyłka" : "Czas realizacji i kontakt"}
              </strong>
              <span>
                {isPurchase
                  ? orderDetails?.purchaseType === "original"
                    ? "Wysyłka oryginalnego dzieła nastąpi w ciągu 2-3 dni roboczych. Otrzymasz osobną wiadomość z numerem śledzenia przesyłki kurierskiej."
                    : "Reprodukcje (wydruki artystyczne) przygotowujemy i wysyłamy w ciągu 3-5 dni roboczych. Jeśli to portret na zamówienie ze zdjęcia, cyfrowy projekt prześlę do konsultacji w ciągu 24-48 godzin."
                  : "Odpowiem na Twoje zapytanie najszybciej jak to możliwe – zazwyczaj w ciągu 24–48 godzin. Jeśli pytałeś o portret ze zdjęcia, prześlę też propozycję kompozycji w ciągu 24-48 godzin."}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Control Back Buttons */}
      <div className="flex flex-col sm:flex-row gap-4 pt-4 justify-center items-center">
        {/* Home Button (Primary pink style) */}
        <button
          onClick={() => {
            if (clearCart) clearCart();
            setCurrentPage("home");
          }}
          className="button flex-grow sm:flex-1"
        >
          <div className="button__blobs">
            <div></div>
            <div></div>
            <div></div>
          </div>
          <div className="button__text">
            <Home className="w-4 h-4" />
            Strona główna
          </div>
        </button>

        {/* Back to Shop Button (Secondary white style) */}
        <button
          onClick={() => setCurrentPage("shop")}
          className="button button--secondary flex-grow sm:flex-1"
        >
          <div className="button__blobs">
            <div></div>
            <div></div>
            <div></div>
          </div>
          <div className="button__text">
            Wróć do sklepu
            <ChevronRight className="w-4 h-4" />
          </div>
        </button>
      </div>
    </div>
  </div>
);
}
