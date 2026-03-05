<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use App\Models\Site;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateSite extends CreateRecord
{
    protected static string $resource = SiteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['site_key'] = Str::random(32);
        $data['site_secret'] = Str::random(64);

        if (isset($data['allowed_domains']) && is_array($data['allowed_domains'])) {
            $data['allowed_domains'] = array_values(array_filter(array_map('trim', $data['allowed_domains'])));
        } else {
            $data['allowed_domains'] = [];
        }

        return $data;
    }
}
