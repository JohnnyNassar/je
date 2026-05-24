<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\LoyaltyTransactionResource\Pages;
use App\Models\LoyaltyTransaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoyaltyTransactionResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = LoyaltyTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?string $navigationLabel = 'Points activity';

    protected static ?string $modelLabel = 'points entry';

    protected static ?string $pluralModelLabel = 'points activity';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'earn' => 'success',
                        'redeem' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('points')
                    ->badge()
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (int $state): string => ($state >= 0 ? '+' : '') . $state),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order')
                    ->formatStateUsing(fn ($state) => $state ? ('#' . $state) : '—'),
                Tables\Columns\TextColumn::make('description')
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'earn' => 'Earn',
                        'redeem' => 'Redeem',
                        'adjust' => 'Adjust',
                    ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyTransactions::route('/'),
        ];
    }
}
