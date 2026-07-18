<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BazaarNightResource\Pages;
use App\Models\BazaarBooking;
use App\Models\BazaarNight;
use App\Models\BazaarTable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BazaarNightResource extends Resource
{
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = BazaarNight::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Bazaar';

    protected static ?string $navigationLabel = 'Nights';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('event_date')
                        ->label('Date')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('One row per trading night.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Open for booking')
                        ->default(true)
                        ->onColor('success')
                        ->helperText('Turn off to hide a night from vendors without deleting its bookings.'),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Doors open')
                        ->seconds(false)
                        ->required(),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Closes')
                        ->seconds(false)
                        ->required()
                        ->helperText('Runs past midnight, so this normally lands on the next day.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('event_date')
            ->columns([
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Date')
                    ->date('D j M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Hours')
                    ->getStateUsing(fn (BazaarNight $record) => $record->starts_at->format('g:i A')
                        .' – '.$record->ends_at->format('g:i A'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Booked')
                    ->counts([
                        'bookings' => fn (Builder $q) => $q->whereNot('status', BazaarBooking::STATUS_CANCELLED),
                    ])
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('available')
                    ->label('Free')
                    // Falls back to counting directly if the withCount column
                    // isn't loaded (e.g. the Booked column toggled off).
                    ->getStateUsing(fn (BazaarNight $record) => static::bookableCount()
                        - ($record->bookings_count ?? $record->bookedCount()))
                    ->badge()
                    ->color(fn ($state) => $state === 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('takings')
                    ->label('Confirmed takings')
                    ->getStateUsing(fn (BazaarNight $record) => money_format(
                        $record->bookings()
                            ->where('status', BazaarBooking::STATUS_CONFIRMED)
                            ->sum('price')
                    ))
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Open')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Open for booking'),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->query(fn (Builder $query) => $query->upcoming())
                    ->default(),
            ])
            ->actions([
                Tables\Actions\Action::make('bookings')
                    ->label('Bookings')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('gray')
                    ->url(fn (BazaarNight $record) => BazaarBookingResource::getUrl('index', [
                        'tableFilters' => ['bazaar_night_id' => ['value' => $record->id]],
                    ])),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Cached per request — the same number is needed on every row. */
    private static function bookableCount(): int
    {
        static $count = null;

        return $count ??= BazaarTable::bookable()->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBazaarNights::route('/'),
            'create' => Pages\CreateBazaarNight::route('/create'),
            'edit' => Pages\EditBazaarNight::route('/{record}/edit'),
        ];
    }
}
