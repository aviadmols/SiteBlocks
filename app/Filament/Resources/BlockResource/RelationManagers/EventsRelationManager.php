<?php

namespace App\Filament\Resources\BlockResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Block analytics: events';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('event_name')->badge(),
                Tables\Columns\TextColumn::make('event_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('page_url')->limit(50)->tooltip(fn ($state) => $state),
                Tables\Columns\TextColumn::make('payload_product')
                    ->label('Product (click payload)')
                    ->getStateUsing(function ($record) {
                        $p = $record->payload ?? [];
                        if (empty($p)) {
                            return null;
                        }
                        $title = $p['product_title'] ?? null;
                        $price = $p['product_price'] ?? null;
                        $parts = array_filter([$title, $price]);

                        return $parts ? implode(' · ', $parts) : null;
                    })
                    ->placeholder('—')
                    ->limit(40)
                    ->tooltip(fn ($record) => isset($record->payload['product_url']) ? $record->payload['product_url'] : null),
            ])
            ->defaultSort('event_at', 'desc')
            ->paginated([10, 25, 50])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
