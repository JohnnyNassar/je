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

    /** Total number of products, shown as a badge next to "Products" in the sidebar. */
    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total products';
    }

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
                    ->label('Main image')
                    ->helperText('The big cover photo shown on the product page.')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([null, '1:1', '4:3', '3:4'])
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
                Forms\Components\FileUpload::make('gallery')
                    ->label('More images (gallery)')
                    ->helperText('Optional. Extra photos shown after the main image. Drag to reorder.')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([null, '1:1', '4:3', '3:4'])
                    ->multiple()
                    ->reorderable()
                    ->appendFiles()
                    ->disk('public')
                    ->directory('products')
                    ->imagePreviewHeight('120')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Forms\Components\Toggle::make('is_featured')
                    ->label('Featured on home page')
                    ->helperText('Highlights this product at the top of the catalog.')
                    ->default(false),
                Forms\Components\Section::make('Options (Colour / Size / Dimension)')
                    ->description('Optional. Define up to 3 attributes shoppers choose from, each with its values (English + Arabic). Then use “Build combinations” in the Variations section below.')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->relationship()
                            ->hiddenLabel()
                            ->maxItems(3)
                            ->schema([
                                Forms\Components\TextInput::make('name_en')
                                    ->label('Attribute (English)')
                                    ->placeholder('Colour')
                                    ->required()
                                    ->maxLength(60),
                                Forms\Components\TextInput::make('name_ar')
                                    ->label('Attribute (Arabic)')
                                    ->placeholder('اللون')
                                    ->maxLength(60),
                                Forms\Components\Repeater::make('values')
                                    ->label('Values')
                                    ->schema([
                                        Forms\Components\TextInput::make('en')
                                            ->label('Value (English)')
                                            ->placeholder('Red')
                                            ->required()
                                            ->maxLength(60),
                                        Forms\Components\TextInput::make('ar')
                                            ->label('Value (Arabic)')
                                            ->placeholder('أحمر')
                                            ->maxLength(60),
                                    ])
                                    ->columns(2)
                                    ->addActionLabel('Add value')
                                    ->defaultItems(1)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['name_en'] ?? null)
                            ->orderColumn('position')
                            ->collapsed()
                            ->collapsible()
                            ->addActionLabel('Add an attribute')
                            ->defaultItems(0),
                    ]),
                Forms\Components\Section::make('Variations')
                    ->description('Optional. Each variation has its own stock, and optionally its own price and photo. When a product has variations, the Stock field above is set automatically from their total.')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('buildCombinations')
                                ->label('Build combinations from options')
                                ->icon('heroicon-m-squares-plus')
                                ->color('gray')
                                ->visible(fn (\Filament\Forms\Get $get): bool => filled($get('options')))
                                ->requiresConfirmation()
                                ->modalDescription('Creates one variation per combination of your options. Stock, price and photo already set for a combination are kept; combinations that no longer exist are removed.')
                                ->action(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set): void {
                                    $axes = [];
                                    foreach ($get('options') ?? [] as $opt) {
                                        $name = trim((string) ($opt['name_en'] ?? ''));
                                        $vals = collect($opt['values'] ?? [])
                                            ->map(fn ($v) => trim((string) ($v['en'] ?? '')))
                                            ->filter()
                                            ->values()
                                            ->all();
                                        if ($name !== '' && $vals) {
                                            $axes[$name] = $vals;
                                        }
                                    }

                                    if (! $axes) {
                                        return;
                                    }

                                    // Cartesian product of all axis values.
                                    $combos = [[]];
                                    foreach ($axes as $name => $vals) {
                                        $next = [];
                                        foreach ($combos as $combo) {
                                            foreach ($vals as $val) {
                                                $next[] = $combo + [$name => $val];
                                            }
                                        }
                                        $combos = $next;
                                    }

                                    // Preserve stock/price/photo for combinations that already exist.
                                    $existing = collect($get('variants') ?? []);
                                    $rows = [];
                                    foreach ($combos as $i => $combo) {
                                        $match = $existing->first(fn ($v) => ($v['option_values'] ?? null) == $combo);
                                        $rows[] = [
                                            'name' => implode(' / ', array_values($combo)),
                                            'option_values' => $combo,
                                            'stock' => $match['stock'] ?? 0,
                                            'price' => $match['price'] ?? null,
                                            'image_path' => $match['image_path'] ?? null,
                                            'position' => $i + 1,
                                        ];
                                    }

                                    $set('variants', $rows);
                                }),
                        ]),
                        Forms\Components\Repeater::make('variants')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\Hidden::make('option_values'),
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
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([null, '1:1', '4:3', '3:4'])
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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_ar')
                    ->searchable()
                    ->sortable(),
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
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('viewOnSite')
                    ->label('View on website')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Product $record) => route('catalog.show', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('coverLogo')
                    ->label('Cover logo')
                    ->icon('heroicon-m-paint-brush')
                    ->color('gray')
                    ->visible(fn (Product $record): bool => (bool) $record->image_path)
                    ->url(fn (Product $record) => route('admin.image-cover', $record))
                    ->openUrlInNewTab(),
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
