<?php

namespace App\Filament\Pages;

use App\Models\AddToCartCount;
use App\Models\Event;
use App\Models\Site;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class Analytics extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Analytics';

    protected static string $view = 'filament.pages.analytics';

    protected static ?string $title = 'Analytics';

    public function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\AddToCartCountsWidget::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Event::query()
                    ->whereIn('site_id', Site::where('user_id', auth()->id())->pluck('id'))
                    ->with(['site:id,name', 'block:id,name'])
            )
            ->columns([
                TextColumn::make('event_name')->badge(),
                TextColumn::make('site.name')->label('Site'),
                TextColumn::make('block.name')->label('Block')->placeholder('—'),
                TextColumn::make('event_at')->dateTime()->sortable(),
                TextColumn::make('page_url')->limit(50)->tooltip(fn ($state) => $state),
            ])
            ->filters([
                SelectFilter::make('site_id')
                    ->relationship('site', 'name', fn (Builder $q) => $q->where('user_id', auth()->id()))
                    ->label('Site'),
                SelectFilter::make('event_name')
                    ->options([
                        Event::EVENT_IMPRESSION => 'Impression',
                        Event::EVENT_CLICK => 'Click',
                        Event::EVENT_CUSTOM => 'Custom',
                    ]),
            ])
            ->defaultSort('event_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
