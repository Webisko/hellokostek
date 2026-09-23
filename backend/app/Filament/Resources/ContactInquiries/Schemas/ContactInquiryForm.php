<?php

namespace App\Filament\Resources\ContactInquiries\Schemas;

use App\Models\ContactInquiry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactInquiryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Szczegóły wiadomości')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Imię i nazwisko')
                        ->disabled(),
                    TextInput::make('email')
                        ->label('Adres E-mail')
                        ->disabled(),
                    TextInput::make('phone')
                        ->label('Numer telefonu')
                        ->disabled(),
                    TextInput::make('subject')
                        ->label('Temat')
                        ->disabled(),
                    Textarea::make('message')
                        ->label('Treść wiadomości')
                        ->rows(6)
                        ->disabled()
                        ->columnSpanFull(),
                ]),
            Section::make('Załączone zdjęcia / pliki referencyjne')->columnSpanFull()
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('attachments_list')
                        ->label('Przesłane pliki referencyjne')
                        ->content(function ($record): \Illuminate\Support\HtmlString {
                            $attachments = $record?->payload['attachments'] ?? [];
                            if (empty($attachments)) {
                                return new \Illuminate\Support\HtmlString('<span class="text-gray-500">Brak załączonych plików.</span>');
                            }
                            $html = '<div class="flex flex-wrap gap-4">';
                            foreach ($attachments as $index => $url) {
                                $safeUrl = htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8');
                                $html .= '<div class="flex flex-col items-center gap-1 p-2 bg-gray-50 rounded-lg border border-gray-200">';
                                $html .= '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" class="block">';
                                $html .= '<img src="' . $safeUrl . '" alt="Załącznik ' . ($index + 1) . '" class="w-32 h-32 object-cover rounded shadow-sm hover:opacity-80 transition" />';
                                $html .= '</a>';
                                $html .= '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" download class="text-xs font-semibold text-primary-600 hover:underline mt-1">Pobierz plik ' . ($index + 1) . '</a>';
                                $html .= '</div>';
                            }
                            $html .= '</div>';
                            return new \Illuminate\Support\HtmlString($html);
                        }),
                ])
                ->visible(fn ($record) => !empty($record?->payload['attachments'] ?? null)),

            Section::make('Dodatkowe dane (Formularz zapytania / Brief)')->columnSpanFull()
                ->schema([
                    KeyValue::make('scalar_payload')
                        ->label('Przesłane parametry')
                        ->valueLabel('Wartość')
                        ->keyLabel('Klucz')
                        ->disabled()
                        ->afterStateHydrated(function (KeyValue $component, $record) {
                            if (! $record || empty($record->payload)) return;
                            $filtered = [];
                            foreach ($record->payload as $k => $v) {
                                if ($k === 'attachments') continue;
                                if (is_scalar($v)) {
                                    $filtered[$k] = (string) $v;
                                }
                            }
                            $component->state($filtered);
                        })
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => !empty(array_filter($record?->payload ?? [], fn ($v, $k) => $k !== 'attachments' && is_scalar($v), ARRAY_FILTER_USE_BOTH))),
            Section::make('Zarządzanie zapytaniem')->columnSpanFull()
                ->schema([
                    Select::make('status')
                        ->label('Status zgłoszenia')
                        ->options(ContactInquiry::getStatuses())
                        ->required()
                        ->native(false),
                    Textarea::make('admin_notes')
                        ->label('Notatki administratora')
                        ->placeholder('Wpisz wewnętrzne notatki dotyczące kontaktu z tym klientem...')
                        ->rows(4),
                ]),
            Section::make('Metadane połączenia')->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('ip_address')
                        ->label('Adres IP')
                        ->disabled(),
                    TextInput::make('user_agent')
                        ->label('Przeglądarka (User Agent)')
                        ->disabled(),
                ])
                ->collapsed(),
        ]);
    }
}
