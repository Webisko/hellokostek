# Reguły projektu (Project Rules)

## Polonizacja interfejsu (CMS Polonization)
- Wszystkie etykiety (`label`), opisy (`helperText`, `placeholder`), nagłówki sekcji (`Section::make(...)`), nazwy kolumn tabel (`TextColumn::make(...)`) oraz etykiety pól w infolistach (`TextEntry::make(...)`) muszą być zdefiniowane wyłącznie w języku polskim.
- Wszelkie statusy i enumy (np. statusy płatności, realizacji, typy produktów: Fizyczny, Cyfrowy, Usługa, Pakiet) muszą być mapowane na polskie etykiety (np. przy użyciu metody `formatStateUsing` z lokalnym mapowaniem lub dynamicznie przy użyciu odpowiednich słowników/enumów z polskimi etykietami).
- Przy dodawaniu nowych zasobów (Resources) lub pól do istniejących formularzy, należy bezwzględnie pilnować, aby interfejs CMS nie zawierał żadnych angielskich etykiet widocznych dla administratora.
