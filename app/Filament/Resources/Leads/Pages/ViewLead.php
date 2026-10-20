<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadActions;
use App\Filament\Resources\Leads\LeadResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return 'Lead '.$this->getRecord()->reference;
    }

    public function getSubheading(): ?string
    {
        $lead = $this->getRecord();

        return trim(($lead->contact?->name ?? '').' · '.($lead->trip?->title ?? 'Tailor-made'), ' ·');
    }

    protected function getHeaderActions(): array
    {
        return [
            LeadActions::createQuote(),
            LeadActions::changeStatus(),
            LeadActions::assignToMe(),
            ActionGroup::make([
                LeadActions::whatsapp(),
                LeadActions::email(),
                EditAction::make(),
            ])->button()->label('More')->color('gray'),
        ];
    }
}
