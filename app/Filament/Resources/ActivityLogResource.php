<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\SuperAdminOnly;
use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ActivityLogResource extends Resource
{
    use SuperAdminOnly;

    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Activity log';

    protected static ?string $modelLabel = 'activity entry';

    protected static ?string $pluralModelLabel = 'activity log';

    protected static ?int $navigationSort = 100;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer')
                    ->label('Who')
                    ->state(fn (ActivityLog $record) => $record->causer
                        ? (($record->causer->name ?? null) ?: ($record->causer->email ?? ('#' . $record->causer_id)))
                        : '—')
                    ->description(fn (ActivityLog $record) => $record->causer_type ? class_basename($record->causer_type) : null),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'failed_login' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Item')
                    ->state(fn (ActivityLog $record): string => $record->subject_type
                        ? class_basename($record->subject_type) . ' #' . $record->subject_id
                        : '—'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Action')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('changes')
                    ->label('Changes')
                    ->wrap()
                    ->state(function (ActivityLog $record): string {
                        $p = $record->properties ?? [];
                        $new = $p['attributes'] ?? [];
                        $old = $p['old'] ?? [];
                        $keys = array_keys($new ?: $old);
                        if (empty($keys)) {
                            return '—';
                        }
                        $fmt = fn ($v) => is_scalar($v) || $v === null
                            ? Str::limit((string) $v, 40)
                            : Str::limit((string) json_encode($v), 40);
                        $lines = [];
                        foreach ($keys as $k) {
                            $lines[] = array_key_exists($k, $old)
                                ? $k . ': ' . $fmt($old[$k]) . ' → ' . $fmt($new[$k] ?? null)
                                : $k . ': ' . $fmt($new[$k] ?? null);
                        }

                        return implode('; ', $lines);
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'failed_login' => 'Failed login',
                    ]),
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Type')
                    ->options(fn (): array => ActivityLog::query()
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->filter()
                        ->all()),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
