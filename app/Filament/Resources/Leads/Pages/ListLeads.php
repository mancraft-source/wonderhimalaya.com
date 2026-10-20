<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadStatus;
use App\Filament\Pages\LeadPipeline;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    /** Statuses that count as an open sales opportunity. */
    public const ACTIVE = [LeadStatus::Contacted, LeadStatus::Qualified, LeadStatus::Quoted];

    public const WON = [LeadStatus::DepositPaid, LeadStatus::Confirmed, LeadStatus::Travelled];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pipeline')
                ->label('Pipeline board')
                ->icon(Heroicon::OutlinedViewColumns)
                ->color('gray')
                ->url(LeadPipeline::getUrl()),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $count = fn (array $statuses): int => LeadResource::getEloquentQuery()->whereIn('status', $statuses)->count();

        return [
            'all' => Tab::make('All'),
            'new' => Tab::make('New')
                ->badge($count([LeadStatus::New]) ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::New)),
            'active' => Tab::make('Active pipeline')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', self::ACTIVE)),
            'won' => Tab::make('Won')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', self::WON)),
            'lost' => Tab::make('Lost')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', LeadStatus::Lost)),
        ];
    }
}
