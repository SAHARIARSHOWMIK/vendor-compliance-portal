<?php

namespace App\Providers;

use App\Models\Notification;
use App\Models\VendorDocument;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('components.layout', function ($view): void {
            $pendingReviewCount = 0;
            $unreadNotificationCount = 0;
            $expiringDocumentCount = 0;
            $recentNotifications = collect();

            if (auth()->check()) {
                $user = auth()->user();

                if ($user->isInternal() && Schema::hasTable('vendor_documents')) {
                    $pendingReviewCount = VendorDocument::query()
                        ->whereIn('status', ['uploaded', 'reuploaded', 'under_review'])
                        ->count();

                    $expiringDocumentCount = VendorDocument::query()
                        ->whereNotNull('expiry_date')
                        ->whereBetween('expiry_date', [now()->startOfDay(), now()->addDays(60)->endOfDay()])
                        ->whereIn('status', ['approved', 'expiring_soon'])
                        ->count();
                }

                if (Schema::hasTable('notifications')) {
                    $notificationQuery = Notification::query()->where('user_id', auth()->id());
                    $unreadNotificationCount = (clone $notificationQuery)->where('is_read', false)->count();
                    $recentNotifications = $notificationQuery->latest()->limit(5)->get();
                }
            }

            $view->with(compact(
                'pendingReviewCount',
                'unreadNotificationCount',
                'expiringDocumentCount',
                'recentNotifications',
            ));
        });
    }
}
