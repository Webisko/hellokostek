import { ArrowRight } from "lucide-react";

const maciejImg = "/hellokostek/images/o_mnie_nowe.webp";

export default function AboutMe() {
  const basePath = "/hellokostek";
  return (
    <div className="animate-fadeIn pt-12 md:pt-20 lg:pt-16 xl:pt-12 2xl:pt-20 pb-16 space-y-16 md:space-y-24 lg:space-y-20 xl:space-y-16 2xl:space-y-24">
      {/* Asymmetric Section 1: Biography vs Workspace Photography */}
      <section className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
        {/* Left Column: Storytelling Text (Asymmetric span) */}
        <div className="lg:col-span-7">
          {/* Editorial Magazine Hero Header */}
          <div className="border-b border-gray-100 pb-12 mb-12">
            <span className="font-mono text-xs uppercase tracking-widest text-[#E0115F] font-semibold block mb-4">
              Artysta • Malarz • Łódź
            </span>
            <h1 className="font-display text-5xl md:text-6xl lg:text-7xl text-gray-900 tracking-tight leading-[1.05] font-normal mb-8">
              Kostek Maciej Kosteczka
            </h1>
            <p className="font-sans italic text-gray-650 text-xl sm:text-2xl pl-6 border-l-4 border-[#E0115F] leading-relaxed">
              „Teatr daje możliwość przeżycia czegoś niepowtarzalnego tu i teraz, a malarstwo pozwala zatrzymać tę ulotną chwilę na zawsze.”
            </p>
          </div>

          <div className="space-y-8 font-sans text-base text-gray-750 leading-relaxed">
            <p className="text-lg font-medium text-gray-950 leading-relaxed">
              Teatr i malarstwo to dwie pasje, które od zawsze się we mnie przenikają. Przez lata pracy na scenie teatralnej nauczyłem się patrzeć na człowieka – na jego emocje, gesty i światło, które go otacza.
            </p>
          
            <p>
              Te doświadczenia przenoszę na płótno, tworząc portrety, które są czymś więcej niż tylko odwzorowaniem zdjęcia. Staram się uchwycić charakter i duszę portretowanej osoby, tworząc pamiątkę, która będzie cieszyć oko przez pokolenia.
            </p>
          </div>
        </div>

        {/* Right Column: Dynamic Workspace Image Framing (Asymmetric span) */}
        <div className="lg:col-span-5 space-y-6">
          <div className="aspect-[3/4] rounded-[32px] overflow-hidden shadow-sm border border-gray-100 bg-gray-50 relative group">
            <img
              src={maciejImg}
              alt="Kostek Maciej Kosteczka, Pracownia Artystyczna Łódź"
              referrerPolicy="no-referrer"
              className="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
            />
          </div>

          <div className="bg-gray-50 rounded-2xl p-6 border border-gray-100 space-y-4">
            <h4 className="font-display text-lg text-gray-900 font-semibold">Dlaczego malarstwo tradycyjne?</h4>
            <p className="text-sm text-gray-600 leading-relaxed">
              Dzisiejszy świat jest przepełniony cyfrowym szumem i natychmiastowym zaspokajaniem potrzeb. Ręcznie malowany obraz olejny to luksus posiadania czegoś trwałego i niepowtarzalnego. Gra światła na fakturowej powierzchni farby zmienia się w zależności od pory dnia, tworząc spektakl, którego nie da się skopiować na żadnym wyświetlaczu.
            </p>
          </div>
        </div>
      </section>

      {/* bottom CTA card */}
      <section className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0 mt-20">
        <div className="bg-stone-50 rounded-[32px] p-8 sm:p-12 border border-neutral-100 flex flex-col md:flex-row items-center justify-between gap-8">
          <div className="space-y-2 text-center md:text-left max-w-2xl">
            <h2 className="font-display text-3xl text-gray-900 font-normal">
              Podoba Ci się moja twórczość?
            </h2>
            <p className="text-stone-600 text-sm sm:text-base leading-relaxed">
              Zamów swój własny, unikalny portret ze zdjęcia lub zapoznaj się z gotowymi dziełami malarskimi dostępnymi w moim sklepie.
            </p>
          </div>
          <div className="flex flex-col sm:flex-row gap-4 shrink-0 w-full sm:w-auto">
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
