<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class LoyaltySettings extends Page implements HasForms
{
    use InteractsWithForms;
    use \App\Filament\Concerns\SuperAdminOnly;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Loyalty settings';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.loyalty-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'loyalty_enabled' => filter_var(Setting::get('loyalty_enabled'), FILTER_VALIDATE_BOOLEAN),
            'loyalty_earn_rate' => Setting::get('loyalty_earn_rate'),
            'loyalty_redeem_value' => Setting::get('loyalty_redeem_value'),
            'loyalty_min_redeem' => Setting::get('loyalty_min_redeem'),
            'tier_vip_points_multiplier' => Setting::get('tier_vip_points_multiplier'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Points')
                    ->description('Reward customers with points they can redeem for a discount at checkout. Points are credited when an order is marked delivered.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('loyalty_enabled')
                            ->label('Enable loyalty points')
                            ->onColor('success')
                            ->columnSpanFull(),
                        TextInput::make('loyalty_earn_rate')
                            ->label('Points earned per 1 ' . (Setting::get('currency_code') ?: 'unit') . ' spent')
                            ->numeric()
                            ->minValue(0)
                            ->default(1)
                            ->helperText('e.g. 1 = one point per ' . (Setting::get('currency_code') ?: 'unit') . '.'),
                        TextInput::make('loyalty_redeem_value')
                            ->label('Value of 1 point (' . (Setting::get('currency_symbol') ?: '') . ')')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.001')
                            ->default(0.01)
                            ->helperText('e.g. 0.01 means 100 points = 1.00.'),
                        TextInput::make('loyalty_min_redeem')
                            ->label('Minimum points before redeeming')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ]),
                Section::make('VIP tier')
                    ->description('VIP customers earn loyalty points faster. The multiplier is applied to the base points before any active promotion.')
                    ->schema([
                        TextInput::make('tier_vip_points_multiplier')
                            ->label('VIP points multiplier')
                            ->numeric()
                            ->minValue(1)
                            ->step('0.1')
                            ->default(2)
                            ->suffix('×')
                            ->helperText('e.g. 2 means VIP customers earn double points. 1 disables the boost.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            Setting::set($key, $value);
        }

        Notification::make()->title('Loyalty settings saved')->success()->send();
    }
}
