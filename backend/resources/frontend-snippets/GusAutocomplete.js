/**
 * GusAutocomplete.js
 * 
 * Client-side integration utility to fetch company data by NIP
 * and populate checkout address fields.
 */

export class GusAutocomplete {
  /**
   * Initialize autocomplete binding on a NIP input field.
   * 
   * @param {Object} options
   * @param {string} options.nipInputSelector - CSS selector for the NIP input field
   * @param {string} options.loadingIndicatorSelector - CSS selector for loading spinner/text
   * @param {Object} options.fields - Map of destination selectors
   * @param {string} options.fields.companyName - Selector for company name field
   * @param {string} options.fields.street - Selector for street/building address field
   * @param {string} options.fields.postalCode - Selector for postcode field
   * @param {string} options.fields.city - Selector for city field
   */
  constructor(options) {
    this.nipInput = document.querySelector(options.nipInputSelector);
    this.loadingIndicator = document.querySelector(options.loadingIndicatorSelector);
    this.fields = {
      companyName: document.querySelector(options.fields.companyName),
      street: document.querySelector(options.fields.street),
      postalCode: document.querySelector(options.fields.postalCode),
      city: document.querySelector(options.fields.city),
    };

    if (this.nipInput) {
      this.init();
    }
  }

  init() {
    this.nipInput.addEventListener('input', () => {
      // Normalize NIP (digits only)
      let nipVal = this.nipInput.value.replace(/\D/g, '');
      this.nipInput.value = nipVal.slice(0, 10);
      
      // Auto-trigger when exactly 10 digits are inputted
      if (this.nipInput.value.length === 10) {
        this.fetchCompanyData(this.nipInput.value);
      }
    });
  }

  async fetchCompanyData(nip) {
    this.toggleLoading(true);

    try {
      const response = await fetch(`/api/b2b/gus/${nip}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        }
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.message || 'Nie udało się pobrać danych z GUS.');
      }

      if (result.success && result.data) {
        const company = result.data;
        
        // Auto-populate fields
        if (this.fields.companyName) this.fields.companyName.value = company.company_name || '';
        if (this.fields.street) this.fields.street.value = company.street || '';
        if (this.fields.postalCode) this.fields.postalCode.value = company.postal_code || '';
        if (this.fields.city) this.fields.city.value = company.city || '';

        // Dispatch change events so frontend frameworks (Astro, Svelte, React) pick up the updates
        Object.values(this.fields).forEach(input => {
          if (input) {
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });
      } else if (result.status === 'timeout_fallback') {
        console.warn('GUS Autocomplete fallback: API GUS/MF jest offline lub przeciążone. Umożliwiono ręczne wpisanie danych.');
        
        const fallbackEvent = new CustomEvent('gus-autocomplete-fallback', {
          bubbles: true,
          detail: {
            message: result.message || 'API GUS/MF jest chwilowo niedostępne. Wprowadź dane firmy ręcznie.',
            status: result.status
          }
        });
        this.nipInput.dispatchEvent(fallbackEvent);
      }
    } catch (error) {
      console.warn('GUS Autocomplete error:', error.message);
      // Optional: Show error badge on UI
    } finally {
      this.toggleLoading(false);
    }
  }

  toggleLoading(show) {
    if (this.loadingIndicator) {
      if (show) {
        this.loadingIndicator.classList.remove('hidden');
      } else {
        this.loadingIndicator.classList.add('hidden');
      }
    }
  }
}
