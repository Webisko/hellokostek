<?php

namespace App\Filament\Resources\ProductReviews\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Szczegóły opinii')->columnSpanFull()
                ->columns(2)
                ->schema([
                    \Filament\Forms\Components\Hidden::make('sort_order')
                        ->default(fn () => (\App\Models\ProductReview::max('sort_order') ?? 0) + 10),
                    Select::make('emoji')
                        ->label('Ikona emoji')
                        ->placeholder('Wybierz ikonę emoji')
                        ->options(fn ($state): array => self::emojiOptions(is_string($state) ? $state : null))
                        ->native(false)
                        ->createOptionForm([
                            TextInput::make('emoji')
                                ->label('Własna ikona emoji')
                                ->placeholder('np. 🐴 lub 💍')
                                ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Na Windowsie naciśnij Win + kropka (.), na Macu Ctrl + Cmd + Spacja.')
                                ->required()
                                ->maxLength(16),
                        ])
                        ->createOptionModalHeading('Dodaj własne emoji')
                        ->createOptionAction(fn (\Filament\Actions\Action $action) => $action->tooltip('Wpisz własne emoji'))
                        ->createOptionUsing(fn (array $data): string => trim((string) $data['emoji'])),
                    Select::make('rating')
                        ->label('Ocena (gwiazdki)')
                        ->options([
                            5 => '⭐⭐⭐⭐⭐ (5 gwiazdek)',
                            4 => '⭐⭐⭐⭐ (4 gwiazdki)',
                            3 => '⭐⭐⭐ (3 gwiazdki)',
                            2 => '⭐⭐ (2 gwiazdki)',
                            1 => '⭐ (1 gwiazdka)',
                        ])
                        ->default(5)
                        ->required()
                        ->native(false),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'publiczny' => 'Publiczny (widoczna na stronie)',
                            'szkic' => 'Szkic (niewidoczna)',
                        ])
                        ->default('publiczny')
                        ->required()
                        ->native(false),
                    TextInput::make('customer_name')
                        ->label('Autor')
                        ->placeholder('np. Kasia')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('meta')
                        ->label('Opis / kontekst')
                        ->placeholder('np. Portret z trzema psami')
                        ->maxLength(255),
                    Textarea::make('comment')
                        ->label('Treść opinii')
                        ->rows(5)
                        ->columnSpanFull()
                        ->required()
                        ->maxLength(2000),
                ]),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private static function emojiOptions(?string $currentValue = null): array
    {
        $options = [
            '✨' => '✨ Zachwyt / ogólna',
            '🎨' => '🎨 Malarstwo / rysunek',
            '🖼️' => '🖼️ Obraz / portret',
            '🖌️' => '🖌️ Pędzel / warsztat',
            '❤️' => '❤️ Serce / polecenie',
            '🥰' => '🥰 Wzruszenie',
            '😍' => '😍 Miłość / zachwyt',
            '🙂' => '🙂 Uśmiech',
            '🐶' => '🐶 Pies / pupil',
            '🐱' => '🐱 Kot / pupil',
            '🐾' => '🐾 Zwierzęta',
            '👨‍👩‍👧' => '👨‍👩‍👧 Rodzina',
            '🎁' => '🎁 Prezent',
            '💐' => '💐 Kwiaty / natura',
            '🏡' => '🏡 Dom / wnętrze',
            '🌟' => '🌟 Gwiazda',
            '👏' => '👏 Brawo',
            '🙏' => '🙏 Podziękowanie',
            '💯' => '💯 Polecam',
            '🔥' => '🔥 Super',
        ];

        $current = trim((string) $currentValue);
        if ($current !== '' && ! array_key_exists($current, $options)) {
            $options[$current] = $current . ' (własna ikona)';
        }

        return $options;
    }
}
