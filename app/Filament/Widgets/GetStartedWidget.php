<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class GetStartedWidget extends Widget
{
    protected static string $view = 'filament.widgets.get-started';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;
}
