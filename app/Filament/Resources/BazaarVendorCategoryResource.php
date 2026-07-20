<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BazaarVendorCategoryResource\Pages;
use App\Models\BazaarVendorCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BazaarVendorCategoryResource extends Resource
{
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = BazaarVendorCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Bazaar';

    protected static ?string $navigationLabel = 'Vendor categories';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name_en')
                        ->label('Name (English)')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug((string) $state))),

                    Forms\Components\TextInput::make('name_ar')
                        ->label('Name (Arabic)')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Used internally. Filled in for you from the English name.'),

                    Forms\Components\TextInput::make('position')
                        ->numeric()
                        ->default(0)
                        ->helperText('Controls the order in the vendor dropdown.'),

                    Forms\Components\Toggle::make('requires_health_certificate')
                        ->label('Needs a health certificate')
                        ->onColor('warning')
                        ->helperText('Turn on for anything eaten, drunk or applied to the body. Vendors cannot book this category without uploading one.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Offered to vendors')
                        ->default(true)
                        ->onColor('success'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name_ar')
                    ->label('Arabic')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('requires_health_certificate')
                    ->label('Health cert.')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-exclamation')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-minus-small')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Bookings')
                    ->counts('bookings')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('requires_health_certificate')
                    ->label('Needs health certificate'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBazaarVendorCategories::route('/'),
            'create' => Pages\CreateBazaarVendorCategory::route('/create'),
            'edit' => Pages\EditBazaarVendorCategory::route('/{record}/edit'),
        ];
    }
}
