import type { Product, Testimonial } from "./types";

export const SHOP_PRODUCTS: Product[] = [
  // --- WATERCOLORS (300 PLN Original, 30 PLN Print) ---
  {
    id: "watercolor-2-2022",
    title: "Obiekt II",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: true,
    imageUrl: "/images/Wiecej-o-obiekcie-2-2022-edited-768x768.webp",
    description: "Subtelna akwarela z cyklu badającego formę i relacje przestrzenne. Delikatne rozmycia i głębokie tony budują melancholijny, intymny nastrój idealny do sypialni lub salonu wypoczynkowego."
  },
  {
    id: "watercolor-7-2022",
    title: "Obiekt VII",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: true,
    imageUrl: "/images/Wiecej-o-obiekcie-7-2022-scaled.webp",
    description: "Poruszająca kompozycja akwarelowa na grubym papierze bawełnianym. Harmoniczne zestrojenie chłodnych barw z delikatną nutą ciepła emanuje spokojem i wyciszeniem."
  },
  {
    id: "watercolor-8-2022",
    title: "Obiekt VIII",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: true,
    imageUrl: "/images/Wiecej-o-obiekcie-8.webp",
    description: "Kameralna praca z przewagą organicznych, miękkich kształtów. Urzekający detal, który przyciąga wzrok i zaprasza do codziennej, cichej kontemplacji."
  },
  {
    id: "watercolor-9-2022",
    title: "Obiekt IX",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: true,
    imageUrl: "/images/Wiecej-o-obiekcie-9-2022-scaled.webp",
    description: "Zmysłowe, płynne przejścia akwarelowe. Praca o silnym ładunku emocjonalnym, zbalansowana lekkim tłem, która doskonale komponuje się z nowoczesnymi oraz klasycznymi wnętrzami."
  },
  {
    id: "watercolor-13-2022",
    title: "Obiekt XIII (Sygnowany)",
    year: "2022",
    category: "watercolor",
    originalPrice: 300,
    printPrice: 30,
    isOriginalAvailable: false, // Only print as requested: "Dostępne są wydruki powyższych akwareli - 30 zł plus koszt wysyłki plus ta praca..."
    imageUrl: "/images/Wiecej-o-obiekcie-13-2022-scaled.webp",
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
    imageUrl: "/images/2086036_1b.webp",
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
    imageUrl: "/images/Codziennosc-2022.webp",
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
    imageUrl: "/images/Nie-wytrzymam-2022.webp",
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
    imageUrl: "/images/Lek-2022-1.webp",
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
    imageUrl: "/images/Obiekt-wyodrebniony-10-2022.webp",
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
    imageUrl: "/images/To-dziwne-uczucie-2022.webp",
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
    imageUrl: "/images/Ucieczka-2022.webp",
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
    imageUrl: "/images/Strach-2022.webp",
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
    text: "„Hej! Mama jest zachwycona! Co się patrzy to się uśmiecha. Roześmiała się jak zobaczyła i powiedziała, że idealnie odwzorowane!”",
    author: "Kasia",
    meta: "Portret z trzema psami",
    emoji: "🐶"
  },
  {
    id: 2,
    stars: 5,
    text: "„Gdy wręczyłem prezent to złożyłem życzenia od ciebie Tata bardzo Cię pozdrawia i jest 🙂 wzruszony prezentem. Bardzo lubi Twoją twórczość Jesteś gość ! Tak powiedział. 🙂”",
    author: "Kamil",
    meta: "Portret taty",
    emoji: "🙂"
  },
  {
    id: 3,
    stars: 5,
    text: "„Kubeczki zamówione dla siostrzeńców rok temu nadal świetnie się myją, wzory nie znikają, a siostrzeńcy uwielbiają spersonalizowane kubki ze swoimi ulubionymi bohaterami Dodatkowo, ❤ wszystkie wytwory szydełkowe jakie wychodzą spod rąk Maćka są zawsze świetnej jakości, solidnie wykonane i przede wszystkim przytulaśne. Obrazy najchętniej powiesiłabym u siebie na ścianie. Generalnie polecam tego Pana, to bardzo solidna firma!”",
    author: "Wiola",
    meta: "O różnych produktach hellokostek",
    emoji: "❤️"
  },
  {
    id: 4,
    stars: 5,
    text: "„Hej, cieszę się, że zamówiłam u Ciebie ten portret, wszedł bardzo ładnie. I sprawił dużo radości rodzinie, do której trafił. Miałam Ci przysłać zdjęcie, jak wygląda oprawiony, ale jednak zrezygnowałam z oprawy - dobrze się prezentuje bez ramy.”",
    author: "Maria",
    meta: "Portret dzieci",
    emoji: "🖼️"
  },
  {
    id: 5,
    stars: 5,
    text: "„Super jest Przepięknie . 🥰 😍😍”",
    author: "Lucia",
    meta: "Portret córek",
    emoji: "🥰"
  },
  {
    id: 6,
    stars: 5,
    text: "„Jaki Ty zdolny jesteś!!!!”",
    author: "Dorota",
    meta: "Opinia ogólna",
    emoji: "✨"
  },
  {
    id: 7,
    stars: 5,
    text: "„cześć, bardzo podobał mi się rysunek. Chętnie bym kupił dwa obrazy. Masz talent! Pozdrawiam. ”",
    author: "Krzysiek",
    meta: "O rysunkach",
    emoji: "🎨"
  }
];
