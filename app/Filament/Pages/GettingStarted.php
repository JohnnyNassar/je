<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class GettingStarted extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationLabel = 'Getting Started';

    protected static ?string $title = 'Getting Started · دليل البداية';

    // Sit right under the Dashboard (which is -2) so it's the first thing a new
    // team member sees in the sidebar.
    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.getting-started';
}
