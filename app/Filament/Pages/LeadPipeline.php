<?php

namespace App\Filament\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use UnitEnum;

/** Kanban board of leads by pipeline stage with drag & drop between columns. */
class LeadPipeline extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Pipeline';

    protected static ?string $title = 'Lead pipeline';

    protected static ?string $slug = 'pipeline';

    protected string $view = 'filament.pages.lead-pipeline';

    /** Max cards rendered per column. */
    public const PER_COLUMN = 60;

    #[Url]
    public bool $onlyMine = false;

    #[Url]
    public bool $showLost = false;

    public static function canAccess(): bool
    {
        return LeadResource::canViewAny();
    }

    /** @return array<string, array{status: LeadStatus, leads: Collection<int, Lead>, count: int, value: float, collapsed: bool}> */
    public function getColumns(): array
    {
        $columns = [];

        foreach (LeadStatus::cases() as $status) {
            $query = $this->baseQuery()->where('status', $status);
            $collapsed = $status === LeadStatus::Lost && ! $this->showLost;

            $columns[$status->value] = [
                'status' => $status,
                'count' => (clone $query)->count(),
                'value' => (float) (clone $query)->sum('estimated_value'),
                'collapsed' => $collapsed,
                'leads' => $collapsed ? collect() : $query
                    ->with(['contact', 'trip', 'owner'])
                    ->orderBy('sort')
                    ->latest()
                    ->limit(self::PER_COLUMN)
                    ->get(),
            ];
        }

        return $columns;
    }

    /**
     * Called from the board when a card is dropped.
     *
     * @param  array<int, int|string>  $orderedIds  card ids of the target column, top to bottom
     */
    public function moveLead(int $id, string $status, array $orderedIds = []): void
    {
        $newStatus = LeadStatus::tryFrom($status);
        $lead = $this->baseQuery()->find($id);

        if (! $newStatus || ! $lead) {
            return;
        }

        if (Gate::denies('update', $lead)) {
            Notification::make()->danger()->title('You cannot move this lead.')->send();

            return;
        }

        DB::transaction(function () use ($lead, $newStatus, $orderedIds): void {
            if ($lead->status !== $newStatus) {
                $lead->status = $newStatus;

                if (in_array($newStatus, [LeadStatus::DepositPaid, LeadStatus::Confirmed], true)) {
                    $lead->converted_at ??= now();
                }

                $lead->save();
            }

            $ids = collect($orderedIds)->map(fn ($v) => (int) $v)->filter()->values();
            $editable = $this->baseQuery()->whereIn('id', $ids)->where('status', $newStatus)->get()->keyBy('id');

            foreach ($ids as $position => $leadId) {
                $item = $editable->get($leadId);

                if ($item && (int) $item->sort !== $position && Gate::allows('update', $item)) {
                    $item->sort = $position;
                    $item->saveQuietly();
                }
            }
        });

        if ($newStatus === LeadStatus::Lost && blank($lead->lost_reason)) {
            Notification::make()->warning()->title('Lead marked as lost')->body('Add a lost reason on the lead when you can.')->send();
        }
    }

    public function toggleMine(): void
    {
        $this->onlyMine = ! $this->onlyMine;
    }

    public function toggleLost(): void
    {
        $this->showLost = ! $this->showLost;
    }

    public function leadUrl(Lead $lead): string
    {
        return LeadResource::getUrl('view', ['record' => $lead]);
    }

    protected function baseQuery()
    {
        return LeadResource::getEloquentQuery()
            ->when($this->onlyMine, fn ($q) => $q->where('owner_id', auth()->id()));
    }
}
