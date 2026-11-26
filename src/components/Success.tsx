import React, { useState, useEffect } from "react";
import { CheckCircle2, ChevronRight, Mail, CalendarCheck, Home, FileText, AlertTriangle, Clock } from "lucide-react";
import { clearCart } from "../stores/cartStore";

function isSafeRedirectUrl(url: string): boolean {
  if (!url || typeof url !== "string") return false;
  const trimmed = url.trim();
  if (trimmed.startsWith("/") && !trimmed.startsWith("//") && !trimmed.startsWith("/\\")) {
    return true;
  }
  try {
    const parsed = new URL(trimmed);
    return parsed.protocol === "https:" || (typeof window !== "undefined" && parsed.origin === window.location.origin);
  } catch {
    return false;
  }
}

interface SuccessProps {
  mode: "purchase" | "contact";
  orderDetails?: OrderDetails | null;
}

interface OrderDetails {
  orderNumber: string;
  token?: string;
  productTitle: string;
  purchaseType: "original" | "print";
  price: number;
  shippingMethod?: string;
  shippingPrice?: number;
  isTransfer?: boolean;
  paymentPending?: boolean;
  stripeStatus?: string | null;
  email?: string;
}

export default function Success({ mode, orderDetails: initialOrderDetails }: SuccessProps) {
  const isPurchase = mode === "purchase";
  const [orderDetails, setOrderDetails] = useState<OrderDetails | null>(initialOrderDetails || null);
  const [retryPaymentUrl, setRetryPaymentUrl] = useState<string | null>(null);
  const [isRetryingPayment, setIsRetryingPayment] = useState(false);
  const [reservationLabel, setReservationLabel] = useState("3 dni robocze");
  const [realPaymentStatus, setRealPaymentStatus] = useState<string | null>(null);
  const [isVerifyingOrder, setIsVerifyingOrder] = useState(false);

  useEffect(() => {
    if (isPurchase) {
      clearCart();

      const apiBase = import.meta.env.PUBLIC_API_URL || "https://panel.hellokostek.pl/api";
      fetch(`${apiBase}/store/settings`)
        .then((res) => (res.ok ? res.json() : null))
        .then((data) => {
          if (data?.bank_transfer_reservation_label) {
            setReservationLabel(data.bank_transfer_reservation_label);
          }
        })
        .catch(() => {});

      if (typeof window !== "undefined") {
        const params = new URLSearchParams(window.location.search);
        const orderNumber = params.get("orderNumber") || params.get("number");
        const token = params.get("token") || undefined;
        const productTitle = params.get("productTitle");
        const purchaseType = (params.get("purchaseType") as "original" | "print" | null) || "original";
        const price = parseFloat(params.get("price") || "0");
        const shippingMethod = params.get("shippingMethod") || undefined;
        const shippingPrice = params.get("shippingPrice") !== null ? parseFloat(params.get("shippingPrice") || "0") : undefined;
        const isTransfer = params.get("isTransfer") === "true";
        const paymentPending = params.get("paymentPending") === "true";
        const stripeStatus = params.get("stripe_status");
        const email = params.get("email") || undefined;

        if (orderNumber) {
          setOrderDetails({
            orderNumber,
            token,
            productTitle: productTitle || "Zamówiona praca",
            purchaseType,
            price,
            shippingMethod,
            shippingPrice,
            isTransfer,
            paymentPending,
            stripeStatus,
            email,
          });
        }
      }
    }
  }, [isPurchase]);

  const basePath = "";

  const isBackendPending = realPaymentStatus === "pending" || realPaymentStatus === "awaiting_payment";
  const isFailed = isPurchase && (
    orderDetails?.stripeStatus === "cancelled" ||
    realPaymentStatus === "failed" ||
    realPaymentStatus === "cancelled"
  );
  const isCancelled = isFailed;
  const isTransfer = isPurchase && orderDetails?.isTransfer;
  const isPaid = isPurchase && realPaymentStatus === "paid";
  const isPending = isPurchase && !isFailed && !isTransfer && !isPaid && (
    isBackendPending ||
    orderDetails?.paymentPending ||
    isVerifyingOrder ||
    realPaymentStatus === null
  );

  useEffect(() => {
    if (!isPurchase || !orderDetails?.orderNumber) return;

    let isSubscribed = true;
    let pollCount = 0;
    const maxPolls = 5;
    let pollTimer: ReturnType<typeof setTimeout> | undefined;

    const verifyOrder = async () => {
      try {
        setIsVerifyingOrder(true);
        const apiBase = import.meta.env.PUBLIC_API_URL || "https://panel.hellokostek.pl/api";
        const headers: Record<string, string> = { Accept: "application/json" };
        if (orderDetails.token) {
          headers["X-Order-Token"] = orderDetails.token;
        } else if (orderDetails.email) {
          headers["X-Order-Email"] = orderDetails.email;
        }

        const res = await fetch(
          `${apiBase}/checkout/orders/${encodeURIComponent(orderDetails.orderNumber)}`,
          { headers }
        );
        if (!res.ok) return;
        const data = await res.json();
        if (!isSubscribed) return;

        const order = data?.data?.order;
        const items = data?.data?.items;
        if (order) {
          if (order.payment_status) {
            setRealPaymentStatus(order.payment_status);
          }

          setOrderDetails((prev) => {
            if (!prev) return prev;
            const firstItem = Array.isArray(items) && items.length > 0 ? items[0] : null;
            const backendShippingPrice = typeof order.shipping_amount === "number" ? order.shipping_amount / 100 : prev.shippingPrice;
            const backendItemsPrice = typeof order.total_amount === "number" && typeof order.shipping_amount === "number"
              ? Math.max(0, (order.total_amount - order.shipping_amount) / 100)
              : prev.price;

            return {
              ...prev,
              productTitle: firstItem?.name || prev.productTitle,
              price: backendItemsPrice,
              shippingMethod: order.shipping_method_name || prev.shippingMethod,
              shippingPrice: backendShippingPrice,
            };
          });
        }

        const redirectUrl = data?.data?.payment_session?.redirect_url;
        if (redirectUrl) {
          setRetryPaymentUrl(redirectUrl);
        }

        if (
          (order?.payment_status === "pending" || order?.payment_status === "awaiting_payment") &&
          !orderDetails.isTransfer &&
          pollCount < maxPolls
        ) {
          pollCount++;
          pollTimer = setTimeout(verifyOrder, 3000);
        }
      } catch (err) {
        console.warn("Could not verify order status:", err);
      } finally {
        if (isSubscribed) {
          setIsVerifyingOrder(false);
        }
      }
    };

    verifyOrder();

    return () => {
      isSubscribed = false;
      if (pollTimer) clearTimeout(pollTimer);
    };
  }, [isPurchase, orderDetails?.orderNumber, orderDetails?.token, orderDetails?.email, orderDetails?.isTransfer]);

  const handleRetryPayment = async () => {
    if (retryPaymentUrl && isSafeRedirectUrl(retryPaymentUrl)) {
      window.location.href = retryPaymentUrl;
      return;
    }
    if (!orderDetails?.orderNumber) return;
    setIsRetryingPayment(true);
    try {
      const apiBase = import.meta.env.PUBLIC_API_URL || "https://panel.hellokostek.pl/api";
      const headers: Record<string, string> = {
        "Content-Type": "application/json",
        Accept: "application/json",
      };
      if (orderDetails.token) {
        headers["X-Order-Token"] = orderDetails.token;
      }

      const res = await fetch(
        `${apiBase}/checkout/orders/${encodeURIComponent(orderDetails.orderNumber)}/payment-session`,
        {
          method: "POST",
          headers,
          body: JSON.stringify({
            token: orderDetails.token,
            email: orderDetails.email,
          }),
        }
      );
      const data = await res.json();
      const redirectUrl = data?.data?.payment_session?.redirect_url;
      if (redirectUrl && isSafeRedirectUrl(redirectUrl)) {
        window.location.href = redirectUrl;
      }
    } catch (err) {
      console.error(err);
    } finally {
      setIsRetryingPayment(false);
    }
  };

  const renderHeading = () => {
    if (!isPurchase) return "Wiadomość wysłana!";
    if (isFailed) return "Płatność została przerwana";
    if (isTransfer) return "Zamówienie zostało przyjęte!";
    if (isPending) return "Oczekiwanie na potwierdzenie płatności";
    return "Płatność powiodła się!";
  };

  const renderDescription = () => {
    if (!isPurchase) {
      return "Twój formularz kontaktowy został pomyślnie wysłany i dostarczony. Wkrótce zapoznam się z Twoim zapytaniem i wrócę z odpowiedzią.";
    }
    if (isFailed) {
      return "Twoje zamówienie zostało zapisane w systemie, jednak płatność nie została sfinalizowana. Nie musisz składać zamówienia ponownie – poniżej oraz w wiadomości e-mail znajdziesz bezpośredni link do dokończenia płatności.";
    }
    if (isTransfer) {
      return `Twoje zamówienie zostało przekazane do Pracowni Artystycznej hellokostek. Dane do przelewu tradycyjnego znajdziesz poniżej oraz w wiadomości potwierdzającej na Twoim adresie e-mail. Wybraną pracę rezerwujemy dla Ciebie na ${reservationLabel}.`;
    }
    if (isPending) {
      return "Twoje zamówienie zostało zarejestrowane. Trwa weryfikacja płatności w bramce płatniczej. Jeśli nie dokończyłeś płatności w poprzednim oknie, możesz wrócić do bramki przyciskiem poniżej.";
    }
    return "Twoja transakcja została pomyślnie autoryzowana. Zamówienie zostało przekazane do realizacji w Pracowni Artystycznej hellokostek.";
  };

  return (
    <div className="min-h-screen flex flex-col justify-center items-center">
      <div className="animate-fadeIn py-12 px-6 max-w-xl w-full text-center space-y-10">
        {/* Visual Status Icon */}
        <div className="flex justify-center">
          <div className={`w-20 h-20 rounded-full flex items-center justify-center border animate-scaleIn ${
            isFailed
              ? "bg-amber-50 text-amber-600 border-amber-200"
              : isPending && !isTransfer
              ? "bg-blue-50 text-blue-600 border-blue-200"
              : "bg-green-50 text-green-600 border-green-100"
          }`}>
            {isFailed ? (
              <AlertTriangle className="w-10 h-10" />
            ) : isPending && !isTransfer ? (
              <Clock className="w-10 h-10 animate-pulse" />
            ) : (
              <CheckCircle2 className="w-10 h-10" />
            )}
          </div>
        </div>

        <div className="space-y-4">
          <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block">
            {isPurchase ? "Dziękujemy za zamówienie • hellokostek" : "Dziękujemy za zaufanie • hellokostek"}
          </span>
          <h1 className="font-display text-4.5xl text-gray-900 tracking-tight leading-none font-normal">
            {renderHeading()}
          </h1>
          <p className="font-sans text-gray-600 text-base leading-relaxed">
            {renderDescription()}
          </p>
        </div>

        {/* Order Summary & Actions */}
        <div className="space-y-4">
          {(isCancelled || (isPending && !isVerifyingOrder)) && (
            <div className="bg-amber-50/90 rounded-2xl p-6 border border-amber-200 text-left space-y-4 font-sans text-gray-800">
              <div>
                <strong className="font-mono text-xs uppercase tracking-wider text-amber-900 font-bold block mb-1">
                  {isCancelled ? "Jak dokończyć płatność?" : "Płatność w trakcie lub niedokończona?"}
                </strong>
                <p className="text-xs sm:text-sm leading-relaxed text-amber-950">
                  <strong>Nie musisz składać tego samego zamówienia ponownie.</strong> Możesz przejść lub wrócić do bezpiecznej płatności online, klikając przycisk poniżej.
                </p>
              </div>

              <div>
                <button
                  type="button"
                  onClick={handleRetryPayment}
                  disabled={isRetryingPayment}
                  className="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3.5 bg-[#181716] hover:bg-[#E0115F] active:bg-black text-white font-semibold text-sm rounded-xl transition-all shadow-md cursor-pointer disabled:opacity-50"
                >
                  {isRetryingPayment ? "Otwieranie bramki płatności..." : "Przejdź do płatności online →"}
                </button>
              </div>
            </div>
          )}

          {isPurchase && orderDetails && (
            <div className="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-left space-y-3 font-sans text-gray-750">
              <div className="flex items-center gap-2 border-b border-gray-150 pb-3">
                <FileText className="w-4 h-4 text-[#E0115F]" />
                <span className="font-mono text-xs uppercase tracking-wider text-gray-600 font-bold">Podsumowanie zamówienia</span>
              </div>
              <div className="grid grid-cols-2 gap-y-2 text-xs sm:text-sm">
                <span className="text-gray-600">Numer zamówienia:</span>
                <strong className="text-gray-900 font-mono text-right">{orderDetails.orderNumber}</strong>

                <span className="text-gray-600">Zamówiona praca:</span>
                <strong className="text-gray-900 text-right">{orderDetails.productTitle}</strong>

                <span className="text-gray-600">Typ zakupu:</span>
                <strong className="text-gray-900 text-right">
                  {orderDetails.purchaseType === "original" ? "Oryginał" : "Wydruk"}
                </strong>

                <span className="text-gray-600">Cena pracy:</span>
                <strong className="text-gray-900 font-mono text-right">{orderDetails.price} zł</strong>

                {orderDetails.shippingMethod && (
                  <>
                    <span className="text-gray-600">Opcja dostawy:</span>
                    <strong className="text-gray-900 text-right text-xs">
                      {orderDetails.shippingMethod} ({orderDetails.shippingPrice === 0 ? "Bezpłatnie" : `${orderDetails.shippingPrice} zł`})
                    </strong>
                  </>
                )}

                <span className="text-gray-900 border-t border-gray-200/60 pt-2 font-semibold flex items-center">Razem (z dostawą):</span>
                <strong className="text-magenta-accent font-bold font-mono text-right border-t border-gray-200/60 pt-2 text-base">
                  {orderDetails.price + (orderDetails.shippingPrice || 0)} zł
                </strong>
              </div>
            </div>
          )}

          {isTransfer && orderDetails && (
            <div className="bg-rose-50/70 rounded-2xl p-6 border border-rose-200 text-left space-y-3 font-sans text-gray-800">
              <div className="flex items-center gap-2 border-b border-rose-200/70 pb-3">
                <span className="font-mono text-xs uppercase tracking-wider text-[#E0115F] font-bold">Dane do przelewu tradycyjnego</span>
              </div>
              <div className="space-y-1.5 text-xs sm:text-sm font-mono">
                <div>
                  <span className="text-gray-600 font-sans">Odbiorca: </span>
                  <strong className="text-gray-900">hellokostek Maciej Kosteczka</strong>
                </div>
                <div>
                  <span className="text-gray-600 font-sans">Adres: </span>
                  <strong className="text-gray-900">Rynek 33, 42-470 Siewierz</strong>
                </div>
                <div>
                  <span className="text-gray-600 font-sans">Konto firmowe IBAN: </span>
                  <strong className="text-gray-900 select-all">PL 74 1050 1227 1000 0090 8312 8604</strong>
                </div>
                <div>
                  <span className="text-gray-600 font-sans">Tytuł przelewu: </span>
                  <strong className="text-gray-900 select-all">{orderDetails.orderNumber} – {orderDetails.productTitle}</strong>
                </div>
                <div>
                  <span className="text-gray-600 font-sans">Kwota: </span>
                  <strong className="text-[#E0115F]">{orderDetails.price + (orderDetails.shippingPrice || 0)} zł</strong>
                </div>
              </div>
            </div>
          )}

          <div className="bg-gray-50 rounded-2xl p-6 border border-gray-100 text-left space-y-4 text-xs sm:text-sm font-sans text-gray-700">
            <div className="flex gap-3">
              <Mail className="w-5 h-5 text-[#E0115F] shrink-0 mt-0.5" />
              <div>
                <strong className="text-gray-900 font-semibold block">Skrzynka odbiorcza</strong>
                <span>
                  {isCancelled
                    ? "Wysłaliśmy na Twój e-mail podsumowanie zamówienia wraz z bezpośrednim przyciskiem do dokończenia płatności online."
                    : isPurchase
                    ? "Potwierdzenie zamówienia wraz z podsumowaniem parametrów wysłaliśmy na Twój adres e-mail."
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
                      ? "Wysyłka oryginalnego dzieła nastąpi w ciągu 2–3 dni roboczych. Otrzymasz osobną wiadomość z numerem śledzenia przesyłki kurierskiej."
                      : "Reprodukcje (wydruki artystyczne) przygotowujemy i wysyłamy w ciągu 3–5 dni roboczych."
                    : "Odpowiem na Twoje zapytanie najszybciej jak to możliwe – zazwyczaj w ciągu 24–48 godzin. Jeśli pytałeś o portret ze zdjęcia, prześlę też propozycję kompozycji."}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Control Back Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 pt-4 justify-center items-center">
          <a
            href={`${basePath}/`}
            className="button flex-grow sm:flex-1 text-center"
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
          </a>

          <a
            href={`${basePath}/sklep`}
            className="button button--secondary flex-grow sm:flex-1 text-center"
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
          </a>
        </div>
      </div>
    </div>
  );
}
