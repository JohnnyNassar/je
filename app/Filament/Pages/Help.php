<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Help extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'Help';

    protected static ?string $title = 'Help & User Guide';

    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.help';
}
