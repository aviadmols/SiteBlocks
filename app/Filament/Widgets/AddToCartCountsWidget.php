<?php

namespace App\Filament\Widgets;

use App\Models\AddToCartCount;
use App\Models\Site;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AddToCartCountsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $siteIds = Site::where('user_id', auth()->id())->pluck('id');

        return $table
            ->query(
                AddToCartCount::query()
                    ->whereIn('site_id', $siteIds)
                    ->where('count', '>', 0)
                    ->with('site:id,name')
                    ->orderByDesc('count')
                    ->limit(20)
            )
            ->columns([
                TextColumn::make('site.name')->label('Site'),
                TextColumn::make('scope')->badge(),
                TextColumn::make('product_id')->label('Product ID')->placeholder('—'),
                TextColumn::make('variant_id')->label('Variant ID')->placeholder('—'),
                TextColumn::make('count')->sortable(),
            ])
            ->heading('Top Add to Cart counts')
            ->paginated(false);
    }
}
