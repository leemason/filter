<?php
/**
 * Created by PhpStorm.
 * User: leemason
 * Date: 08/11/15
 * Time: 17:16
 */

namespace LeeMason\Filter;


use Illuminate\Support\ServiceProvider;

class FilterServiceProvider extends ServiceProvider
{

    public function register(){
        $this->app->singleton(Dispatcher::class, function ($app) {
            return new Dispatcher($app);
        });
    }

    public function boot(){

    }

}