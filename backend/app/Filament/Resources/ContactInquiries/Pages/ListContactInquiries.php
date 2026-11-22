<?php

namespace App\Filament\Resources\ContactInquiries\Pages;

use App\Filament\Resources\ContactInquiries\ContactInquiryResource;
use App\Models\ContactInquiry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListContactInquiries extends ListRecords
{
    protected static string $resource = ContactInquiryResource::class;

    #[Url]
    public ?string $record = null;

    public function mount(): void
    {
        parent::mount();

        if ($this->record && ContactInquiry::query()->whereKey($this->record)->exists()) {
            $this->bootedInteractsWithTable();
            $this->mountTableAction('edit', $this->record);
        }
    }

    public function getTabs(): array
    {
        $counts = ContactInquiry::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $allCount = (int) $counts->sum();
        $newCount = (int) ($counts['new'] ?? 0);
        $inProgressCount = (int) ($counts['in_progress'] ?? 0);
        $acceptedCount = (int) ($counts['accepted'] ?? 0);
        $completedCount = (int) ($counts['completed'] ?? 0);

        return [
            'wszystkie' => Tab::make('Wszystkie')
                ->badge($allCount),
            'nowe' => Tab::make('Nowe')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'new'))
                ->badge($newCount > 0 ? $newCount : null)
                ->badgeColor('danger'),
            'w_trakcie' => Tab::make('W trakcie')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'in_progress'))
                ->badge($inProgressCount > 0 ? $inProgressCount : null)
                ->badgeColor('warning'),
            'zaakceptowane' => Tab::make('Zaakceptowane')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'accepted'))
                ->badge($acceptedCount > 0 ? $acceptedCount : null)
                ->badgeColor('info'),
            'zakonczone' => Tab::make('Zakończone')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'completed'))
                ->badge($completedCount > 0 ? $completedCount : null)
                ->badgeColor('success'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
