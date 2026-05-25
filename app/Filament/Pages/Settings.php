<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Setting;
use App\Support\ImageResizer;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;
    use \App\Concerns\HandlesMediaPicking;
    use \App\Filament\Concerns\AdminOnly;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'currency_code' => Setting::get('currency_code'),
            'currency_symbol' => Setting::get('currency_symbol'),
            'currency_position' => Setting::get('currency_position'),
            'admin_whatsapp' => Setting::get('admin_whatsapp'),
            'coming_soon_enabled' => filter_var(Setting::get('coming_soon_enabled'), FILTER_VALIDATE_BOOLEAN),
            'coming_soon_message_en' => Setting::get('coming_soon_message_en'),
            'coming_soon_message_ar' => Setting::get('coming_soon_message_ar'),
            'hero_image_path' => Setting::get('hero_image_path'),
            'hero_product_id' => Setting::get('hero_product_id'),
            'google_analytics_id' => Setting::get('google_analytics_id'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Coming Soon mode')
                    ->description('When on, public visitors see a Coming Soon page. Admins (logged in) still see the real site.')
                    ->schema([
                        Toggle::make('coming_soon_enabled')
                            ->label('Show Coming Soon page to public visitors')
                            ->onColor('warning'),
                        TextInput::make('coming_soon_message_en')
                            ->label('Headline (English)')
                            ->maxLength(120),
                        TextInput::make('coming_soon_message_ar')
                            ->label('Headline (Arabic)')
                            ->maxLength(120),
                    ]),
                Section::make('Landing page hero')
                    ->description('The background of the catalog hero banner. Either upload a custom image OR pick one of your products to feature its image. Custom upload wins if both are set.')
                    ->schema([
                        Placeholder::make('current_hero_preview')
                            ->label('Current hero image')
                            ->content(function () {
                                $val = $this->data['hero_image_path'] ?? '';

                                // FileUpload can hold its state as an array (during upload, or in some lifecycle phases).
                                // Coerce to a clean string path; ignore non-string entries.
                                if (is_array($val)) {
                                    $first = collect($val)->filter(fn ($v) => is_string($v))->first();
                                    $val = is_string($first) ? $first : '';
                                }
                                $path = is_string($val) ? trim($val) : '';

                                if ($path === '') {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div style="padding:1rem;border:1px dashed #d1d5db;border-radius:8px;color:#6b7280;text-align:center;">No hero image set — using gradient fallback.</div>'
                                    );
                                }

                                $url = asset('storage/' . $path);
                                return new \Illuminate\Support\HtmlString(
                                    '<div style="position:relative;display:inline-block;">'
                                    . '<img src="' . e($url) . '" alt="" style="height:120px;border-radius:8px;border:1px solid #e5e7eb;object-fit:cover;">'
                                    . '<div style="font-size:11px;color:#6b7280;margin-top:6px;font-family:ui-monospace,monospace;">' . e($path) . '</div>'
                                    . '</div>'
                                );
                            }),
                        FormActions::make([
                            FormAction::make('chooseHeroFromLibrary')
                                ->label('Pick hero image from media library')
                                ->icon('heroicon-o-photo')
                                ->color('primary')
                                ->modalHeading('Pick the hero image')
                                ->modalDescription('Click any image to use it as the hero banner. Filter by filename above.')
                                ->modalContent(fn () => view('filament.components.media-picker', [
                                    'statePath' => 'data.hero_image_path',
                                    'dirs' => ['hero', 'products'],
                                ]))
                                ->modalSubmitAction(false)
                                ->modalCancelActionLabel('Close')
                                ->modalWidth('5xl'),
                            FormAction::make('clearHeroImage')
                                ->label('Clear hero')
                                ->icon('heroicon-o-x-mark')
                                ->color('gray')
                                ->visible(function () {
                                    $v = $this->data['hero_image_path'] ?? '';
                                    return is_string($v) ? $v !== '' : ! empty($v);
                                })
                                ->action(function () {
                                    $this->data['hero_image_path'] = '';
                                    Setting::set('hero_image_path', '');
                                    \Filament\Notifications\Notification::make()
                                        ->title('Hero image cleared')->success()->send();
                                }),
                        ])->columnSpanFull(),
                        FileUpload::make('hero_image_path')
                            ->label('Or upload a new custom hero image')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '21:9',
                                '16:5',
                                '3:1',
                                '16:9',
                                null,
                            ])
                            ->imageEditorViewportWidth('1920')
                            ->imageEditorViewportHeight('820')
                            ->directory('hero')
                            ->disk('public')
                            ->imagePreviewHeight('200')
                            ->maxSize(8192)
                            ->helperText('After picking the file, click "Edit image" to crop to 21:9. Auto-resized to 1920px.'),
                        Select::make('hero_product_id')
                            ->label('Option B — feature a product image')
                            ->searchable()
                            ->live(onBlur: true)
                            ->getSearchResultsUsing(function (string $search) {
                                $like = '%' . $search . '%';
                                return Product::active()
                                    ->whereNotNull('image_path')
                                    ->where(function ($q) use ($like) {
                                        $q->where('name_en', 'like', $like)
                                          ->orWhere('name_ar', 'like', $like);
                                    })
                                    ->orderBy('is_featured', 'desc')
                                    ->orderByDesc('created_at')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [$p->id => '#' . $p->id . ' · ' . ($p->name_ar ?: $p->name_en)])
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $p = Product::find($value);
                                return $p ? ('#' . $p->id . ' · ' . ($p->name_ar ?: $p->name_en)) : null;
                            })
                            ->placeholder('Start typing a product name (EN or AR)...')
                            ->helperText('Server-searched, returns up to 50 matches. Used only when no custom image is uploaded above.'),
                        Placeholder::make('hero_product_preview')
                            ->label('Preview')
                            ->content(function ($get) {
                                $id = (int) $get('hero_product_id');
                                if ($id <= 0) return '—';
                                $p = Product::find($id);
                                if (! $p || ! $p->image_path) return '—';
                                $url = asset('storage/' . $p->image_path);
                                return new \Illuminate\Support\HtmlString(
                                    '<img src="' . e($url) . '" alt="" style="height:160px;border-radius:8px;border:1px solid #e5e7eb;">'
                                );
                            }),
                    ]),
                Section::make('Currency')
                    ->columns(3)
                    ->schema([
                        TextInput::make('currency_code')
                            ->label('Currency code')
                            ->placeholder('USD, JOD, AED, EUR, ...')
                            ->maxLength(8)
                            ->required(),
                        TextInput::make('currency_symbol')
                            ->label('Symbol')
                            ->placeholder('$, JD, د.إ, €')
                            ->maxLength(8)
                            ->required(),
                        Select::make('currency_position')
                            ->label('Symbol position')
                            ->options([
                                'before' => 'Before amount ($100.00)',
                                'after' => 'After amount (100.00 JD)',
                            ])
                            ->required(),
                    ]),
                Section::make('WhatsApp')
                    ->schema([
                        TextInput::make('admin_whatsapp')
                            ->label('Admin WhatsApp number (with country code, no +)')
                            ->placeholder('962790000000')
                            ->helperText('Used on the order confirmation page and Coming Soon page.')
                            ->maxLength(20),
                    ]),
                Section::make('Analytics')
                    ->description('Track visitor traffic with Google Analytics (GA4). Create a free property at analytics.google.com and paste its Measurement ID below.')
                    ->schema([
                        TextInput::make('google_analytics_id')
                            ->label('Google Analytics Measurement ID')
                            ->placeholder('G-XXXXXXXXXX')
                            ->helperText('GA4 → Admin → Data Streams → your web stream. Leave blank to turn tracking off.')
                            ->maxLength(20),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            Setting::set($key, $value);
        }

        // Resize hero image after upload (wider crop than products)
        if (! empty($data['hero_image_path'])) {
            $abs = storage_path('app/public/' . ltrim($data['hero_image_path'], '/'));
            ImageResizer::fit($abs, 1920, 85);
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
