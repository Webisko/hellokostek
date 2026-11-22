<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    #[Url]
    public ?string $action = null;

    public function mount(): void
    {
        parent::mount();

        if ($this->action === 'create') {
            $this->mountAction('create');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-plus')
                ->slideOver()
                ->modalWidth('7xl')
                ->mutateFormDataUsing(function (array $data): array {
                    if (blank($data['slug'] ?? null) && filled($data['name'] ?? null)) {
                        $baseSlug = Str::slug((string) $data['name']);
                        $slug = $baseSlug !== '' ? $baseSlug : 'praca';
                        $suffix = 2;
                        while (\App\Models\Product::withTrashed()->where('slug', $slug)->exists()) {
                            $slug = ($baseSlug !== '' ? $baseSlug : 'praca') . '-' . $suffix;
                            $suffix++;
                        }
                        $data['slug'] = $slug;
                    }

                    if (blank($data['sku'] ?? null) && filled($data['slug'] ?? null)) {
                        $data['sku'] = Str::upper((string) $data['slug']);
                    }

                    if (isset($data['print_regular_price']) && filled($data['print_regular_price'])) {
                        $data['regular_price_amount'] = (int) round(((float) $data['print_regular_price']) * 100);
                    } elseif (isset($data['original_regular_price']) && filled($data['original_regular_price'])) {
                        $data['regular_price_amount'] = (int) round(((float) $data['original_regular_price']) * 100);
                    } else {
                        $data['regular_price_amount'] = 0;
                    }

                    return $data;
                })
                ->after(function (\App\Models\Product $record, array $data): void {
                    \App\Models\Product::syncVariantsFromData($record, $data);
                }),
        ];
    }
}