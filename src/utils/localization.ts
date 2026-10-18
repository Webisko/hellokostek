export function parseLocalizedString(val: unknown): string {
  if (!val) return "";
  if (typeof val === "object") {
    const obj = val as Record<string, unknown>;
    return String(obj.pl || obj.PL || obj.en || obj.EN || Object.values(obj)[0] || "");
  }
  const str = String(val).trim();
  if (str.startsWith("{") && str.endsWith("}")) {
    try {
      const parsed = JSON.parse(str);
      if (parsed && typeof parsed === "object") {
        return String(parsed.pl || parsed.PL || parsed.en || parsed.EN || Object.values(parsed)[0] || str);
      }
    } catch {
      // ignore invalid JSON
    }
  }
  return str;
}
