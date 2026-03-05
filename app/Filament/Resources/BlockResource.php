<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlockResource\Pages;
use App\Models\Block;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlockResource extends Resource
{
    protected static ?string $model = Block::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Embed';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Block';

    protected static ?string $pluralModelLabel = 'Blocks';

    protected static ?string $navigationLabel = 'Blocks (widgets)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('site_id')
                    ->options(fn (): array => Site::where('user_id', auth()->id())->orderBy('name')->pluck('name', 'id')->toArray())
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        Block::TYPE_SHOPIFY_ADD_TO_CART_COUNTER => 'Shopify Add To Cart Counter',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('settings', [])),
                Forms\Components\Select::make('status')
                    ->options([
                        Block::STATUS_ACTIVE => 'Active',
                        Block::STATUS_INACTIVE => 'Inactive',
                    ])
                    ->default(Block::STATUS_ACTIVE)
                    ->required(),
                Forms\Components\Section::make('Block settings')
                    ->schema(fn (Forms\Get $get): array => static::getSettingsSchemaForType($get('type')))
                    ->visible(fn (Forms\Get $get): bool => (bool) $get('type')),
                Forms\Components\Section::make('Display rules (when to show)')
                    ->description('Optional: show block only on certain URLs or page types.')
                    ->schema([
                        Forms\Components\KeyValue::make('display_rules.url_param')
                            ->label('URL parameter (e.g. show=1)')
                            ->keyLabel('Param name')
                            ->valueLabel('Value')
                            ->reorderable(false),
                        Forms\Components\CheckboxList::make('display_rules.page_types')
                            ->label('Page types (Shopify)')
                            ->options([
                                'product' => 'Product page',
                                'collection' => 'Collection page',
                                'cart' => 'Cart page',
                                'home' => 'Home page',
                            ])
                            ->columns(2),
                        Forms\Components\TextInput::make('display_rules.url_path_contains')
                            ->label('URL path contains')
                            ->placeholder('/products/')
                            ->maxLength(255),
                    ])
                    ->collapsed(),
            ]);
    }

    protected static function getSettingsSchemaForType(?string $type): array
    {
        if ($type === Block::TYPE_SHOPIFY_ADD_TO_CART_COUNTER) {
            return [
                Forms\Components\TextInput::make('settings.target_selector')
                    ->label('Target selector (insertion anchor)')
                    ->placeholder('[data-product-form], form[action*="/cart/add"]')
                    ->maxLength(255),
                Forms\Components\Select::make('settings.insert_position')
                    ->label('Insert position')
                    ->options([
                        'after' => 'After',
                        'before' => 'Before',
                        'append' => 'Append inside',
                        'prepend' => 'Prepend inside',
                    ])
                    ->default('after'),
                Forms\Components\TextInput::make('settings.message_template')
                    ->label('Message template (use {{count}})')
                    ->default('This product was added to cart {{count}} times')
                    ->maxLength(512),
                Forms\Components\TextInput::make('settings.message_class')
                    ->label('Message CSS class')
                    ->default('embed-add-to-cart-count')
                    ->maxLength(128),
                Forms\Components\TextInput::make('settings.min_count_to_show')
                    ->label('Min count to show')
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('settings.count_scope')
                    ->label('Count scope')
                    ->options([
                        'product' => 'Product',
                        'variant' => 'Variant',
                    ])
                    ->default('variant'),
                Forms\Components\Toggle::make('settings.debug')
                    ->label('Debug (console logs)')
                    ->default(false),
            ];
        }

        return [
            Forms\Components\KeyValue::make('settings')
                ->label('Settings (key-value)')
                ->reorderable(false),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('site.name')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === Block::STATUS_ACTIVE ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('site_id')
                    ->label('Site')
                    ->options(fn (): array => Site::where('user_id', auth()->id())->orderBy('name')->pluck('name', 'id')->toArray())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Block::STATUS_ACTIVE => 'Active',
                        Block::STATUS_INACTIVE => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlocks::route('/'),
            'create' => Pages\CreateBlock::route('/create'),
            'view' => Pages\ViewBlock::route('/{record}'),
            'edit' => Pages\EditBlock::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('site', fn (Builder $q) => $q->where('user_id', auth()->id()));
    }

    public static function getPolicy(): ?string
    {
        return \App\Policies\BlockPolicy::class;
    }
}
