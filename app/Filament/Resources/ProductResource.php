<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name_en')
                    ->searchable(['name_en', 'name_ar', 'slug'])
                    ->preload()
                    ->placeholder('— uncategorized —')
                    ->helperText('Pick a category, or type to search.'),
                Forms\Components\TextInput::make('name_en')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name_ar')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Textarea::make('description_en')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description_ar')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix(\App\Models\Setting::get('currency_symbol')),
                Forms\Components\TextInput::make('cost_price')
                    ->label('Cost price (what you paid)')
                    ->numeric()
                    ->minValue(0)
                    ->prefix(\App\Models\Setting::get('currency_symbol'))
                    ->helperText('Never shown to customers — used to track profit margin. Visible to admins and any staff granted cost access.')
                    ->visible(fn () => auth()->user()?->canViewCost()),
                Forms\Components\TextInput::make('compare_at_price')
                    ->label('Original price (for "Save %" badge)')
                    ->numeric()
                    ->prefix(\App\Models\Setting::get('currency_symbol'))
                    ->helperText('Optional. When higher than current price, shows a red "Save X%" badge.'),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Total stock. If you add variations below, this is set automatically from their stock.'),
                Forms\Components\FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('products')
                    ->imagePreviewHeight('200')
                    ->hintAction(
                        \Filament\Forms\Components\Actions\Action::make('chooseFromLibrary')
                            ->label('Choose from media library')
                            ->icon('heroicon-o-photo')
                            ->modalHeading('Choose an existing image')
                            ->modalDescription('Showing your most-recent uploads. Filter by filename or click any to pick.')
                            ->modalContent(fn () => view('filament.components.media-picker', [
                                'statePath' => 'data.image_path',
                                'dirs' => ['products', 'hero'],
                            ]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                            ->modalWidth('5xl')
                    ),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Featured on home page')
                    ->helperText('Highlights this product at the top of the catalog.')
                    ->default(false),
                Forms\Components\Section::make('Variations')
                    ->description('Optional. Add colors/sizes, each with its own stock. When a product has variations, the Stock field above is set automatically from their total.')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Option')
                                    ->placeholder('e.g. Red / M')
                                    ->required()
                                    ->maxLength(120)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('stock')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('price')
                                    ->label('Price override')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix(\App\Models\Setting::get('currency_symbol'))
                                    ->placeholder('Defaults to product price'),
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Image override')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products')
                                    ->imagePreviewHeight('90')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->orderColumn('position')
                            ->collapsed()
                            ->collapsible()
                            ->addActionLabel('Add a variation')
                            ->defaultItems(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_en')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name_en')
                    ->label('Category')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => money_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Cost')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : money_format($state))
                    ->sortable()
                    ->visible(fn () => auth()->user()?->canViewCost())
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('profit')
                    ->label('Profit')
                    ->state(fn ($record) => $record->profit)
                    ->formatStateUsing(fn ($state, $record) => $state === null
                        ? '—'
                        : money_format($state) . ($record->margin_percentage !== null ? " ({$record->margin_percentage}%)" : ''))
                    ->color(fn ($state) => $state === null ? 'gray' : ($state < 0 ? 'danger' : 'success'))
                    ->visible(fn () => auth()->user()?->canViewCost())
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('public_url')
                    ->label('Link')
                    ->state(fn ($record) => route('catalog.show', $record))
                    ->formatStateUsing(fn () => 'Copy link')
                    ->icon('heroicon-m-link')
                    ->copyable()
                    ->copyMessage('Link copied — paste into WhatsApp')
                    ->copyMessageDuration(2000)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image_path')
                    ->size(28),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('setCategory')
                        ->label('Set category')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Select::make('category_id')
                                ->label('Category')
                                ->options(fn () => \App\Models\Category::orderBy('position')->orderBy('name_en')->pluck('name_en', 'id'))
                                ->searchable()
                                ->placeholder('— remove category —'),
                        ])
                        ->action(fn (array $data, \Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['category_id' => $data['category_id'] ?? null]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
