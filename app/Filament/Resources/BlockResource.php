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
                        Block::TYPE_VIDEO_CALL_BUTTON => 'Video Call (WhatsApp) Button',
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
                    ->description('General: optional rules for when this block is shown (URL, page type). Applies to all block types.')
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

    /**
     * Common settings shared by all block types (e.g. Custom CSS). Use spread when building type schema.
     *
     * @return list<\Filament\Forms\Components\Component>
     */
    protected static function getCommonBlockSettingsFields(string $placeholder = ''): array
    {
        return [
            Forms\Components\Textarea::make('settings.custom_css')
                ->label('Custom CSS')
                ->placeholder($placeholder ?: '/* block-specific styles */')
                ->rows(4)
                ->columnSpanFull(),
        ];
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
                    ->default(1),
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
                ...static::getCommonBlockSettingsFields('.embed-add-to-cart-count { font-size: 14px; color: #333; }'),
            ];
        }

        if ($type === Block::TYPE_VIDEO_CALL_BUTTON) {
            return [
                Forms\Components\TextInput::make('settings.phone')
                    ->label('Phone (WhatsApp, with country code, no +)')
                    ->placeholder('97239539683')
                    ->required()
                    ->maxLength(32),
                Forms\Components\TextInput::make('settings.open_days')
                    ->label('Open days (0=Sun, 5=Fri, comma-separated)')
                    ->placeholder('0,1,2,3,4,5')
                    ->default('0,1,2,3,4,5')
                    ->maxLength(64),
                Forms\Components\TextInput::make('settings.open_time')
                    ->label('Open time (HH:MM)')
                    ->placeholder('10:30')
                    ->default('10:30')
                    ->maxLength(8),
                Forms\Components\TextInput::make('settings.close_time')
                    ->label('Close time (HH:MM)')
                    ->placeholder('18:00')
                    ->default('18:00')
                    ->maxLength(8),
                Forms\Components\TextInput::make('settings.friday_close')
                    ->label('Friday close time (HH:MM)')
                    ->placeholder('14:00')
                    ->default('14:00')
                    ->maxLength(8),
                Forms\Components\TextInput::make('settings.timezone')
                    ->label('Timezone')
                    ->placeholder('Asia/Jerusalem')
                    ->default('Asia/Jerusalem')
                    ->maxLength(64),
                Forms\Components\TextInput::make('settings.button_text')
                    ->label('Button text')
                    ->default('התחל שיחת וידאו לצפייה במוצר')
                    ->maxLength(255),
                Forms\Components\TextInput::make('settings.target_selector')
                    ->label('Target selector (where to insert the button)')
                    ->placeholder('[data-product-form], .product-form')
                    ->default('[data-product-form], form[action*="/cart/add"]')
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
                Forms\Components\Textarea::make('settings.message_template')
                    ->label('WhatsApp message template (use {{product_title}}, {{product_price}}, {{product_url}})')
                    ->placeholder('*התחלת שיחת וידאו:*\n*{{product_title}}*\n{{product_price}}\n\n{{product_url}}')
                    ->rows(4)
                    ->columnSpanFull(),
                ...static::getCommonBlockSettingsFields('.embed-video-call-button__btn { font-size: 16px; }'),
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
        return [
            \App\Filament\Resources\BlockResource\RelationManagers\EventsRelationManager::class,
            \App\Filament\Resources\BlockResource\RelationManagers\AddToCartCountsRelationManager::class,
        ];
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
        return parent::getEloquentQuery()
            ->whereHas('site', fn (Builder $q) => $q->where('user_id', auth()->id()))
            ->with('site:id,name');
    }

    public static function getPolicy(): ?string
    {
        return \App\Policies\BlockPolicy::class;
    }
}
