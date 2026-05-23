<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(60)
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('code', strtoupper(trim((string) $state))))
                            ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state)))
                            ->helperText('Customers type this at checkout. Stored upper-cased; matching is case-insensitive.'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->inline(false),
                        Forms\Components\Select::make('type')
                            ->options([
                                'percent' => 'Percentage (%)',
                                'fixed' => 'Fixed amount',
                            ])
                            ->default('percent')
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('value')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->suffix(fn (Forms\Get $get) => $get('type') === 'percent' ? '%' : \App\Models\Setting::get('currency_symbol')),
                        Forms\Components\TextInput::make('min_order_total')
                            ->label('Minimum order total')
                            ->numeric()
                            ->minValue(0)
                            ->prefix(\App\Models\Setting::get('currency_symbol'))
                            ->helperText('Leave empty for no minimum.'),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Usage limit')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->helperText('Total redemptions allowed. Leave empty for unlimited.'),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Starts at')
                            ->helperText('Leave empty to start immediately.'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires at')
                            ->helperText('Leave empty for no expiry.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Discount')
                    ->formatStateUsing(fn ($state, Coupon $record) => $record->type === 'percent'
                        ? rtrim(rtrim(number_format((float) $state, 2), '0'), '.') . '%'
                        : money_format($state)),
                Tables\Columns\TextColumn::make('min_order_total')
                    ->label('Min. order')
                    ->formatStateUsing(fn ($state) => $state ? money_format($state) : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Used')
                    ->formatStateUsing(fn ($state, Coupon $record) => $record->max_uses
                        ? "{$state} / {$record->max_uses}"
                        : (string) $state),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
