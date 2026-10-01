import React, { useState, useEffect, useRef } from "react";
import { MapPin, X, Search, Navigation, Check, Loader2, ExternalLink } from "lucide-react";
import "leaflet/dist/leaflet.css";

export interface SelectedParcelPoint {
  id: string;
  name: string;
  address: string;
  city?: string;
  postalCode?: string;
}

interface ParcelMapModalProps {
  isOpen: boolean;
  onClose: () => void;
  provider: "inpost" | "orlen";
  onSelectPoint: (point: SelectedParcelPoint) => void;
  defaultCity?: string;
}

interface ShipXPoint {
  name: string;
  location: {
    latitude: number;
    longitude: number;
  };
  address: {
    line1: string;
    line2: string;
  };
  address_details?: {
    city: string;
    street: string;
    building_number: string;
    post_code: string;
  };
  location_description?: string;
  opening_hours?: string;
}

export function parseOrlenPointInput(input: string): {
  id: string;
  name: string;
  address: string;
} {
  const trimmed = input.trim();
  if (!trimmed) return { id: "", name: "", address: "" };

  // 1. Check for 5-7 digit number (e.g. 913861) or standard format (e.g. OP-123456)
  const digitMatch = trimmed.match(/\b(\d{5,7})\b/);
  const codeMatch = trimmed.match(/\b([A-Z]{2,4}-?\d{5,7})\b/i);

  const id = digitMatch ? digitMatch[1] : (codeMatch ? codeMatch[1].toUpperCase() : trimmed.replace(/\s+/g, "").toUpperCase());

  let pointType = "Punkt ORLEN Paczka";
  if (/partnerski/i.test(trimmed)) pointType = "Punkt Partnerski";
  else if (/automat/i.test(trimmed)) pointType = "Automat Paczkowy";
  else if (/stacja/i.test(trimmed)) pointType = "Stacja Paliw ORLEN";

  // Check if multiline or address included
  const lines = trimmed.split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
  let detectedAddress = "";
  for (const line of lines) {
    if (line !== trimmed && (line.match(/\d{2}-\d{3}/) || line.match(/ul\.|al\.|świętokrzyska|[0-9]+\/[0-9]+/i))) {
      detectedAddress = line;
      break;
    }
  }

  return {
    id,
    name: `${pointType} ${id}`,
    address: detectedAddress || `Punkt odbioru ${id}`,
  };
}

export default function ParcelMapModal({
  isOpen,
  onClose,
  provider,
  onSelectPoint,
  defaultCity = "Warszawa",
}: ParcelMapModalProps) {
  // Common state
  const isInPost = provider === "inpost";

  // InPost interactive map state
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const mapInstanceRef = useRef<any>(null);
  const markersLayerRef = useRef<any>(null);

  const [searchQuery, setSearchQuery] = useState("");
  const [points, setPoints] = useState<ShipXPoint[]>([]);
  const [selectedPoint, setSelectedPoint] = useState<ShipXPoint | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [searchError, setSearchError] = useState<string | null>(null);

  // Orlen state
  const [orlenCode, setOrlenCode] = useState("");
  const [orlenError, setOrlenError] = useState("");
  const parsedOrlen = orlenCode.trim() ? parseOrlenPointInput(orlenCode) : null;

  // Close on Escape
  useEffect(() => {
    if (!isOpen) return;
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [isOpen, onClose]);

  // Lock body scroll
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = "hidden";
      setOrlenCode("");
      setOrlenError("");
      setSearchError(null);
    } else {
      document.body.style.overflow = "";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, [isOpen]);

  // Fetch InPost points from public ShipX API (no token needed, CORS enabled)
  const fetchInPostPoints = async (lat?: number, lng?: number, query?: string) => {
    setIsLoading(true);
    setSearchError(null);
    try {
      let url = "https://api-shipx-pl.easypack24.net/v1/points?type=parcel_locker&limit=40";
      if (query && query.trim().length > 0) {
        url += `&query=${encodeURIComponent(query.trim())}`;
      } else if (lat !== undefined && lng !== undefined) {
        url += `&relative_point=${lat},${lng}`;
      } else {
        url += "&city=Warszawa";
      }

      const res = await fetch(url);
      if (!res.ok) throw new Error("Błąd pobierania punktów InPost");
      const data = await res.json();
      const items: ShipXPoint[] = data.items || [];
      setPoints(items);

      if (items.length === 0) {
        setSearchError("Nie znaleziono Paczkomatów w tej lokalizacji. Spróbuj wpisać inne miasto lub ulicę.");
      } else if (!selectedPoint && items.length > 0) {
        setSelectedPoint(items[0]);
      }
    } catch (err) {
      console.warn("Błąd pobierania punktów InPost:", err);
      setSearchError("Wystąpił problem z połączeniem z bazą Paczkomatów.");
    } finally {
      setIsLoading(false);
    }
  };

  // Initialize Leaflet map for InPost
  useEffect(() => {
    if (!isOpen || !isInPost) return;

    let isMounted = true;

    const initMap = async () => {
      const L = (await import("leaflet")).default;
      if (!isMounted || !mapContainerRef.current) return;

      if (mapInstanceRef.current) {
        mapInstanceRef.current.remove();
        mapInstanceRef.current = null;
      }

      const defaultLat = 52.2297;
      const defaultLng = 21.0122;

      const map = L.map(mapContainerRef.current, {
        zoomControl: false,
      }).setView([defaultLat, defaultLng], 13);

      L.control.zoom({ position: "bottomright" }).addTo(map);

      // Clean OpenStreetMap tiles - 100% free and open
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a> | InPost',
      }).addTo(map);

      const markersGroup = L.layerGroup().addTo(map);
      markersLayerRef.current = markersGroup;
      mapInstanceRef.current = map;

      // Invalidate size once modal animation finishes
      setTimeout(() => {
        if (isMounted && mapInstanceRef.current) {
          mapInstanceRef.current.invalidateSize();
        }
      }, 250);

      // Handle window / orientation resize on mobile and tablet
      window.addEventListener("resize", handleResize);

      // On pan/zoom end, update points around center
      map.on("moveend", () => {
        const center = map.getCenter();
        fetchInPostPoints(center.lat, center.lng);
      });

      // Initial points load
      if (defaultCity && defaultCity.trim().length > 1) {
        fetchInPostPoints(undefined, undefined, defaultCity.trim());
      } else {
        fetchInPostPoints(defaultLat, defaultLng);
      }
    };

    const handleResize = () => {
      if (mapInstanceRef.current) {
        mapInstanceRef.current.invalidateSize();
      }
    };

    initMap();

    return () => {
      isMounted = false;
      window.removeEventListener("resize", handleResize);
      if (mapInstanceRef.current) {
        mapInstanceRef.current.remove();
        mapInstanceRef.current = null;
      }
    };
  }, [isOpen, isInPost]);

  // Update InPost markers on points change
  useEffect(() => {
    if (!mapInstanceRef.current || !markersLayerRef.current || !isInPost) return;

    import("leaflet").then(({ default: L }) => {
      const layer = markersLayerRef.current;
      layer.clearLayers();

      points.forEach((point) => {
        const isSelected = selectedPoint?.name === point.name;
        const icon = L.divIcon({
          className: "inpost-marker-icon",
          html: `<div style="
            background: ${isSelected ? "#E0115F" : "#FFD200"};
            color: ${isSelected ? "#FFFFFF" : "#111111"};
            font-weight: 700;
            font-size: 11px;
            font-family: ui-monospace, SFMono-Regular, monospace;
            padding: 4px 8px;
            border-radius: 6px;
            border: 2px solid ${isSelected ? "#FFFFFF" : "#111111"};
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.15s ease;
            transform: ${isSelected ? "scale(1.15)" : "scale(1)"};
          ">
            <span style="display:inline-block;width:6px;height:6px;background:${isSelected ? "#FFFFFF" : "#111111"};border-radius:50%;"></span>
            ${point.name}
          </div>`,
          iconSize: [85, 28],
          iconAnchor: [42, 14],
        });

        const marker = L.marker([point.location.latitude, point.location.longitude], { icon });
        marker.on("click", () => {
          setSelectedPoint(point);
          mapInstanceRef.current?.panTo([point.location.latitude, point.location.longitude]);
        });
        layer.addLayer(marker);
      });

      // Pan to first result if searching
      if (searchQuery && points.length > 0) {
        const first = points[0];
        mapInstanceRef.current.setView([first.location.latitude, first.location.longitude], 14);
      }
    });
  }, [points, selectedPoint, isInPost]);

  const handleInPostSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!searchQuery.trim()) return;
    fetchInPostPoints(undefined, undefined, searchQuery.trim());
  };

  const handleUseLocation = () => {
    if (!navigator.geolocation) {
      alert("Twoja przeglądarka nie obsługuje geolokalizacji.");
      return;
    }
    setIsLoading(true);
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const { latitude, longitude } = pos.coords;
        if (mapInstanceRef.current) {
          mapInstanceRef.current.setView([latitude, longitude], 14);
        }
        fetchInPostPoints(latitude, longitude);
      },
      (err) => {
        console.warn("Geolocation error:", err);
        setIsLoading(false);
        alert("Nie udało się pobrać Twojej lokalizacji.");
      }
    );
  };

  const handleConfirmInPost = () => {
    if (!selectedPoint) return;
    const streetName = selectedPoint.address_details?.street || selectedPoint.address.line1 || "";
    const bld = selectedPoint.address_details?.building_number || "";
    const city = selectedPoint.address_details?.city || selectedPoint.address.line2 || "";
    const postal = selectedPoint.address_details?.post_code || "";

    onSelectPoint({
      id: selectedPoint.name,
      name: `Paczkomat InPost ${selectedPoint.name}`,
      address: `${streetName} ${bld}`.trim(),
      city: city,
      postalCode: postal,
    });
    onClose();
  };



  const handleConfirmOrlen = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    const parsed = parseOrlenPointInput(orlenCode);

    if (!parsed.id || parsed.id.length < 3) {
      setOrlenError("Wprowadź prawidłowy kod punktu ORLEN (np. 913861).");
      return;
    }

    onSelectPoint({
      id: parsed.id,
      name: parsed.name,
      address: parsed.address,
    });
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-6 bg-black/65 backdrop-blur-sm animate-fadeIn">
      {/* Modal Container */}
      <div 
        className="relative w-full max-w-4xl bg-white rounded-2xl sm:rounded-3xl border border-gray-200 shadow-2xl overflow-hidden flex flex-col h-[90dvh] sm:h-[85dvh] max-h-[760px] animate-scaleUp"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="p-4 sm:p-5 border-b border-gray-100 flex items-start sm:items-center justify-between bg-white relative z-10 shrink-0">
          <div className="space-y-0.5 pr-4">
            <h3 className="font-display text-xl sm:text-2xl text-gray-900 tracking-tight">
              {isInPost ? "Wybierz Paczkomat" : "Wybierz punkt ORLEN Paczka"}
            </h3>
            <p className="text-xs sm:text-sm text-gray-500 font-sans">
              {isInPost 
                ? "Kliknij automat na mapie lub wyszukaj swoją ulicę — bez konieczności logowania." 
                : "Sprawdź punkt na oficjalnej mapie ORLEN Paczki i wpisz jego kod."}
            </p>
          </div>

          <button
            type="button"
            onClick={onClose}
            className="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-gray-50 hover:bg-gray-100 border border-gray-200 text-gray-500 hover:text-gray-900 flex items-center justify-center transition-all shrink-0 cursor-pointer"
            aria-label="Zamknij okno"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content Body */}
        {isInPost ? (
          <>
            {/* Search Bar for InPost */}
            <div className="p-3 sm:p-4 bg-gray-50/80 border-b border-gray-100 flex flex-col sm:flex-row gap-2 shrink-0">
              <form onSubmit={handleInPostSearchSubmit} className="flex-1 flex gap-2">
                <div className="relative flex-1">
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Wpisz miasto, ulicę lub kod (np. Piotrkowska Łódź, WAW01A)..."
                    className="w-full bg-white border border-gray-200 rounded-xl py-2.5 pl-10 pr-4 text-xs sm:text-sm font-sans text-gray-900 focus:outline-none focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] transition-all"
                  />
                  <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400" />
                </div>
                <button
                  type="submit"
                  disabled={isLoading}
                  className="px-4 py-2.5 bg-gray-900 hover:bg-[#E0115F] text-white text-xs font-semibold uppercase tracking-wider rounded-xl transition-all disabled:opacity-50 flex items-center justify-center cursor-pointer shrink-0"
                >
                  {isLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : "Szukaj"}
                </button>
              </form>
              <button
                type="button"
                onClick={handleUseLocation}
                className="px-3.5 py-2.5 bg-white border border-gray-200 hover:border-gray-300 text-gray-700 hover:text-black text-xs font-medium rounded-xl transition-all flex items-center justify-center gap-1.5 whitespace-nowrap cursor-pointer shrink-0"
              >
                <Navigation className="w-3.5 h-3.5 text-[#E0115F]" />
                <span>Moja lokalizacja</span>
              </button>
            </div>

            {/* Interactive Leaflet Map Area */}
            <div className="relative flex-1 w-full bg-neutral-100 min-h-0 overflow-hidden">
              <div ref={mapContainerRef} className="w-full h-full" />
              {isLoading && (
                <div className="absolute top-3 right-3 bg-white/95 backdrop-blur-xs px-3.5 py-2 rounded-xl border border-gray-200 text-xs font-sans text-gray-800 flex items-center gap-2 shadow-sm z-[500]">
                  <Loader2 className="w-3.5 h-3.5 animate-spin text-[#E0115F]" />
                  <span>Pobieranie Paczkomatów...</span>
                </div>
              )}
              {searchError && (
                <div className="absolute top-3 left-1/2 -translate-x-1/2 bg-white/95 backdrop-blur-xs px-4 py-2 rounded-xl border border-rose-200 text-xs text-rose-700 font-sans shadow-md z-[500] max-w-[90%] text-center">
                  {searchError}
                </div>
              )}
            </div>

            {/* Selected InPost Locker Bottom Bar */}
            <div className="p-3 sm:p-4 px-4 sm:px-6 bg-white border-t border-gray-100 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shrink-0">
              {selectedPoint ? (
                <div className="flex items-start gap-3 min-w-0">
                  <div className="w-9 h-9 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs font-mono">
                    {selectedPoint.name.slice(0, 3)}
                  </div>
                  <div className="min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-mono font-bold text-xs bg-gray-900 text-white px-2 py-0.5 rounded-md">
                        {selectedPoint.name}
                      </span>
                      {selectedPoint.location_description && (
                        <span className="text-xs text-gray-500 truncate max-w-[240px]">
                          {selectedPoint.location_description}
                        </span>
                      )}
                    </div>
                    <p className="text-xs sm:text-sm font-medium text-gray-900 mt-1 truncate">
                      {selectedPoint.address_details?.street || selectedPoint.address.line1}{" "}
                      {selectedPoint.address_details?.building_number},{" "}
                      {selectedPoint.address_details?.city || selectedPoint.address.line2}
                    </p>
                  </div>
                </div>
              ) : (
                <p className="text-xs text-gray-500 font-sans self-center">
                  Kliknij znacznik Paczkomatu na mapie, aby go wybrać.
                </p>
              )}

              <button
                type="button"
                onClick={handleConfirmInPost}
                disabled={!selectedPoint}
                className="w-full sm:w-auto px-6 py-3 bg-[#E0115F] hover:bg-[#c00d50] text-white text-xs font-semibold uppercase tracking-wider rounded-xl transition-all disabled:opacity-40 flex items-center justify-center gap-2 whitespace-nowrap cursor-pointer shadow-xs self-stretch sm:self-auto"
              >
                <Check className="w-4 h-4" />
                <span>Wybierz ten Paczkomat</span>
              </button>
            </div>
          </>
        ) : (
          /* ORLEN Paczka View */
          <div className="p-4 sm:p-6 space-y-5 overflow-y-auto flex-1 font-sans">
            <div className="p-4 sm:p-5 rounded-2xl bg-stone-50 border border-stone-200/80 space-y-3">
              <div className="flex items-start gap-3">
                <div className="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-[#E0115F] shrink-0 mt-0.5 shadow-2xs">
                  <MapPin className="w-5 h-5" />
                </div>
                <div className="space-y-1">
                  <h4 className="font-semibold text-sm sm:text-base text-gray-900">
                    1. Znajdź punkt na oficjalnej mapie ORLEN Paczki
                  </h4>
                  <p className="text-xs sm:text-sm text-gray-500 leading-relaxed">
                    Otwórz mapę, aby zlokalizować najbliższy automat lub punkt partnerski i skopiować jego nagłówek.
                  </p>
                </div>
              </div>

              <a
                href="https://www.orlenpaczka.pl/pokaz-punkty/"
                target="_blank"
                rel="noopener noreferrer"
                className="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white hover:bg-gray-55 border border-gray-300 hover:border-gray-400 text-gray-900 font-semibold text-xs sm:text-sm shadow-xs transition-all cursor-pointer group"
              >
                <span>Otwórz oficjalną mapę ORLEN Paczka</span>
                <ExternalLink className="w-4 h-4 text-gray-500 group-hover:text-gray-900 group-hover:translate-x-0.5 transition-transform" />
              </a>
            </div>

            <form onSubmit={handleConfirmOrlen} className="p-4 sm:p-5 rounded-2xl bg-stone-50 border border-stone-200/80 space-y-3">
              <div className="flex items-start gap-3">
                <div className="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-700 shrink-0 mt-0.5 shadow-2xs">
                  <Search className="w-4 h-4" />
                </div>
                <div className="space-y-1">
                  <h4 className="font-semibold text-sm sm:text-base text-gray-900">
                    2. Wklej nagłówek lub kod punktu
                  </h4>
                  <p className="text-xs sm:text-sm text-gray-500 leading-relaxed">
                    Wskazówka: Kodem docelowym jest numer w nagłówku (np. w <strong>Punkt Partnerski 913861</strong> kodem jest <strong>913861</strong>). Możesz wkleić cały nagłówek lub sam numer — system automatycznie wyodrębni właściwy kod.
                  </p>
                </div>
              </div>

              <div className="space-y-2 pt-1">
                <div className="flex flex-col sm:flex-row gap-2">
                  <input
                    type="text"
                    value={orlenCode}
                    onChange={(e) => {
                      setOrlenCode(e.target.value);
                      if (orlenError) setOrlenError("");
                    }}
                    placeholder="np. Punkt Partnerski 913861 lub 913861"
                    className="flex-1 px-4 py-3 rounded-xl border border-gray-300 focus:border-[#E0115F] focus:ring-1 focus:ring-[#E0115F] outline-none text-sm sm:text-base bg-white text-gray-900 transition-all placeholder:text-gray-400"
                  />
                  <button
                    type="submit"
                    disabled={!orlenCode.trim()}
                    className="inline-flex items-center justify-center gap-1.5 px-5 py-3 rounded-xl bg-gray-900 hover:bg-[#E0115F] text-white font-medium text-xs sm:text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer shrink-0 shadow-xs"
                  >
                    <Check className="w-4 h-4" />
                    <span>Zatwierdź punkt</span>
                  </button>
                </div>

                {parsedOrlen && parsedOrlen.id && (
                  <div className="p-3 bg-emerald-50/90 border border-emerald-200 rounded-xl text-xs font-sans text-emerald-900 flex items-center justify-between gap-3 animate-fadeIn">
                    <div className="flex items-center gap-2.5">
                      <div className="w-6 h-6 rounded-md bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">
                        ✓
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-bold text-emerald-950">Kod: {parsedOrlen.id}</span>
                          <span className="text-emerald-700 font-medium font-mono text-[11px] bg-emerald-100/60 px-1.5 py-0.5 rounded">
                            {parsedOrlen.name}
                          </span>
                        </div>
                        {parsedOrlen.address && parsedOrlen.address !== `Punkt odbioru ${parsedOrlen.id}` && (
                          <p className="text-emerald-700 text-xs mt-0.5">{parsedOrlen.address}</p>
                        )}
                      </div>
                    </div>
                    <span className="text-[11px] font-semibold text-emerald-700 bg-white px-2 py-0.5 rounded-md border border-emerald-200 shrink-0">
                      Gotowy
                    </span>
                  </div>
                )}

                {orlenError && (
                  <p className="text-xs text-red-600 font-medium">{orlenError}</p>
                )}
              </div>
            </form>

            <div className="p-3 sm:p-4 px-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-xs font-sans text-gray-500 mt-auto">
              <span className="hidden sm:inline">Kod punktu zostanie automatycznie dodany do zamówienia.</span>
              <button
                type="button"
                onClick={onClose}
                className="w-full sm:w-auto px-4 py-2 bg-white border border-gray-200 hover:border-gray-300 text-gray-700 hover:text-black rounded-xl font-medium transition-all text-center cursor-pointer"
              >
                Anuluj i zamknij
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
