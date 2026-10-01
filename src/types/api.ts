/**
 * TypeScript API definitions for Laravel 13 backend endpoints.
 * These types match the standardized JsonResource structures returned by the backend.
 */

export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  has_more_pages: boolean;
}

export interface PaginatedResponse<T> {
  data: T[];
  pagination: PaginationMeta;
}

export interface ApiProductCategory {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
}

export interface ApiProductVariant {
  id: number;
  sku: string;
  regular_price_amount: number;
  sale_price_amount?: number | null;
  stock_quantity: number;
  is_active: boolean;
}

export interface ApiProduct {
  id: number | string;
  slug: string;
  sku: string;
  name: string;
  short_description?: string | null;
  description?: string | null;
  regular_price_amount: number;
  sale_price_amount?: number | null;
  price_amount: number;
  currency: string;
  stock_quantity?: number | null;
  featured_image_url?: string | null;
  hover_image_url?: string | null;
  gallery_image_urls?: string[];
  categories?: ApiProductCategory[];
  variants?: ApiProductVariant[];
  is_new?: boolean;
  is_bestseller?: boolean;
  is_recommended?: boolean;
  metadata?: Record<string, any>;
  lowest_price_last_30_days?: number | null;
  reviews_count?: number;
  average_rating?: number;
}

export interface ApiCatalogPayload {
  products: ApiProduct[];
  categories: ApiProductCategory[];
  pagination: PaginationMeta;
}

export type ApiCatalogResponse = ApiResponse<ApiCatalogPayload>;

export interface ApiGalleryArtwork {
  id: string;
  title: string;
  technique?: string | null;
  format?: string | null;
  category?: string | null;
  category_slug?: string | null;
  category_id?: number | null;
  year?: string | null;
  image_url: string;
  original_url?: string | null;
  sort_order: number;
}

export interface ApiGalleryPayload {
  items: ApiGalleryArtwork[];
}

export type ApiGalleryResponse = ApiResponse<ApiGalleryPayload>;

export interface ApiContentPage {
  id: number;
  slug: string;
  title: string;
  excerpt?: string | null;
  content?: string | null;
  hero_image_url?: string | null;
  hero_image_alt?: string | null;
  template: string;
  template_label?: string;
  seo_title?: string | null;
  seo_description?: string | null;
  is_noindex: boolean;
  canonical_url: string;
  published_at?: string | null;
  updated_at?: string | null;
  sections?: any[];
  metadata?: Record<string, any>;
}

export type ApiContentPageResponse = ApiResponse<{ page: ApiContentPage }>;
export type ApiContentPagesResponse = ApiResponse<{ pages: ApiContentPage[] }>;

export interface ApiFaqItem {
  id: number;
  question: string;
  answer: string;
  group_name?: string | null;
  sort_order: number;
}

export interface ApiFaqPayload {
  items: ApiFaqItem[];
  schema_json_ld?: Record<string, any>;
}

export type ApiFaqResponse = ApiResponse<ApiFaqPayload>;

export interface ApiCustomerAddress {
  id: number;
  name: string;
  company_name?: string | null;
  nip?: string | null;
  first_name: string;
  last_name: string;
  address_line_1: string;
  address_line_2?: string | null;
  postal_code: string;
  city: string;
  country_code: string;
  phone?: string | null;
  is_default_shipping: boolean;
  is_default_billing: boolean;
  created_at?: string;
  updated_at?: string;
}

export type ApiAddressesResponse = ApiResponse<ApiCustomerAddress[]>;

export interface ApiReview {
  id: number;
  customer_name: string;
  rating: number;
  comment: string;
  is_verified_purchase: boolean;
  created_at?: string;
}

export interface ApiReviewsPayload {
  average_rating: number;
  reviews_count: number;
  reviews: ApiReview[];
}

export type ApiReviewsResponse = ApiResponse<ApiReviewsPayload>;

export interface ApiOrderReturn {
  id: number;
  order_id: number;
  order_number?: string | null;
  status: string;
  reason?: string | null;
  tracking_number?: string | null;
  refund_amount?: number | null;
  items: Array<{
    id: number;
    order_item_id: number;
    quantity: number;
    product_name?: string;
    product_sku?: string;
  }>;
  created_at?: string;
  updated_at?: string;
}

export type ApiOrderReturnsResponse = ApiResponse<ApiOrderReturn[]>;
