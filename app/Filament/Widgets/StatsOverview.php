<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PendingUserResource;
use App\Filament\Resources\SurveyReviewResource;
use App\Models\Survey;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(
                'Pending Student IDs',
                User::where('status', 'pending')->where('role', 'user')->count(),
            )
                ->description('Awaiting face-to-face verification')
                ->icon('heroicon-o-identification')
                ->color('warning')
                ->url(PendingUserResource::getUrl()),
            Stat::make(
                'Pending Surveys',
                Survey::where('status', 'pending_review')->count(),
            )
                ->description('Awaiting moderation approval')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info')
                ->url(SurveyReviewResource::getUrl()),
            Stat::make(
                'Verified Students',
                User::where('role', 'user')->whereIn('status', ['active', 'restricted'])->count(),
            )
                ->description('Active PUP Calauan accounts')
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make(
                'Total Points Redeemed',
                abs((int) WalletTransaction::where('type', 'prof_redemption')->sum('amount')),
            )
                ->description('Across all active professors')
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),
        ];
    }
}
