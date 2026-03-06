<?php

namespace App\Filament\Resources\BlockResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AddToCartCountsRelationManager extends RelationManager
{
    protected static string $relationship = 'addToCartCounts';

    protected static ?string $title = 'Block analytics: add to cart counts';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('product_slug')->label('Product slug')->placeholder('—'),
                Tables\Columns\TextColumn::make('product_id')->label('Product ID')->placeholder('—'),
                Tables\Columns\TextColumn::make('variant_id')->label('Variant ID')->placeholder('—'),
                Tables\Columns\TextColumn::make('scope')->badge(),
                Tables\Columns\TextColumn::make('count')->sortable(),
            ])
            ->defaultSort('count', 'desc')
            ->paginated([10, 25, 50])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
