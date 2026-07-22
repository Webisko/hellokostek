import React, { useState, useEffect } from "react";
import { HelpCircle, ChevronDown, ArrowRight } from "lucide-react";

interface FaqItem {
  id: number;
  question: string;
  answer: string;
  group_name?: string;
  sort_order?: number;
}

const DEFAULT_FAQ_ITEMS: FaqItem[] = [
  {
    id: 1,
    question: "Jak zamawiać obraz na indywidualne zamówienie lub portret ze zdjęcia?",
    answer: "<p>Wystarczy wypełnić <strong>formularz kontaktowy</strong> na stronie lub wysłać wiadomość ze zdjęciem referencyjnym. Po ustaleniu wymiaru, techniki (olej, akryl, akwarela, rysunek) oraz kompozycji, przygotuję dla Ciebie wycenę i prześlę wstępne propozycje.</p>",
    group_name: "Zamówienia indywidualne"
  },
  {
    id: 2,
    question: "Jak zabezpieczane są obrazy i rysunki podczas transportu?",
    answer: "<p>Każde dzieło traktuję wyjątkowo! Obrazy na płótnie pakuję w narożniki ochronne, folię bąbelkową i grube kartony malarskie. Akwarele i rysunki zabezpieczam na płasko przekładkami bezkwasowymi i sztywną tekturą, dzięki czemu przesyłka dociera w nienaruszonym stanie.</p>",
    group_name: "Wysyłka i opakowanie"
  },
  {
    id: 3,
    question: "Czy obrazy sprzedawane są z ramą?",
    answer: "<p>Większość obrazów olejnych i akrylowych na płótnie posiada estetycznie zamalowane krawędzie i jest <strong>gotowa do powieszenia</strong> bez dodatkowej ramy. W opisach poszczególnych prac zawsze znajdziesz dokładną informację o oprawie.</p>",
    group_name: "Oprawa i prezentacja"
  },
  {
    id: 4,
    question: "Jaki jest czas dostawy gotowych prac oraz zamówień dedykowanych?",
    answer: "<p>Prace z gotowej kolekcji wysyłam zazwyczaj w ciągu <strong>24–48 godzin</strong>. Czas wykonania portretu na zamówienie wynosi zazwyczaj od 7 do 14 dni roboczych, w zależności od wybranej techniki i czasochłonności.</p>",
    group_name: "Czas realizacji"
  },
  {
    id: 5,
    question: "Jak odpowiednio dbać o akwarele i obrazy olejne?",
    answer: "<p>Prace wykonane w technice akwarelowej warto oprawić za szkłem (najlepiej z passe-partout) i unikać eksponowania ich na bezpośrednie promienie słoneczne. Obrazy olejne i akrylowe są zabezpieczone profesjonalnym werniksem – do ich pielęgnacji wystarczy sucha, miękka szmatka z mikrofibry.</p>",
    group_name: "Pielęgnacja"
  }
];

export default function FaqSection() {
  const [items, setItems] = useState<FaqItem[]>(DEFAULT_FAQ_ITEMS);
  const [openId, setOpenId] = useState<number | null>(1);

  useEffect(() => {
    const apiBase = import.meta.env.PUBLIC_API_URL || "http://localhost:8000/api";
    fetch(`${apiBase}/faq`)
      .then((res) => {
        if (!res.ok) throw new Error("Network response failed");
        return res.json();
      })
      .then((payload) => {
        const fetchedItems = payload.data?.items;
        if (Array.isArray(fetchedItems) && fetchedItems.length > 0) {
          setItems(fetchedItems);
          setOpenId(fetchedItems[0].id);
        }
      })
      .catch((err) => {
        console.warn("Could not fetch FAQ from CMS API, using fallback FAQ items:", err);
      });
  }, []);

  const toggleAccordion = (id: number) => {
    setOpenId((prev) => (prev === id ? null : id));
  };

  return (
    <section id="faq" className="py-16 md:py-24 bg-white border-t border-gray-100 relative overflow-hidden">
      <div className="content-container max-w-4xl mx-auto space-y-12">
        {/* Header */}
        <div className="text-center space-y-3 max-w-4xl mx-auto">
          <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-bold block mb-3">
            Pytania i odpowiedzi
          </span>
          <h2 className="font-display text-4xl sm:text-5xl text-gray-900 font-normal tracking-tight whitespace-nowrap sm:whitespace-normal">
            Często zadawane pytania
          </h2>
          <p className="font-sans text-gray-600 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">
            Masz pytania dotyczące procesu zamawiania, czasu realizacji lub wysyłki? Znajdziesz tu najważniejsze informacje.
          </p>
        </div>

        {/* Accordion List */}
        <div className="space-y-4">
          {items.map((item) => {
            const isOpen = openId === item.id;
            return (
              <div
                key={item.id}
                className={`border rounded-2xl transition-all duration-300 overflow-hidden ${
                  isOpen
                    ? "border-stone-300 bg-stone-50/50 shadow-sm"
                    : "border-gray-200/80 bg-white hover:border-stone-300 hover:bg-stone-50/30"
                }`}
              >
                <button
                  onClick={() => toggleAccordion(item.id)}
                  className="w-full text-left p-6 flex items-center justify-between gap-4 cursor-pointer focus:outline-none group"
                  aria-expanded={isOpen}
                >
                  <div className="flex items-center gap-3">
                    <HelpCircle className={`w-5 h-5 shrink-0 transition-colors duration-200 ${
                      isOpen
                        ? "text-[#E0115F]"
                        : "text-stone-400 group-hover:text-[#C4F013]"
                    }`} />
                    <span className="font-display text-lg font-semibold text-gray-900 leading-snug">
                      {item.question}
                    </span>
                  </div>
                  <ChevronDown
                    className={`w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300 ${
                      isOpen ? "rotate-180 text-[#E0115F]" : "group-hover:text-gray-600"
                    }`}
                  />
                </button>

                {isOpen && (
                  <div className="px-6 pb-6 pt-0 animate-fadeIn">
                    <div
                      className="font-sans text-sm sm:text-base text-gray-700 leading-relaxed border-t border-stone-200/60 pt-4 [&>p]:mb-2 [&>ul]:list-disc [&>ul]:pl-5 [&>ol]:list-decimal [&>ol]:pl-5 [&>a]:text-[#E0115F] [&>a]:underline"
                      dangerouslySetInnerHTML={{ __html: item.answer }}
                    />
                  </div>
                )}
              </div>
            );
          })}
        </div>

        {/* Sekcja CTA na dole */}
        <section className="bg-stone-50 rounded-[32px] p-8 sm:p-12 border border-neutral-100 flex flex-col md:flex-row items-center justify-between gap-8 mt-16">
          <div className="space-y-2 text-center md:text-left flex-1">
            <h3 className="font-display text-3xl text-gray-900 font-normal">
              Nie znalazłaś/eś odpowiedzi na swoje pytanie?
            </h3>
            <p className="text-stone-600 text-sm sm:text-base leading-relaxed">
              Napisz do mnie bezpośrednio – z chęcią pomogę i doradzę.
            </p>
          </div>
          <a
            href="/hellokostek/kontakt"
            className="button shrink-0 text-center cursor-pointer"
          >
            <div className="button__blobs">
              <div></div>
              <div></div>
              <div></div>
            </div>
            <div className="button__text">
              <span>Napisz do mnie</span> <ArrowRight className="w-4 h-4 ml-1.5" />
            </div>
          </a>
        </section>
      </div>
    </section>
  );
}
