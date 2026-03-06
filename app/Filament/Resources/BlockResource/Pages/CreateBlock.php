<?php

namespace App\Filament\Resources\BlockResource\Pages;

use App\Filament\Resources\BlockResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBlock extends CreateRecord
{
    protected static string $resource = BlockResource::class;

    /** After create, go to blocks list (avoids 403 on view). */
    protected function getRedirectUrl(): string
    {
        return BlockResource::getUrl('index');
    }
}
