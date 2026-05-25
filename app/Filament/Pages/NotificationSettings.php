<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NotificationSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use \App\Filament\Concerns\SuperAdminOnly;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $title = 'Notification channels';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.notification-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'notify_admin_new_order' => filter_var(Setting::get('notify_admin_new_order') ?? 'true', FILTER_VALIDATE_BOOLEAN),
            'admin_notify_email' => Setting::get('admin_notify_email'),
            'admin_notify_phone' => Setting::get('admin_notify_phone'),

            'mail_enabled' => filter_var(Setting::get('mail_enabled'), FILTER_VALIDATE_BOOLEAN),
            'mail_from_name' => Setting::get('mail_from_name'),
            'mail_from_address' => Setting::get('mail_from_address'),
            'mail_host' => Setting::get('mail_host'),
            'mail_port' => Setting::get('mail_port'),
            'mail_username' => Setting::get('mail_username'),
            'mail_password' => Setting::get('mail_password'),
            'mail_encryption' => Setting::get('mail_encryption') ?: 'tls',

            'sms_enabled' => filter_var(Setting::get('sms_enabled'), FILTER_VALIDATE_BOOLEAN),
            'sms_provider' => Setting::get('sms_provider'),
            'sms_key' => Setting::get('sms_key'),
            'sms_secret' => Setting::get('sms_secret'),
            'sms_from' => Setting::get('sms_from'),

            'whatsapp_enabled' => filter_var(Setting::get('whatsapp_enabled'), FILTER_VALIDATE_BOOLEAN),
            'whatsapp_phone_id' => Setting::get('whatsapp_phone_id'),
            'whatsapp_token' => Setting::get('whatsapp_token'),
            'whatsapp_business_phone' => Setting::get('whatsapp_business_phone'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('In-app (Dashboard)')
                    ->description('Shows a notification bell in this admin panel. Works immediately — no setup needed.')
                    ->icon('heroicon-o-computer-desktop')
                    ->columns(2)
                    ->schema([
                        Toggle::make('notify_admin_new_order')
                            ->label('Alert admins when a new order is placed')
                            ->onColor('success')
                            ->columnSpanFull(),
                        TextInput::make('admin_notify_email')
                            ->label('Admin email for alerts')
                            ->email()
                            ->helperText('Used once the Email channel is connected.'),
                        TextInput::make('admin_notify_phone')
                            ->label('Admin phone for alerts')
                            ->tel()
                            ->helperText('Used once SMS / WhatsApp is connected (with country code).'),
                    ]),

                Section::make('Email')
                    ->description('SMTP details from your email provider (Resend, Brevo, Gmail, ...). Powers password-reset and order emails.')
                    ->icon('heroicon-o-envelope')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('mail_enabled')->label('Enable email')->onColor('success')->columnSpanFull(),
                        TextInput::make('mail_from_name')->label('From name'),
                        TextInput::make('mail_from_address')->label('From address')->email(),
                        TextInput::make('mail_host')->label('SMTP host')->placeholder('smtp.resend.com'),
                        TextInput::make('mail_port')->label('Port')->numeric()->placeholder('587'),
                        TextInput::make('mail_username')->label('Username'),
                        TextInput::make('mail_password')->label('Password / API key')->password()->revealable(),
                        Select::make('mail_encryption')->label('Encryption')->options(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None']),
                    ]),

                Section::make('SMS')
                    ->description('Connect an SMS gateway (e.g. Twilio) to text customers and admins.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('sms_enabled')->label('Enable SMS')->onColor('success')->columnSpanFull(),
                        TextInput::make('sms_provider')->label('Provider')->placeholder('Twilio'),
                        TextInput::make('sms_from')->label('Sender ID / from number'),
                        TextInput::make('sms_key')->label('API key / Account SID'),
                        TextInput::make('sms_secret')->label('API secret / Auth token')->password()->revealable(),
                    ]),

                Section::make('WhatsApp')
                    ->description('WhatsApp Business Cloud API (Meta). Requires a verified Meta business, a dedicated API number, and approved message templates.')
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('whatsapp_enabled')->label('Enable WhatsApp')->onColor('success')->columnSpanFull(),
                        TextInput::make('whatsapp_business_phone')->label('Business phone (display)')->tel(),
                        TextInput::make('whatsapp_phone_id')->label('Phone number ID'),
                        TextInput::make('whatsapp_token')->label('Access token')->password()->revealable()->columnSpanFull(),
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

        Notification::make()->title('Notification settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label('Send test email')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Sends a test message using your saved email settings. Save first if you just changed them.')
                ->action(function () {
                    $to = Setting::get('admin_notify_email') ?: Setting::get('mail_from_address');

                    if (! filter_var(Setting::get('mail_enabled'), FILTER_VALIDATE_BOOLEAN)) {
                        Notification::make()->title('Enable email and Save before testing.')->warning()->send();

                        return;
                    }

                    if (! $to) {
                        Notification::make()->title('Set an admin email (or a from-address) and Save first.')->warning()->send();

                        return;
                    }

                    try {
                        \Illuminate\Support\Facades\Mail::raw(
                            'This is a test email from your Joreption store. If you can read this, your email settings are working.',
                            fn ($message) => $message->to($to)->subject('Joreption — test email'),
                        );

                        Notification::make()->title("Test email sent to {$to}")->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Could not send the test email')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
