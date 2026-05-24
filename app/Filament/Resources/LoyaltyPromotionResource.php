<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\LoyaltyPromotionResource\Pages;
use App\Models\LoyaltyPromotion;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoyaltyPromotionResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = LoyaltyPromotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?string $navigationLabel = 'Promotions';

    protected static ?string $modelLabel = 'promotion';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Double points weekend')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('type')
                        ->required()
                        ->default('multiplier')
                        ->live()
                        ->options([
                            'multiplier' => 'Multiply points',
                            'bonus' => 'Bonus points',
                        ]),
                    Forms\Components\TextInput::make('multiplier')
                        ->label('Multiplier (×)')
                        ->numeric()
                        ->minValue(1)
                        ->step('0.1')
                        ->default(2)
                        ->helperText('2 = double points, 3 = triple.')
                        ->visible(fn (Forms\Get $get) => $get('type') === 'multiplier')
                        ->required(fn (Forms\Get $get) => $get('type') === 'multiplier'),
                    Forms\Components\TextInput::make('bonus_points')
                        ->label('Bonus points per order')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Added on top of points normally earned.')
                        ->visible(fn (Forms\Get $get) => $get('type') === 'bonus')
                        ->required(fn (Forms\Get $get) => $get('type') === 'bonus'),
                    Forms\Components\TextInput::make('min_order_total')
                        ->label('Minimum order total')
                        ->numeric()
                        ->minValue(0)
                        ->prefix(Setting::get('currency_symbol') ?: null)
                        ->helperText('Leave blank to apply to any order.'),
                    Forms\Components\Toggle::make('active')
                        ->default(true)
                        ->onColor('success'),
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Starts')
                        ->helperText('Leave blank to start immediately.'),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Ends')
                        ->after('starts_at')
                        ->helperText('Leave blank for no end date.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'multiplier' ? 'Multiply' : 'Bonus')
                    ->color('info'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Reward')
                    ->state(fn (LoyaltyPromotion $record): string => $record->type === 'multiplier'
                        ? '×' . rtrim(rtrim(number_format((float) $record->multiplier, 2), '0'), '.')
                        : '+' . (int) $record->bonus_points . ' pts'),
                Tables\Columns\TextColumn::make('window')
                    ->label('When')
                    ->state(function (LoyaltyPromotion $record): string {
                        $fmt = fn ($d) => $d ? $d->format('d M Y') : null;
                        $s = $fmt($record->starts_at);
                        $e = $fmt($record->ends_at);
                        if (! $s && ! $e) {
                            return 'Always';
                        }

                        return ($s ?: '…') . ' → ' . ($e ?: '…');
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->state(function (LoyaltyPromotion $record): string {
                        if (! $record->active) {
                            return 'Off';
                        }
                        if ($record->starts_at && $record->starts_at->isFuture()) {
                            return 'Scheduled';
                        }
                        if ($record->ends_at && $record->ends_at->isPast()) {
                            return 'Ended';
                        }

                        return 'Running';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Running' => 'success',
                        'Scheduled' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\ToggleColumn::make('active')
                    ->label('On'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('No promotions yet')
            ->emptyStateDescription('Create a promotion to give customers extra points for a period.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyPromotions::route('/'),
            'create' => Pages\CreateLoyaltyPromotion::route('/create'),
            'edit' => Pages\EditLoyaltyPromotion::route('/{record}/edit'),
        ];
    }
}
