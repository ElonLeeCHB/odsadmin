<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
// use App\Models\Catalog\Product;
// use App\Observers\ProductObserver;
// use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen(\App\Events\SaleOrderSavedEvent::class, \App\Listeners\SaleOrderSavedListener::class);
    }
}
