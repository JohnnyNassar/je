<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BazaarTableResource\Pages;
use App\Models\BazaarTable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BazaarTableResource extends Resource
{
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = BazaarTable::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Bazaar';

    protected static ?string $navigationLabel = 'Tables';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->label('Table number')
                        ->numeric()
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('section')
                        ->options(BazaarTable::SECTIONS)
                        ->required(),

                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->prefix(\App\Models\Setting::get('currency_symbol'))
                        ->helperText('What a vendor pays for this table for a whole weekend (Thursday + Friday).'),

                    Forms\Components\Toggle::make('is_bookable')
                        ->label('Vendors can book it')
                        ->default(true)
                        ->onColor('success')
                        ->helperText('Off for restaurant units — they still show on the plan.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('In use this season')
                        ->default(true)
                        ->onColor('success'),
                ]),

            Forms\Components\Section::make('Position on the floor plan')
                ->description('Coordinates for the map drawing. Normally set by the seeder — only touch these if the layout changes.')
                ->columns(4)
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('pos_x')->numeric()->required(),
                    Forms\Components\TextInput::make('pos_y')->numeric()->required(),
                    Forms\Components\TextInput::make('width')->numeric()->required(),
                    Forms\Components\TextInput::make('height')->numeric()->required(),
                    Forms\Components\TextInput::make('rotation')
                        ->numeric()
                        ->required()
                        ->helperText('Degrees.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('number')
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('#')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('section')
                    ->badge()
                    ->formatStateUsing(fn ($state) => BazaarTable::SECTIONS[$state] ?? $state)
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => money_format($state))
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_bookable')
                    ->label('Bookable')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('section')
                    ->options(BazaarTable::SECTIONS),

                Tables\Filters\TernaryFilter::make('is_bookable')->label('Bookable'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('set_price')
                        ->label('Set price')
                        ->icon('heroicon-o-currency-dollar')
                        ->form([
                            Forms\Components\TextInput::make('price')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->prefix(\App\Models\Setting::get('currency_symbol')),
                        ])
                        ->action(fn ($records, array $data) => $records->each->update([
                            'price' => $data['price'],
                        ]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBazaarTables::route('/'),
            'create' => Pages\CreateBazaarTable::route('/create'),
            'edit' => Pages\EditBazaarTable::route('/{record}/edit'),
        ];
    }
}
