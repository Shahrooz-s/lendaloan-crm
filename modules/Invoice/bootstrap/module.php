<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Modules\Core\Facades\MailableTemplates;
use Modules\Core\Facades\Module;
use Illuminate\Contracts\Foundation\Application;
use Modules\Deals\Models\Pipeline;
use Modules\Deals\Models\Stage;
use Illuminate\Support\Facades\Config;
use Modules\Invoice\Database\State\EnsureInvoicePaidStatusExists;
use Modules\Core\Database\State\DatabaseState;

return Module::configure('invoice')
    ->enabled(function (Application $app) {

        if (array_key_exists("module:clear-compiled", \Artisan::all()))
            Config::set("core.commands.optimize", "module:clear-compiled");

        DatabaseState::register(EnsureInvoicePaidStatusExists::class);
        DatabaseState::seed();

        settings()->set(['invoice_module_active' => false])->save();
        settings()->set(['invoice_activation_code' => null])->save();
        settings()->set(['invoice_verification_id' => null])->save();
        settings()->set(['invoice_last_verified_at' => null])->save();
        settings()->set(['invoice_product_token' => null])->save();
        settings()->set(['invoice_heartbeat' => null])->save();
        settings()->set(['run_optimize_command' => true])->save();
    })
    ->disabled(function (Application $app) {
        $pipeline = Pipeline::where('name', 'Sales Pipeline')->first();
        try
        {
            Stage::where([
                'pipeline_id' => $pipeline->id,
                'name' => 'Invoice Paid'
            ])->delete();
        } catch (QueryException $e)
        {
            Log::error("Error while deleting stage: " . $e->getMessage());
        }

        if (array_key_exists("module:clear-compiled", \Artisan::all()))
            Config::set("core.commands.optimize", "module:clear-compiled");

    })
    ->deleted(function (Application $app) {

        settings()->forget('invoice_module_active')->save();
        settings()->forget('invoice_activation_code')->save();
        settings()->forget('invoice_verification_id')->save();
        settings()->forget('invoice_last_verified_at')->save();
        settings()->forget('invoice_product_token')->save();
        settings()->forget('invoice_heartbeat')->save();
        settings()->forget('paypal_api_key')->save();
        settings()->forget('paypal_secret')->save();
        settings()->forget('paypal_mode')->save();
        settings()->forget('stripe_secret')->save();
        settings()->forget('stripe_api_key')->save();

    });
