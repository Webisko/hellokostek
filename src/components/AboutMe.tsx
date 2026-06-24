import { Palette, Landmark, ShieldCheck, Heart, Camera, Brush } from "lucide-react";
import Testimonials from "./Testimonials";
import ProductSlider from "./ProductSlider";
import { PageId } from "../types";

const maciejImg = "https://hellokostek.pl/wp-content/uploads/2024/12/VideoCapture_20241205-135234.jpg";

interface AboutMeProps {
  setCurrentPage: (page: PageId) => void;
  onSelectProduct: (product: any) => void;
}

export default function AboutMe({ setCurrentPage, onSelectProduct }: AboutMeProps) {
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
            <p className="font-display italic text-gray-650 text-xl sm:text-2xl pl-6 border-l-4 border-[#E0115F] leading-relaxed">
              „Prawdziwy portret nie powstaje na ekranie komputera. Tworzy się go z wolna, warstwa po warstwie, szanując czas, fizyczny opór płótna oraz tradycyjny zapach terpentyny.”
            </p>
          </div>

          <div className="space-y-8 font-sans text-base text-gray-750 leading-relaxed">
            <p className="text-lg font-medium text-gray-950 leading-relaxed">
              Malarstwo to proces intymny i niepospieszny. Prace sygnowane marką <strong>hellokostek</strong> powstają z chęci zachowania tradycyjnego, czystego rzemiosła artystycznego. Jako niezależny malarz tworzę w zaciszu łódzkiej Pracowni Artystycznej portrety olejne, rysunki oraz akwarele, które stają się świadkami najważniejszych życiowych emocji moich klientów.
            </p>
          
          <p>
            Miejscem moich codziennych poszukiwań twórczych jest Łódź – miasto o surowej, postindustrialnej architekturze, niesamowitym filmowym dziedzictwie i nastrojowej, nieco melancholijnej aurze. Ta unikalna tożsamość przestrzeni bezpośrednio wpływa na moją paletę barw: od głębokich umbr i ugorów, po mocne, nasycone akcenty świetlne wyłaniające się z mroku.
          </p>

          <p>
            Specjalizuję się w malowaniu portretów ze zdjęcia na zamówienie. Każde dzieło to osobna opowieść. Kiedy powierzasz mi wizerunek męża, dziecka, rodziców lub ukochanego pupila, nie oddajesz go w ręce algorytmów czy bezdusznych maszyn drukarskich. Uzyskujesz bezpośredni, stały kontakt z autorem obrazu. Rozmawiamy o charakterze portretowanej osoby, o jej wzroku, uśmiechu czy ułożeniu dłoni, by praca emanowała prawdą, a nie tylko suchym podobieństwem.
          </p>

          <p>
            Mój warsztat wywodzi się z klasycznych tradycji akademickich. Korzystam wyłącznie z najwyższej klasy materiałów: naturalnych lnianych lub bawełnianych płócien naciąganych na krosna sosnowe, pędzli z włosia naturalnego oraz wyselekcjonowanych pigmentów olejnych uznanych europejskich marek. Wszystko po to, by obraz zachwycał intensywnością i fakturą przez pokolenia.
          </p>
        </div>
      </div>

        {/* Right Column: Dynamic Workspace Image Framing (Asymmetric span) */}
        <div className="lg:col-span-5 space-y-6">
          <div className="zoom-container aspect-[3/4] rounded-3xl overflow-hidden shadow-sm border border-gray-100 bg-gray-50">
            <img
              src={maciejImg}
              alt="Kostek Maciej Kosteczka, Pracownia Artystyczna Łódź"
              referrerPolicy="no-referrer"
              className="w-full h-full object-cover transition-all duration-700 hover:scale-105"
            />
          </div>

          <div className="bg-gray-50 rounded-2xl p-6 border border-gray-100 space-y-4">
            <h4 className="font-display text-lg text-gray-900 font-semibold">Dlaczego malarstwo tradycyjne?</h4>
            <p className="text-sm text-gray-600 leading-relaxed">
              Dzisiejszy świat jest przepełniony cyfrowym szumem i natychmiastowym zaspokajaniem potrzeb. Ręcznie malowany obraz olejny to luksus posiadania czegoś trwałego i niepowtarzalnego. Gra światła na fakturowej powierzchni farby zmienia się w zależności od pory dnia, tworząc spektakl, którego nie da się skopiować na żadnym wyświetlaczu.
            </p>
          </div>

          <div className="bg-gray-50 rounded-2xl p-6 border border-gray-100 space-y-4">
            <h4 className="font-display text-lg text-gray-900 font-semibold">Certyfikaty autentyczności</h4>
            <p className="text-sm text-gray-600 leading-relaxed">
              Każdy obraz – czy to rysunek, akwarela, czy portret olejny – otrzymuje ode mnie ręcznie wypisany i opieczętowany certyfikat, potwierdzający jego unikalność.
            </p>
          </div>
        </div>
      </section>

      {/* Symmetrical Core Values & Workflow Focus */}
      <section className="max-w-[1600px] mx-auto px-6 md:px-12 lg:px-16 xl:px-20 2xl:px-6 3xl:px-0">
        <div className="bg-gray-50 rounded-[32px] p-8 sm:p-12 border border-blue-50/10 grid grid-cols-1 md:grid-cols-3 gap-8">
          <div className="space-y-3 font-sans">
            <div className="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-gray-900 border border-gray-100">
              <Brush className="w-5 h-5 text-[#E0115F]" />
            </div>
            <h3 className="font-display text-lg font-semibold text-gray-900 uppercase tracking-tight">Tradycyjna Technologia</h3>
            <p className="text-sm text-gray-600 leading-relaxed">
              Nie chodzę na skróty. Stosuję klasyczną metodę laserunku oraz impastu, malując warstwowo na solidnych gruntowanych podobraziach sosnowych.
            </p>
          </div>

          <div className="space-y-3 font-sans">
            <div className="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-gray-900 border border-gray-100">
              <Palette className="w-5 h-5 text-[#E0115F]" />
            </div>
            <h3 className="font-display text-lg font-semibold text-gray-900 uppercase tracking-tight">Osobisty Dialog</h3>
            <p className="text-sm text-gray-600 leading-relaxed">
              Wspólnie z klientem opracowuję układ kompozycji drogą cyfrową. Ty zatwierdzasz szkic i gamę barwną przed fizycznym przyłożeniem pędzla do płótna.
            </p>
          </div>

          <div className="space-y-3 font-sans">
            <div className="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-gray-900 border border-gray-100">
              <ShieldCheck className="w-5 h-5 text-[#E0115F]" />
            </div>
            <h3 className="font-display text-lg font-semibold text-gray-900 uppercase tracking-tight">Bezpieczeństwo i Trwałość</h3>
            <p className="text-sm text-gray-600 leading-relaxed">
              Ukończony obraz pokrywam satynowym werniksem chroniącym przed promieniami UV i kurzem. Paczki pakuję osobiście w pancerną konstrukcję z tektury i pianki.
            </p>
          </div>
        </div>
      </section>

      {/* Verified Client Quotes with premium layout */}
      <Testimonials />

      {/* Interactive gallery slider - gotowe prace */}
      <ProductSlider setCurrentPage={setCurrentPage} onSelectProduct={onSelectProduct} />
    </div>
  );
}
