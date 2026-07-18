<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BazaarBookingResource\Pages;
use App\Models\BazaarBooking;
use App\Models\BazaarNight;
use App\Models\BazaarTable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BazaarBookingResource extends Resource
{
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = BazaarBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Bazaar';

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?int $navigationSort = 1;

    /** Pending bookings are the admin's to-do list, so surface the count. */
    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', BazaarBooking::STATUS_PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Booking')
                ->description('Which table, on which night.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('bazaar_night_id')
                        ->label('Night')
                        ->options(fn () => static::nightOptions())
                        ->required()
                        ->searchable()
                        ->live()
                        ->helperText('Each booking covers one night. A vendor coming twice needs two bookings.'),

                    // Only tables still free on the chosen night are offered, so
                    // the admin never walks into the double-booking constraint.
                    Forms\Components\Select::make('bazaar_table_id')
                        ->label('Table')
                        ->options(function (Forms\Get $get, ?BazaarBooking $record) {
                            $nightId = $get('bazaar_night_id');

                            if (! $nightId) {
                                return [];
                            }

                            $taken = BazaarBooking::query()
                                ->where('bazaar_night_id', $nightId)
                                ->whereNot('status', BazaarBooking::STATUS_CANCELLED)
                                ->when($record, fn (Builder $q) => $q->whereKeyNot($record->getKey()))
                                ->pluck('bazaar_table_id');

                            return BazaarTable::bookable()
                                ->whereNotIn('id', $taken)
                                ->orderBy('number')
                                ->get()
                                ->mapWithKeys(fn (BazaarTable $t) => [
                                    $t->id => "#{$t->number} — {$t->section_label}",
                                ])
                                ->all();
                        })
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($table = BazaarTable::find($state)) {
                                $set('price', $table->price);
                            }
                        })
                        ->helperText('Only tables still free on that night are listed.'),

                    Forms\Components\TextInput::make('price')
                        ->label('Price')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->prefix(\App\Models\Setting::get('currency_symbol'))
                        ->helperText('Copied from the table, but you can override it for this booking.'),

                    Forms\Components\Select::make('status')
                        ->options(BazaarBooking::STATUSES)
                        ->default(BazaarBooking::STATUS_PENDING)
                        ->required()
                        ->helperText('Cancelling frees the table for someone else.'),
                ]),

            Forms\Components\Section::make('Vendor')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('vendor_name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('vendor_phone')
                        ->label('Phone')
                        ->tel()
                        ->required()
                        ->maxLength(32),

                    Forms\Components\TextInput::make('vendor_business')
                        ->label('Shop / brand name')
                        ->maxLength(255)
                        ->helperText('Optional — shown in the vendor list if you publish one.'),

                    Forms\Components\Textarea::make('goods_description')
                        ->label('What they sell')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Internal notes')
                        ->rows(2)
                        ->columnSpanFull()
                        ->helperText('Only you and your staff see this.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('night.event_date')
                    ->label('Night')
                    ->date('D j M')
                    ->sortable(),

                Tables\Columns\TextColumn::make('table.number')
                    ->label('Table')
                    ->formatStateUsing(fn ($state) => '#'.$state)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('table.section')
                    ->label('Section')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vendor_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendor_phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Phone copied'),

                Tables\Columns\TextColumn::make('vendor_business')
                    ->label('Shop')
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => BazaarBooking::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        BazaarBooking::STATUS_CONFIRMED => 'success',
                        BazaarBooking::STATUS_CANCELLED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => money_format($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Booked')
                    ->dateTime('j M H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bazaar_night_id')
                    ->label('Night')
                    ->options(fn () => static::nightOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->options(BazaarBooking::STATUSES),

                Tables\Filters\SelectFilter::make('section')
                    ->label('Section')
                    ->options(BazaarTable::SECTIONS)
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, $section) => $q->whereHas(
                            'table',
                            fn (Builder $t) => $t->where('section', $section)
                        )
                    )),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (BazaarBooking $record) => $record->status === BazaarBooking::STATUS_PENDING)
                    ->requiresConfirmation()
                    ->modalHeading('Confirm this booking?')
                    ->modalDescription('The vendor keeps the table. Payment is still collected on the night.')
                    ->action(fn (BazaarBooking $record) => $record->update([
                        'status' => BazaarBooking::STATUS_CONFIRMED,
                    ])),

                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->url(fn (BazaarBooking $record) => $record->whatsapp_url)
                    ->openUrlInNewTab()
                    ->visible(fn (BazaarBooking $record) => filled($record->whatsapp_url)),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (BazaarBooking $record) => ! $record->isCancelled())
                    ->requiresConfirmation()
                    ->modalHeading('Cancel this booking?')
                    ->modalDescription('The table is released and someone else can book it.')
                    ->action(fn (BazaarBooking $record) => $record->update([
                        'status' => BazaarBooking::STATUS_CANCELLED,
                    ])),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** Night dropdown options, shared by the form and the table filter. */
    private static function nightOptions(): array
    {
        return BazaarNight::query()
            ->orderBy('event_date')
            ->get()
            ->mapWithKeys(fn (BazaarNight $n) => [
                $n->id => $n->event_date->format('D j M Y'),
            ])
            ->all();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['night', 'table']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBazaarBookings::route('/'),
            'create' => Pages\CreateBazaarBooking::route('/create'),
            'edit' => Pages\EditBazaarBooking::route('/{record}/edit'),
        ];
    }
}
