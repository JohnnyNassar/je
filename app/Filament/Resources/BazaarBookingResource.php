<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BazaarBookingResource\Pages;
use App\Models\BazaarBooking;
use App\Models\BazaarBookingDocument;
use App\Models\BazaarPeriod;
use App\Models\BazaarTable;
use App\Models\BazaarVendorCategory;
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
                ->description('One booking covers a whole weekend — Thursday and Friday together.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('bazaar_period_id')
                        ->label('Weekend')
                        ->options(fn () => static::periodOptions())
                        ->required()
                        ->searchable()
                        ->live(),

                    // Only tables still free that weekend are offered, so the
                    // admin never walks into the double-booking constraint.
                    Forms\Components\Select::make('bazaar_table_id')
                        ->label('Table')
                        ->options(function (Forms\Get $get, ?BazaarBooking $record) {
                            $periodId = $get('bazaar_period_id');

                            if (! $periodId) {
                                return [];
                            }

                            $taken = BazaarBooking::query()
                                ->where('bazaar_period_id', $periodId)
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
                        ->helperText('Only tables still free that weekend are listed.'),

                    Forms\Components\TextInput::make('price')
                        ->label('Fee for the weekend')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->prefix(\App\Models\Setting::get('currency_symbol')),

                    Forms\Components\TextInput::make('deposit')
                        ->label('Deposit')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(fn () => (float) \App\Models\Setting::get('bazaar_deposit', '10'))
                        ->prefix(\App\Models\Setting::get('currency_symbol'))
                        ->helperText('Refunded after the weekend if nothing is damaged.'),

                    Forms\Components\Select::make('status')
                        ->options(BazaarBooking::STATUSES)
                        ->default(BazaarBooking::STATUS_PENDING)
                        ->required()
                        ->helperText('Cancelling frees the table for someone else.'),

                    Forms\Components\DateTimePicker::make('deposit_returned_at')
                        ->label('Deposit returned on')
                        ->seconds(false)
                        ->helperText('Leave empty while you are still holding it.'),
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

                    Forms\Components\Select::make('bazaar_vendor_category_id')
                        ->label('What they sell')
                        ->options(fn () => BazaarVendorCategory::active()->pluck('name_en', 'id')->all())
                        ->searchable()
                        ->helperText('Food, drink and personal care need a health certificate.'),

                    Forms\Components\TextInput::make('vendor_business')
                        ->label('Shop / brand name')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('goods_description')
                        ->label('Product details')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Internal notes')
                        ->rows(2)
                        ->columnSpanFull()
                        ->helperText('Only you and your staff see this.'),
                ]),

            Forms\Components\Section::make('Certificates')
                ->description('Uploaded by the vendor. Stored privately — never published on the site.')
                ->schema([
                    Forms\Components\Placeholder::make('documents')
                        ->hiddenLabel()
                        ->content(function (?BazaarBooking $record) {
                            if (! $record || $record->documents->isEmpty()) {
                                return new \Illuminate\Support\HtmlString(
                                    '<span class="text-sm text-gray-500">Nothing uploaded yet.</span>'
                                );
                            }

                            $rows = $record->documents->map(function (BazaarBookingDocument $d) {
                                $url = route('admin.bazaar.document', $d);

                                return '<li><a class="text-primary-600 underline" href="'.e($url).'" target="_blank" rel="noopener">'
                                    .e($d->kind_label).' — '.e($d->original_name).'</a> '
                                    .'<span class="text-gray-500">('.e($d->size_for_humans).')</span></li>';
                            })->implode('');

                            return new \Illuminate\Support\HtmlString('<ul class="list-disc ps-5 text-sm">'.$rows.'</ul>');
                        }),
                ])
                ->hidden(fn (?BazaarBooking $record) => $record === null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('period.starts_on')
                    ->label('Weekend')
                    ->getStateUsing(fn (BazaarBooking $record) => $record->period
                        ? $record->period->starts_on->format('j').'–'.$record->period->ends_on->format('j M')
                        : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('table.number')
                    ->label('Table')
                    ->formatStateUsing(fn ($state) => '#'.$state)
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vendor_name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name_en')
                    ->label('Sells')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                Tables\Columns\IconColumn::make('paperwork')
                    ->label('Docs')
                    ->getStateUsing(fn (BazaarBooking $record) => ! $record->isMissingHealthCertificate())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-exclamation-triangle')
                    ->falseColor('danger')
                    ->tooltip(fn (BazaarBooking $record) => $record->isMissingHealthCertificate()
                        ? 'Health certificate missing'
                        : 'Nothing outstanding'),

                Tables\Columns\TextColumn::make('vendor_phone')
                    ->label('Phone')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Phone copied'),

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
                    ->label('Fee')
                    ->formatStateUsing(fn ($state) => money_format($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('deposit')
                    ->label('Deposit')
                    ->formatStateUsing(fn ($state) => money_format($state))
                    ->description(fn (BazaarBooking $record) => $record->deposit_returned_at
                        ? 'returned '.$record->deposit_returned_at->format('j M')
                        : 'held')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Booked')
                    ->dateTime('j M H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bazaar_period_id')
                    ->label('Weekend')
                    ->options(fn () => static::periodOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->options(BazaarBooking::STATUSES),

                Tables\Filters\SelectFilter::make('bazaar_vendor_category_id')
                    ->label('Category')
                    ->options(fn () => BazaarVendorCategory::orderBy('position')->pluck('name_en', 'id')->all()),

                Tables\Filters\Filter::make('missing_health')
                    ->label('Missing health certificate')
                    ->query(fn (Builder $query) => $query
                        ->whereHas('category', fn (Builder $c) => $c->where('requires_health_certificate', true))
                        ->whereDoesntHave('documents', fn (Builder $d) => $d->where('kind', BazaarBookingDocument::KIND_HEALTH))),

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
                    ->modalDescription(fn (BazaarBooking $record) => $record->isMissingHealthCertificate()
                        ? 'This vendor sells food or personal care but has NOT uploaded a health certificate. They cannot trade without one.'
                        : 'The vendor keeps the table for both nights. Fee and deposit are collected on the night.')
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

                Tables\Actions\Action::make('return_deposit')
                    ->label('Deposit returned')
                    ->icon('heroicon-o-banknotes')
                    ->color('gray')
                    ->visible(fn (BazaarBooking $record) => $record->deposit > 0
                        && $record->deposit_returned_at === null
                        && $record->status === BazaarBooking::STATUS_CONFIRMED)
                    ->requiresConfirmation()
                    ->modalHeading('Mark the deposit as returned?')
                    ->action(fn (BazaarBooking $record) => $record->update([
                        'deposit_returned_at' => now(),
                    ])),

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

    /** Weekend dropdown options, shared by the form and the table filter. */
    private static function periodOptions(): array
    {
        return BazaarPeriod::query()
            ->orderBy('starts_on')
            ->get()
            ->mapWithKeys(fn (BazaarPeriod $p) => [
                $p->id => $p->starts_on->format('D j M').' – '.$p->ends_on->format('D j M Y'),
            ])
            ->all();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['period', 'table', 'category', 'documents']);
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
