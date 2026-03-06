<?php

namespace App\Filament\Resources\BlockResource\Pages;

use App\Filament\Resources\BlockResource;
use App\Models\Block;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlock extends EditRecord
{
    protected static string $resource = BlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Resolve the record using the resource's scoped query (user's blocks only).
     */
    /** After save, go to blocks list (avoids 403 on view). */
    protected function getRedirectUrl(): string
    {
        return BlockResource::getUrl('index');
    }

    protected function resolveRecord(int|string $key): Block
    {
        return BlockResource::getEloquentQuery()
            ->whereKey($key)
            ->with('site')
            ->firstOrFail();
    }
}
