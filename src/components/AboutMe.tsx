import React, { useState, useEffect } from "react";
import { ArrowRight } from "lucide-react";

const DEFAULT_SUBTITLE = "Aktor • Tancerz • Malarz • Rocznik ‘88";
const DEFAULT_TITLE = "Kostek Maciej Kosteczka";
const DEFAULT_QUOTE =
  "„Wydawać się może, że klasyczne malarstwo i sztuka figuratywna bardzo ograniczają twórcę, ale stosując zasady z improwizacji w teatrze, że: „gdzie są ograniczenia, tam jest najwięcej wolności”, szukam wolności w malarstwie realistycznym.”";
const DEFAULT_BOX_TITLE = "Dlaczego malarstwo tradycyjne?";
const DEFAULT_BOX_TEXT =
  "Wierzę, że w dobie sztucznej inteligencji i fotografii cyfrowej to właśnie tradycyjne malarstwo i sztuka użytkowa mają największą wartość sentymentalną. W końcu za każdym stworzonym przeze mnie dziełem stoją godziny pracy nad detalami i serce włożone w proces twórczy.";
const DEFAULT_CONTENT = [
  "Ukończyłem Państwową Wyższą Szkołę Teatralną w Krakowie na Wydziale Teatru Tańca w Bytomiu oraz kulturoznawstwo na Uniwersytecie Śląskim w Katowicach. Byłem stypendystą Roberta Wilsona w Watermill Center w Nowym Jorku (International Summer Program). Pracowałem w Teatrze Tańca i Ruchu Rozbark, brałem udział w projekcie „Ksenofonia Symfonia dla Innego” w ramach Festiwalu Malta oraz współtworzyłem spektakl „A co gdyby” na rezydencji Gdańskiego Festiwalu Tańca. Od lat współpracuję z Teatrem w Ruchu, grając w spektaklach dla dzieci w całym kraju. Mam też za sobą doświadczenie pracy w korporacji.",
  "Równolegle rozwijam się jako malarz, grafik i twórca rękodzieła – od 2023 roku prowadzę własną działalność: hellokostek, tworząc obrazy olejne na zamówienie oraz ucząc aktorstwa i rysunku. Od 2020 roku doskonalę warsztat pod okiem Pauliny Kwietniewskiej (malarstwo) i Macieja Kusa (rzeźba).",
  "Wystawy indywidualne: „SIEWIERZ-513W13R2”, 2017, Hotel Natura Residence, Siewierz; „Rogue Planet”, 2022, Klubokawiarnia KIPISZ, Łódź.",
  "Wystawy zbiorowe: „Spotkanie na czerwonej kanapie - jak rzeźba wrocławska czuje się w Łodzi”, 2025, OFF Piotrkowska, Łódź.",
  "Moje prace są dostępne w galerii Two Of Art w Amsterdamie oraz na aukcjach organizowanych przez galerię Pragaleria w Warszawie.",
].join("\n\n");
const DEFAULT_IMAGE = "/images/o_mnie_nowe.webp";
const DEFAULT_CTA_TITLE = "Podoba Ci się moja twórczość?";
const DEFAULT_CTA_TEXT =
  "Zamów swój własny, unikalny portret ze zdjęcia lub zapoznaj się z gotowymi dziełami malarskimi dostępnymi w moim sklepie.";

function formatAboutTitle(rawTitle: string) {
  const trimmed = rawTitle.trim();
  const match = trimmed.match(/^(kostek)(?:\s*(?:[—–\-]\s*)?|\s+)(maciej\s+kosteczka)$/i);
  if (match) {
    return (
      <>
        <span className="text-gray-900 font-normal">Kostek</span>{" "}
        <span className="text-[#E0115F] font-semibold">Maciej Kosteczka</span>
      </>
    );
  }
  return rawTitle;
}

export default function AboutMe() {
  const [subtitle, setSubtitle] = useState<string>(DEFAULT_SUBTITLE);
  const [title, setTitle] = useState<string>(DEFAULT_TITLE);
  const [quote, setQuote] = useState<string>(DEFAULT_QUOTE);
  const [boxTitle, setBoxTitle] = useState<string>(DEFAULT_BOX_TITLE);
  const [boxText, setBoxText] = useState<string>(DEFAULT_BOX_TEXT);
  const [ctaTitle, setCtaTitle] = useState<string>(DEFAULT_CTA_TITLE);
  const [ctaText, setCtaText] = useState<string>(DEFAULT_CTA_TEXT);
  const [content, setContent] = useState<string>(DEFAULT_CONTENT);
  const [imageUrl, setImageUrl] = useState<string>(DEFAULT_IMAGE);

  useEffect(() => {
    const apiBase = import.meta.env.PUBLIC_API_URL || "http://localhost:8000/api";
    fetch(`${apiBase}/content/pages/o-mnie`)
      .then((res) => {
        if (!res.ok) throw new Error("API network error");
        return res.json();
      })
      .then((payload) => {
        const page = payload.data?.page;
        if (page) {
          if (page.metadata?.subtitle) {
            setSubtitle(page.metadata.subtitle);
          }
          if (page.title) {
            setTitle(page.title);
          }
          if (typeof page.excerpt === "string" && page.excerpt.trim() !== "" && !page.excerpt.includes("Wierzę, że w dobie")) {
            setQuote(page.excerpt.trim());
          } else if (page.metadata?.quote) {
            setQuote(page.metadata.quote);
          }
          if (page.metadata?.box_title) {
            setBoxTitle(page.metadata.box_title);
          }
          if (page.metadata?.box_text) {
            setBoxText(page.metadata.box_text);
          } else if (typeof page.excerpt === "string" && page.excerpt.includes("Wierzę, że w dobie")) {
            setBoxText(page.excerpt.trim());
          }
          if (page.metadata?.cta_title) {
            setCtaTitle(page.metadata.cta_title);
          }
          if (page.metadata?.cta_text) {
            setCtaText(page.metadata.cta_text);
          }
          if (typeof page.content === "string" && page.content.trim() !== "") {
            setContent(page.content.trim());
          }
          if (page.hero_image_url) {
            let resolvedImg = page.hero_image_url;
            if (typeof resolvedImg === "string" && resolvedImg.includes("/storage/images/")) {
              resolvedImg = resolvedImg.replace("/storage/images/", "/images/");
            }
            setImageUrl(resolvedImg);
          }
        }
      })
      .catch(() => {
        // Fallback to static content seamlessly
      });
  }, []);

  const isHtmlContent = /<\/?[a-z][\s\S]*>/i.test(content);
  const paragraphs = !isHtmlContent
    ? content.split(/\n\s*\n/).map((p) => p.trim()).filter(Boolean)
    : [];

  const basePath = "";
  return (
    <div className="animate-fadeIn pt-5 sm:pt-8 md:pt-14 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 space-y-12 md:space-y-24 lg:space-y-20 xl:space-y-16 2xl:space-y-24">
      {/* Asymmetric Section 1: Biography vs Workspace Photography */}
      <section className="content-container grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-16 items-start">
        {/* Left Column: Storytelling Text (Asymmetric span) */}
        <div className="lg:col-span-7">
          {/* Editorial Magazine Hero Header */}
          <div className="border-b border-gray-100 pb-7 mb-7 lg:pb-12 lg:mb-12">
            {subtitle && (
              <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block mb-3 sm:mb-4">
                {subtitle}
              </span>
            )}
            <h1 className="font-display text-[1.45rem] min-[375px]:text-[1.65rem] min-[410px]:text-[1.85rem] sm:text-4xl md:text-[2.6rem] lg:text-[2.5rem] xl:text-[3.15rem] 2xl:text-[3.5rem] text-gray-900 tracking-tight leading-tight font-normal mb-5 sm:mb-8 whitespace-nowrap overflow-hidden text-ellipsis">
              {formatAboutTitle(title)}
            </h1>
            {quote && (
              <p className="font-sans italic text-gray-650 text-lg sm:text-xl pl-5 sm:pl-6 border-l-4 border-[#E0115F] leading-relaxed">
                {quote}
              </p>
            )}
          </div>

          {/* Mobile/Tablet Portrait Photo (placed between quote and biography on < lg) */}
          <div className="lg:hidden mb-8 aspect-[3/4] rounded-[28px] sm:rounded-[32px] overflow-hidden shadow-sm border border-gray-100 bg-gray-50 relative group">
            <img
              src={imageUrl}
              alt={`${title}, Pracownia Artystyczna Łódź`}
              referrerPolicy="no-referrer"
              onError={(e) => {
                const target = e.currentTarget;
                if (target.src !== window.location.origin + DEFAULT_IMAGE) {
                  target.src = DEFAULT_IMAGE;
                }
              }}
              className="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
            />
          </div>

          {isHtmlContent ? (
            <div
              className="space-y-6 font-sans text-base text-gray-750 leading-relaxed"
              dangerouslySetInnerHTML={{ __html: content }}
            />
          ) : (
            <div className="space-y-6 font-sans text-base text-gray-750 leading-relaxed">
              {paragraphs.map((paragraph, idx) => (
                <p
                  key={idx}
                  className="leading-relaxed whitespace-pre-line"
                >
                  {paragraph}
                </p>
              ))}
            </div>
          )}
        </div>

        {/* Right Column: Dynamic Workspace Image Framing (Asymmetric span) */}
        <div className="lg:col-span-5 space-y-8">
          <div className="hidden lg:block aspect-[3/4] rounded-[32px] overflow-hidden shadow-sm border border-gray-100 bg-gray-50 relative group">
            <img
              src={imageUrl}
              alt={`${title}, Pracownia Artystyczna Łódź`}
              referrerPolicy="no-referrer"
              onError={(e) => {
                const target = e.currentTarget;
                if (target.src !== window.location.origin + DEFAULT_IMAGE) {
                  target.src = DEFAULT_IMAGE;
                }
              }}
              className="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
            />
          </div>

          {/* Boks wyróżniony pod zdjęciem: Dlaczego malarstwo tradycyjne? */}
          {boxText && (
            <div className="bg-stone-50 rounded-[28px] p-7 border border-neutral-100 space-y-3 font-sans shadow-xs">
              {boxTitle && (
                <h3 className="font-display text-xl sm:text-2xl text-gray-950 font-normal">
                  {boxTitle}
                </h3>
              )}
              <p className="text-sm sm:text-base text-gray-650 leading-relaxed">
                {boxText}
              </p>
            </div>
          )}
        </div>
      </section>

      {/* bottom CTA card */}
      <section className="content-container mt-20">
        <div className="bg-stone-50 rounded-[32px] p-8 sm:p-12 border border-neutral-100 flex flex-col lg:flex-row items-center justify-between gap-8">
          <div className="space-y-2 text-center lg:text-left max-w-2xl">
            <h2 className="font-display text-3xl text-gray-900 font-normal">
              {ctaTitle}
            </h2>
            <p className="text-stone-600 text-sm sm:text-base leading-relaxed">
              {ctaText}
            </p>
          </div>
          <div className="flex flex-col xl:flex-row gap-4 shrink-0 w-full sm:w-auto">
            <a
              href={`${basePath}/#kontakt-sekcja`}
              className="button shrink-0 text-center cursor-pointer"
            >
              <div className="button__blobs">
                <div></div>
                <div></div>
                <div></div>
              </div>
              <div className="button__text font-bold">
                Zamów swój portret
                <ArrowRight className="w-4 h-4 ml-1.5" />
              </div>
            </a>
            <a
              href={`${basePath}/sklep`}
              className="button button--secondary shrink-0 text-center cursor-pointer"
            >
              <div className="button__blobs">
                <div></div>
                <div></div>
                <div></div>
              </div>
              <div className="button__text font-bold">
                Przejdź do sklepu
                <ArrowRight className="w-4 h-4 ml-1.5" />
              </div>
            </a>
          </div>
        </div>
      </section>
    </div>
  );
}
