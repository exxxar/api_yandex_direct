<?php
/**
 * Created by PhpStorm.
 * User: exxxa
 * Date: 23.11.2017
 * Time: 10:42
 */

namespace App\Providers;


use App\Http\Controllers\API\YandexApi;
use Illuminate\Support\ServiceProvider;


class YandexApiServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton(YandexApi::class, function ($app) {
            return new YandexApi();
        });

    }
}