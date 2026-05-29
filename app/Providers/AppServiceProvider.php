<?php

namespace App\Providers;

use App\Models\Article;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        View::composer('layouts.app', function ($view) {
            if (!auth()->check() || !app()->bound('currentTenant')) {
                $view->with('lowStockAlerts', collect());
                return;
            }

            $alerts = Article::withSum('branchStocks as total_stock', 'quantity')
                ->where('is_active', true)
                ->get()
                ->filter(fn($a) => ($a->total_stock ?? 0) <= $a->stock_min)
                ->values();

            $view->with('lowStockAlerts', $alerts);
        });
    }
}
