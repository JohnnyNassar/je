<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BazaarPeriodResource\Pages;
use App\Models\BazaarBooking;
use App\Models\BazaarPeriod;
use App\Models\BazaarTable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BazaarPeriodResource extends Resource
{
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = BazaarPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Bazaar';

    protected static ?string $navigationLabel = 'Weekends';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->description('One weekend — Thursday and Friday sold together for a single fee.')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('starts_on')
                        ->label('First night (Thursday)')
                        ->required(),

                    Forms\Components\DatePicker::make('ends_on')
                        ->label('Last night (Friday)')
                        ->required()
                        ->after('starts_on'),

                    Forms\Components\TextInput::make('position')
                        ->numeric()
                        ->default(0)
                        ->helperText('Controls the order weekends are listed in.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Open for booking')
                        ->default(true)
                        ->onColor('success')
                        ->helperText('Turn off to hide a weekend from vendors without deleting its bookings.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_on')
            ->columns([
                Tables\Columns\TextColumn::make('starts_on')
                    ->label('Weekend')
                    ->getStateUsing(fn (BazaarPeriod $record) => $record->starts_on->format('D j M')
                        .' – '.$record->ends_on->format('D j M Y'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('nights_count')
                    ->label('Nights')
                    ->counts('nights')
                    ->badge()
                    ->color('gray')
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
                    ->getStateUsing(fn (BazaarPeriod $record) => static::bookableCount()
                        - ($record->bookings_count ?? $record->bookedCount()))
                    ->badge()
                    ->color(fn ($state) => $state === 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('takings')
                    ->label('Confirmed takings')
                    ->getStateUsing(fn (BazaarPeriod $record) => money_format(
                        $record->bookings()
                            ->where('status', BazaarBooking::STATUS_CONFIRMED)
                            ->sum('price')
                    ))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deposits')
                    ->label('Deposits held')
                    ->getStateUsing(fn (BazaarPeriod $record) => money_format(
                        $record->bookings()
                            ->where('status', BazaarBooking::STATUS_CONFIRMED)
                            ->whereNull('deposit_returned_at')
                            ->sum('deposit')
                    ))
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->url(fn (BazaarPeriod $record) => BazaarBookingResource::getUrl('index', [
                        'tableFilters' => ['bazaar_period_id' => ['value' => $record->id]],
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
            'index' => Pages\ListBazaarPeriods::route('/'),
            'create' => Pages\CreateBazaarPeriod::route('/create'),
            'edit' => Pages\EditBazaarPeriod::route('/{record}/edit'),
        ];
    }
}
