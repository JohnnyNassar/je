<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable(['name', 'phone', 'email'])
                            ->required()
                            ->helperText('Type to search by name, phone, or email.'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required(),
                        Forms\Components\TextInput::make('city'),
                        Forms\Components\Textarea::make('address')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Status')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\Select::make('payment_method')
                            ->options(['cod' => 'Cash on Delivery'])
                            ->required()
                            ->default('cod'),
                        Forms\Components\TextInput::make('total')
                            ->numeric()
                            ->prefix(\App\Models\Setting::get('currency_symbol'))
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('discount_total')
                            ->label('Discount')
                            ->numeric()
                            ->prefix(\App\Models\Setting::get('currency_symbol'))
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('coupon_code')
                            ->label('Coupon')
                            ->placeholder('—')
                            ->disabled()
                            ->dehydrated(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => money_format($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('coupon_code')
                    ->label('Coupon')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('points_redeemed')
                    ->label('Pts used')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('points_earned')
                    ->label('Pts earned')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            \App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Order::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
