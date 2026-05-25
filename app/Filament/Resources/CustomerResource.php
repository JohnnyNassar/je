<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('city')
                    ->maxLength(255),
                Forms\Components\Select::make('tier')
                    ->options(Customer::TIERS)
                    ->default('regular')
                    ->required()
                    ->native(false),
                Forms\Components\Textarea::make('address')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tier')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => Customer::TIERS[$state] ?? 'Regular')
                    ->color(fn (?string $state) => match ($state) {
                        'vip' => 'warning',
                        'wholesale' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('orders_sum_total')
                    ->label('Total spent')
                    ->sum('orders', 'total')
                    ->formatStateUsing(fn ($state) => $state ? money_format($state) : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('points_balance')
                    ->label('Points')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->options(Customer::TIERS),
                Tables\Filters\Filter::make('has_orders')
                    ->label('Has orders')
                    ->query(fn ($query) => $query->whereHas('orders'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjustPoints')
                    ->label('Adjust points')
                    ->icon('heroicon-o-star')
                    ->color('gray')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label('Points to add (negative subtracts)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('reason')
                            ->label('Reason (optional)')
                            ->maxLength(255),
                    ])
                    ->action(function (Customer $record, array $data) {
                        $points = (int) $data['points'];
                        if ($points === 0) {
                            return;
                        }
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $points, $data) {
                            $record->increment('points_balance', $points);
                            \App\Models\LoyaltyTransaction::create([
                                'customer_id' => $record->id,
                                'points' => $points,
                                'type' => 'adjust',
                                'description' => $data['reason'] ?: 'Manual adjustment',
                            ]);
                        });
                        \Filament\Notifications\Notification::make()
                            ->title('Points adjusted')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
