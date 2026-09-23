<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\SuperAdminOnly;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    use SuperAdminOnly;

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'staff member';

    protected static ?string $pluralModelLabel = 'staff';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('role')
                            ->options([
                                'super_admin' => 'Super Admin (owner — full access)',
                                'admin' => 'Administrator (everyday ops)',
                                'staff' => 'Staff (catalog only)',
                            ])
                            ->default('staff')
                            ->required()
                            ->helperText('Super Admin can manage staff, settings and the activity log, and always sees orders and cost. Administrator runs the back office — customers, coupons, loyalty and the bazaar — and by default orders too, though the two switches below can take orders or cost away without changing the role. Staff manage the catalog only and never see orders.'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->helperText('Leave blank to keep the current password.'),
                        Forms\Components\Toggle::make('can_view_cost')
                            ->label('Can view cost prices & profit')
                            ->helperText('Cost price and profit margin, on the product form, the products list and Quick Add. Applies to Administrators too — switch it off to give someone the back office without showing them what stock cost. The owner always sees them.')
                            ->default(false)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('can_view_orders')
                            ->label('Can view orders & revenue')
                            ->helperText('The Orders screen, the dashboard order and revenue figures, and a customer’s order count, total spent and order history. Switch it off for someone who manages the catalogue but should not see takings. The owner always sees them; Staff never do.')
                            ->default(true)
                            ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                // Two administrators with different access must not read as the
                // same thing. The badge carries the role plus whether anything
                // has been taken away, so the list is honest at a glance.
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record) => match ($state) {
                        'super_admin' => 'Super Admin',
                        'admin' => $record->isRestricted() ? 'Administrator · limited' : 'Administrator',
                        default => 'Staff',
                    })
                    ->color(fn (?string $state, User $record) => match ($state) {
                        'super_admin' => 'success',
                        'admin' => $record->isRestricted() ? 'warning' : 'info',
                        default => 'gray',
                    })
                    ->tooltip(fn (User $record) => $record->isRestricted()
                        ? 'Cannot see: ' . implode(', ', $record->restrictions())
                        : null),
                Tables\Columns\IconColumn::make('can_view_orders')
                    ->label('Orders')
                    ->boolean()
                    ->tooltip(fn ($record) => $record->canViewOrders()
                        ? 'Sees orders, revenue and customer spend'
                        : 'Cannot see orders, revenue or customer spend')
                    // The owner is exempt from the flag, so show what is true
                    // rather than what the column happens to store.
                    ->state(fn ($record) => $record->canViewOrders()),
                Tables\Columns\IconColumn::make('can_view_cost')
                    ->label('Cost')
                    ->boolean()
                    ->tooltip(fn ($record) => $record->canViewCost()
                        ? 'Sees cost prices and profit'
                        : 'Cannot see cost prices or profit')
                    ->state(fn ($record) => $record->canViewCost()),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    // Don't let an admin delete their own account.
                    ->visible(fn (User $record) => $record->getKey() !== auth()->id()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
