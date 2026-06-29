export type PageId = "home" | "shop" | "about" | "contact" | "product-detail" | "success-purchase" | "success-contact" | "privacy" | "terms" | "gallery";

export interface GalleryArtwork {
  id: string;
  title: string;
  year: string;
  imageUrl: string;
  originalUrl?: string;
  technique: "olej" | "akwarela" | "akryl" | "rysunek";
}

export interface Product {
  id: string;
  title: string;
  year: string;
  category: "watercolor" | "drawing" | "custom_portrait";
  originalPrice: number;
  printPrice?: number; // 30 PLN for watercolor prints, 20 PLN for drawing prints
  isOriginalAvailable: boolean;
  imageUrl: string;
  originalPageUrl?: string;
  description: string;
  isPopular?: boolean;
}

export interface CartItem {
  cartId: string;
  productId: string;
  title: string;
  category: "watercolor" | "drawing" | "custom_portrait";
  purchaseType: "original" | "print";
  price: number;
  quantity: number;
  optionsSummary?: string;
  shippingMethod?: string;
  shippingPrice?: number;
}

export interface PortraitCustomConfig {
  canvasShape: "rectangle" | "oval";
  dimensions: "30x40" | "40x55" | "50x70";
  peopleCount: number;
  specialRequests: string;
  uploadedPhotoUrl?: string;
  price: number;
}

export interface Testimonial {
  id: number;
  stars: number;
  text: string;
  author: string;
  meta: string;
  emoji?: string;
}
