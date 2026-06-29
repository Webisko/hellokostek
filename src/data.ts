import type { Product, Testimonial } from "./types";

export const SHOP_PRODUCTS: Product[] = [
  // --- WATERCOLORS (300 PLN Original, 30 PLN Print) ---
  {
    id: "watercolor-2-2022",
    title: "Więcej o obiekcie II",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Wiecej-o-obiekcie-2-2022-edited-768x768.webp",
    description: "Subtelna akwarela z cyklu badającego formę i relacje przestrzenne. Delikatne rozmycia i głębokie tony budują melancholijny, intymny nastrój idealny do sypialni lub salonu wypoczynkowego."
  },
  {
    id: "watercolor-7-2022",
    title: "Więcej o obiekcie VII",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Wiecej-o-obiekcie-7-2022-scaled.webp",
    description: "Poruszająca kompozycja akwarelowa na grubym papierze bawełnianym. Harmoniczne zestrojenie chłodnych barw z delikatną nutą ciepła emanuje spokojem i wyciszeniem."
  },
  {
    id: "watercolor-8-2022",
    title: "Więcej o obiekcie VIII",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Wiecej-o-obiekcie-8.webp",
    description: "Kameralna praca z przewagą organicznych, miękkich kształtów. Urzekający detal, który przyciąga wzrok i zaprasza do codziennej, cichej kontemplacji."
  },
  {
    id: "watercolor-9-2022",
    title: "Więcej o obiekcie IX",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Wiecej-o-obiekcie-9-2022-scaled.webp",
    description: "Zmysłowe, płynne przejścia akwarelowe. Praca o silnym ładunku emocjonalnym, zbalansowana lekkim tłem, która doskonale komponuje się z nowoczesnymi oraz klasycznymi wnętrzami."
  },
  {
    id: "watercolor-13-2022",
    title: "Więcej o obiekcie XIII (Sygnowany)",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: false, // Only print as requested: "Dostępne są wydruki powyższych akwareli - 30 zł plus koszt wysyłki plus ta praca..."
    imageUrl: "/hellokostek/images/Wiecej-o-obiekcie-13-2022-scaled.webp",
    description: "Wyrafinowana kompozycja akwarelowa, dostępna wyłącznie w postaci wysokiej jakości wydruku artystycznego na luksusowym papierze archiwalnym."
  },

  // --- DRAWINGS (200 PLN Original, 20 PLN Print) ---
  {
    id: "drawing-run-2024",
    title: "Postaci w biegu",
    year: "2024",
    category: "drawing",
    originalPrice: 200,
    printPrice: 20,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/2086036_1b.webp",
    description: "Ekspresyjny rysunek ołówkiem rejestrujący dynamikę ludzkiego ciała, grę cieni i ruch. Nowoczesna kreska, która wnosi do wnętrza powiew energii."
  },
  {
    id: "drawing-daily-2022",
    title: "Codzienność",
    year: "2022",
    category: "drawing",
    originalPrice: 200,
    printPrice: 20,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Codziennosc-2022.webp",
    description: "Kameralne studium chłodnej, melancholijnej codzienności. Wyjątkowo intymna kompozycja, skłaniająca do odnalezienia piękna w najprostszych, ulotnych momentach."
  },
  {
    id: "drawing-cant-stand-2022",
    title: "Nie wytrzymam",
    year: "2022",
    category: "drawing",
    originalPrice: 200,
    printPrice: 20,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Nie-wytrzymam-2022.webp",
    description: "Poruszające personifikowanie nagromadzonych emocji za pomocą wyrazistej kreski graficznej. Głębokie kontrasty ucieleśniają wewnętrzną odporność i siłę."
  },
  {
    id: "drawing-anxiety-2022",
    title: "Lęk",
    year: "2022",
    category: "drawing",
    originalPrice: 200,
    printPrice: 20,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Lek-2022-1.webp",
    description: "Delikatny, pełen czułości i zniuansowania rysunek poruszający intymny temat lęku jako części ludzkiego doświadczenia. Uniwersalna, piękna praca kolekcjonerska."
  },
  {
    id: "drawing-isolated-10-2022",
    title: "Obiekt wyodrębniony #10",
    year: "2022",
    category: "drawing",
    originalPrice: 200,
    printPrice: 20,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Obiekt-wyodrebniony-10-2022.webp",
    description: "Minimalistyczny, surowy w formie rysunek ołówkiem skupiający się na pojedynczej bryle i cieniu. Wybitna lekcja czystej proporcji i przestrzeni."
  },
  {
    id: "drawing-weird-feeling-2022",
    title: "To dziwne uczucie",
    year: "2022",
    category: "drawing",
    originalPrice: 200,
    printPrice: 20,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/To-dziwne-uczucie-2022.webp",
    description: "Złożony i zmysłowy rysunek, który dotyka nieuchwytnych stanów emocjonalnych. Każde pociągnięcie ołówka buduje głęboką strukturę psychologiczną postaci."
  },
  {
    id: "drawing-escape-2022",
    title: "Ucieczka",
    year: "2022",
    category: "drawing",
    originalPrice: 200,
    printPrice: 20,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Ucieczka-2022.webp",
    description: "Dynamiczny, metaforyczny rysunek ukazujący pragnienie wolności i przestrzeni. Niezwykła lekkość kompozycji idealnie ożywi minimalistyczne wnętrze."
  },
  {
    id: "drawing-fear-2022",
    title: "Strach",
    year: "2022",
    category: "drawing",
    originalPrice: 200,
    printPrice: 20,
    isOriginalAvailable: true,
    imageUrl: "/hellokostek/images/Strach-2022.webp",
    description: "Sztuka zmagań sformułowana w nienagannym rzemiośle ołówka. Oparta na delikatnych cieniach praca, która potrafi oczarować głębią wyrazu."
  }
];

export const PORTRAIT_PRICING = {
  basePrice: 800, // For 30x40 cm
  rectangle30x40: 800,
  oval30x40: 800,
  extraPersonFee: 300, // Approximate reference for larger portraits to make the calculator fully interactive
  advanceRatio: 0.50, // 50% non-refundable deposit
};

export const TESTIMONIALS: Testimonial[] = [
  {
    id: 1,
    stars: 5,
    text: "„Portret ślubny wyszedł niesamowicie. Kiedy rozpakowaliśmy przesyłkę, oboje mieliśmy łzy w oczach. Dbałość o szczegóły i faktura farby olejnej na płótnie robią spektakularne wrażenie na żywo.”",
    author: "Anna K.",
    meta: "Warszawa • Portret Ślubny Pary",
    emoji: "💖"
  },
  {
    id: 2,
    stars: 5,
    text: "„Zamówiłem portret córki Marysi. Kontakt z Panem Maciejem był rewelacyjny na każdym etapie – od projektu cyfrowego po gotowy obraz. Odbiór osobisty w Łodzi był bardzo miłym akcentem. Polecam z całego serca.”",
    author: "Piotr M.",
    meta: "Łódź • Portret Marysi",
    emoji: "😊"
  },
  {
    id: 3,
    stars: 5,
    text: "„Owalny portret Oliwii w salonie przykuwa uwagę każdego gościa. To nie jest zwykły wydruk ze zdjęcia, to prawdziwa sztuka z duszą. Gra światła na tym płótnie o różnych porach dnia jest zachwycająca.”",
    author: "Karolina W.",
    meta: "Kraków • Portret Oliwii",
    emoji: "✨"
  },
  {
    id: 4,
    stars: 5,
    text: "„Portret Leona to był strzał w dziesiątkę jako prezent dla taty. Obraz wisi w gabinecie i robi piorunujące wrażenie na wszystkich. Tradycyjna technika olejna ma tę głębię, której brak współczesnym wydrukom.”",
    author: "Michał T.",
    meta: "Poznań • Portret Leona",
    emoji: "👍"
  },
  {
    id: 5,
    stars: 5,
    text: "„Zamówiłem akwarelę do sypialni. Kolorystyka i nastrój tego rysunku są wręcz hipnotyzujące. Cały proces od wysyłki do dostarczenia paczki przebiegł sprawnie i bezpiecznie. Na pewno wrócę po kolejną pracę!”",
    author: "Zofia S.",
    meta: "Wrocław • Akwarela",
    emoji: "🥰"
  },
  {
    id: 6,
    stars: 5,
    text: "„Rysunek ołówkiem wysłany w tubie zabezpieczony idealnie. Precyzja cieniowania i realizm powalają. Bardzo sprawna wysyłka i profesjonalne podejście.”",
    author: "Janusz B.",
    meta: "Gdańsk • Rysunek Ołówkiem",
    emoji: "👌"
  },
  {
    id: 7,
    stars: 5,
    text: "„Malowany portret dla rodziców na jubileusz okazał się najpięknisem prezentem. Rodzice byli wzruszeni, a obraz wisi w najważniejszym miejscu w domu.”",
    author: "Małgorzata D.",
    meta: "Katowice • Portret Ślubny Pary",
    emoji: "❤️"
  },
  {
    id: 8,
    stars: 5,
    text: "„Klasa sama w sobie. Tradycyjny warsztat malarski czuć od pierwszego spojrzenia na płótno. Szczerze polecam każdemu, kto ceni autentyczne rzemiosło.”",
    author: "Tomasz R.",
    meta: "Lublin • Portret Leona",
    emoji: "👏"
  },
  {
    id: 9,
    stars: 5,
    text: "„Kupiłem gotową akwarelę do pokoju gościnnego. Kolory są jeszcze piękniejsze na żywo niż na zdjęciach. Bardzo szybka dostawa, obraz był solidnie spakowany.”",
    author: "Agnieszka K.",
    meta: "Szczecin • Akwarela",
    emoji: "🎨"
  }
];
