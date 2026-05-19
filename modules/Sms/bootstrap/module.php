<?php

use Modules\Core\Facades\Module;
use Illuminate\Contracts\Foundation\Application;

return Module::configure('sms')
    ->onDeleteResetMigrations()
    ->enabled(function (Application $app) {
        if (array_key_exists('module:clear-compiled', \Artisan::all())) {
            config()->set('core.commands.optimize', 'module:clear-compiled');
        }
        settings()->set(['run_optimize_command' => true])->save();
    })
    ->disabled(function (Application $app) {
        if (array_key_exists('module:clear-compiled', \Artisan::all())) {
            config()->set('core.commands.optimize', 'module:clear-compiled');
        }
    })
    ->deleted(function (Application $app) {
        if (array_key_exists('module:clear-compiled', \Artisan::all())) {
            config()->set('core.commands.optimize', 'module:clear-compiled');
        }
    });
