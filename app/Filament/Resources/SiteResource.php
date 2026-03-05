<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Embed';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Site';

    protected static ?string $pluralModelLabel = 'Sites';

    protected static ?string $navigationLabel = 'Sites (domain & script)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('primary_domain')
                    ->maxLength(255)
                    ->placeholder('example.com'),
                Forms\Components\TagsInput::make('allowed_domains')
                    ->label('Allowed domains')
                    ->placeholder('Add domain')
                    ->helperText('Domains that may load the embed script (e.g. yourstore.com).'),
                Forms\Components\Select::make('status')
                    ->options([
                        Site::STATUS_ACTIVE => 'Active',
                        Site::STATUS_INACTIVE => 'Inactive',
                    ])
                    ->default(Site::STATUS_ACTIVE)
                    ->required(),
                Forms\Components\Placeholder::make('site_key_display')
                    ->label('Site Key')
                    ->content(fn (?Site $record): string => $record ? $record->site_key : '—')
                    ->visible(fn (?Site $record): bool => (bool) $record),
                Forms\Components\Placeholder::make('site_secret_display')
                    ->label('Site Secret')
                    ->content(fn (?Site $record): string => $record ? '••••••••' : '—')
                    ->helperText('Stored securely; never expose in the embed script.')
                    ->visible(fn (?Site $record): bool => (bool) $record),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('name'),
                TextEntry::make('primary_domain'),
                TextEntry::make('site_key')->copyable()->fontFamily('mono'),
                TextEntry::make('status'),
                TextEntry::make('embed_snippet')
                    ->label('Embed snippet')
                    ->state(function (Site $record): string {
                        $url = rtrim(config('app.url'), '/').'/embed.js?site='.urlencode($record->site_key);

                        return '<script async src="'.$url.'"></script>';
                    })
                    ->html()
                    ->copyable()
                    ->copyMessage('Snippet copied'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('primary_domain')->searchable(),
                Tables\Columns\TextColumn::make('site_key')->copyable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => $state === Site::STATUS_ACTIVE ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('blocks_count')->counts('blocks')->label('Blocks'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Site::STATUS_ACTIVE => 'Active',
                        Site::STATUS_INACTIVE => 'Inactive',
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
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('user_id', auth()->id()));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'view' => Pages\ViewSite::route('/{record}'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getPolicy(): ?string
    {
        return \App\Policies\SitePolicy::class;
    }
}
