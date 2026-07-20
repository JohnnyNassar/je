<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BazaarNightResource\Pages;
use App\Models\BazaarNight;
use App\Models\BazaarPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trading nights. Vendors book a whole weekend, so this screen is about the
 * hours the gates are open — not about availability, which lives on Weekends.
 */
class BazaarNightResource extends Resource
{
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = BazaarNight::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Bazaar';

    protected static ?string $navigationLabel = 'Nights & hours';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->description('Both nights open at 6:00 PM and run to midnight.')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('bazaar_period_id')
                        ->label('Belongs to weekend')
                        ->options(fn () => BazaarPeriod::orderBy('starts_on')->get()
                            ->mapWithKeys(fn (BazaarPeriod $p) => [
                                $p->id => $p->starts_on->format('D j M').' – '.$p->ends_on->format('D j M Y'),
                            ])->all())
                        ->searchable()
                        ->helperText('Which bookable weekend this night is part of.'),

                    Forms\Components\DatePicker::make('event_date')
                        ->label('Date')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Doors open')
                        ->seconds(false)
                        ->required(),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Closes')
                        ->seconds(false)
                        ->required()
                        ->helperText('Runs past midnight, so this normally lands on the next day.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Trading this night')
                        ->default(true)
                        ->onColor('success'),
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

                Tables\Columns\TextColumn::make('period.starts_on')
                    ->label('Weekend')
                    ->getStateUsing(fn (BazaarNight $record) => $record->period
                        ? $record->period->starts_on->format('j').'–'.$record->period->ends_on->format('j M')
                        : '—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Hours')
                    ->getStateUsing(fn (BazaarNight $record) => $record->starts_at->format('g:i A')
                        .' – '.$record->ends_at->format('g:i A')),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Trading')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Trading'),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming only')
                    ->query(fn (Builder $query) => $query->upcoming())
                    ->default(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('period');
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
