<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['subtotal'], $data['total'], $data['deposit_amount']);
        $data['created_by_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        QuoteResource::recalculate($this->getRecord());
    }

    protected function getRedirectUrl(): string
    {
        return QuoteResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
