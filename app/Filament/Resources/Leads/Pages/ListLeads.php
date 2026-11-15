<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Enums\LeadStatus;
use App\Filament\Pages\LeadPipeline;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Support\ListTabs;
use App\Support\Permissions;
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

    /** Statuses after which a lead needs no more chasing. */
    public const CLOSED = [LeadStatus::Lost, LeadStatus::Travelled];

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

    /** @return array<string, Tab> */
    public function getTabs(): array
    {
        $leads = LeadResource::getEloquentQuery();

        return [
            'all' => Tab::make('All'),
            ...(auth()->user()?->can(Permissions::CRM_MANAGE) ? [
                'mine' => ListTabs::counted('My open leads', $leads, fn (Builder $query): Builder => $query
                    ->where('owner_id', auth()->id())
                    ->whereNotIn('status', self::CLOSED), 'primary'),
            ] : []),
            'new' => ListTabs::counted('New', $leads, fn (Builder $query): Builder => $query->where('status', LeadStatus::New), 'warning'),
            'follow_up' => ListTabs::counted('Follow-up due', $leads, fn (Builder $query): Builder => $query
                ->where('next_follow_up_at', '<=', now())
                ->whereNotIn('status', self::CLOSED), 'danger'),
            'active' => ListTabs::counted('Active pipeline', $leads, fn (Builder $query): Builder => $query->whereIn('status', self::ACTIVE)),
            'won' => ListTabs::counted('Won', $leads, fn (Builder $query): Builder => $query->whereIn('status', self::WON), 'success'),
            'lost' => ListTabs::counted('Lost', $leads, fn (Builder $query): Builder => $query->where('status', LeadStatus::Lost)),
        ];
    }
}
