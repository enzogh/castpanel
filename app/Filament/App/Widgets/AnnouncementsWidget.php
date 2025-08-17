<?php

namespace App\Filament\App\Widgets;

use App\Models\Announcement;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class AnnouncementsWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.announcements-widget';

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $user = auth()->user();
        
        $announcements = Announcement::visible()
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->filter(fn (Announcement $announcement) => $announcement->isVisibleForUser($user));

        return [
            'announcements' => $announcements,
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        
        return Announcement::visible()
            ->get()
            ->filter(fn (Announcement $announcement) => $announcement->isVisibleForUser($user))
            ->isNotEmpty();
    }
}