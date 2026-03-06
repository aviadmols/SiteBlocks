<?php

namespace App\Filament\Resources\BlockResource\Pages;

use App\Filament\Resources\BlockResource;
use App\Models\Block;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBlock extends ViewRecord
{
    protected static string $resource = BlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    /**
     * Resolve the record using the resource's scoped query (user's blocks only).
     * Avoids 403 when redirecting here after save.
     */
    protected function resolveRecord(int|string $key): Block
    {
        $id = is_numeric($key) ? (int) $key : $key;
        return BlockResource::getEloquentQuery()
            ->whereKey($id)
            ->with('site')
            ->firstOrFail();
    }
}
