<?php

namespace Modules\Invoice\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Modules\Core\Database\State\DatabaseState;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Support\ModuleServiceProvider;
use Modules\Invoice\Database\State\EnsureInvoicePaidStatusExists;
use Modules\Invoice\Events\InvoiceCreate;
use Modules\Invoice\Events\InvoicePaidEvent;
use Modules\Invoice\Http\Middleware\ModuleActivationMiddleware;
use Modules\Invoice\Listeners\InvoiceCreate as InvoiceCreateListener;
use Modules\Invoice\Listeners\InvoicePaidListener;
use Modules\Invoice\Mail\Customer\InvoicePaidForCustomer;
use Modules\Invoice\Mail\Customer\NewInvoice;
use Modules\Invoice\Mail\SalesAgent\InvoicePaidForSalesAgent;
use Modules\Invoice\Notifications\Customer\InvoicePaidNotification as InvoicePaidNotificationForCustomer;
use Modules\Invoice\Notifications\SalesAgent\InvoicePaidNotification as InvoicePaidNotificationForSalesAgent;
use Modules\Invoice\Notifications\Customer\NewInvoiceNotification;
use Modules\Invoice\Services\ModuleInitializationService;

class InvoiceServiceProvider extends ModuleServiceProvider
{
    protected array $resources = [
        \Modules\Invoice\Resources\Invoice::class,
    ];

    protected array $mailableTemplates = [
        NewInvoice::class,
        InvoicePaidForCustomer::class,
        InvoicePaidForSalesAgent::class
    ];

    protected array $notifications = [
        NewInvoiceNotification::class,
        InvoicePaidNotificationForCustomer::class,
        InvoicePaidNotificationForSalesAgent::class,
    ];
    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->loadViewsFrom(
            module_path($this->moduleName(), 'resources/views'),
            $this->moduleNameLower()
        );

        Innoclapps::permissions(function ($manager) {
            $group = ['name' => 'invoices', 'as' => 'invoices'];

            $manager->group($group, function ($manager){
                $manager->view('view', [
                    'as' => __('core::role.capabilities.view'),
                    'permissions' => [
                        'view own invoices' => __('core::role.capabilities.owning_only'),
                        'view all invoices' => __('core::role.capabilities.all', ['resourceName' => 'Invoices']),
                        'view team invoices' => __('users::team.capabilities.team_only'),
                    ],
                ]);

                $manager->view('edit', [
                    'as' => __('core::role.capabilities.edit'),
                    'permissions' => [
                        'edit own invoices' => __('core::role.capabilities.owning_only'),
                        'edit all invoices' => __('core::role.capabilities.all', ['resourceName' => 'Invoices']),
                        'edit team invoices' => __('users::team.capabilities.team_only'),
                    ],
                ]);

                $manager->view('delete', [
                    'as' => __('core::role.capabilities.delete'),
                    'revokeable' => true,
                    'permissions' => [
                        'delete own invoices' => __('core::role.capabilities.owning_only'),
                        'delete any invoice' => __('core::role.capabilities.all', ['resourceName' => 'invoices']),
                        'delete team invoices' => __('users::team.capabilities.team_only'),
                    ],
                ]);

                $manager->view('bulk_delete', [
                    'permissions' => [
                        'bulk delete invoices' => __('core::role.capabilities.bulk_delete'),
                    ],
                ]);
            });
        });

        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'invoice');
        $this->mergeConfigFrom(__DIR__.'/../../config/paypal.php', 'invoice.paypal');
    }

    /**
     * Get the views path.
     */
    protected function getViewsPath(): string
    {
        return module_path($this->moduleName(), 'Resources/views');
    }


    /**
     * Register any module services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Configure the module.
     */
    protected function setup(): void
    {
        DatabaseState::register(EnsureInvoicePaidStatusExists::class);

        Innoclapps::whenReadyForServing(function () {
            Innoclapps::booting($this->shareDataToScript(...));

            Innoclapps::vite('resources/js/app.js', 'modules/'.$this->moduleNameLower().'/build');
        });
    }

    /**
     * Register module commands.
     */
    protected function registerCommands(): void
    {
        $kernel = $this->app->make(Kernel::class);

        $kernel->appendMiddlewareToGroup('web', ModuleActivationMiddleware::class);

        if (ModuleInitializationService::isModuleActive("invoice")) {
            Event::listen( InvoiceCreate::class, InvoiceCreateListener::class);
            Event::listen( InvoicePaidEvent::class, InvoicePaidListener::class);
        }
    }

    /**
     * Schedule module tasks.
     */
    protected function scheduleTasks(Schedule $schedule): void
    {
        // $schedule->safeCommand('inspire')->hourly();
    }

    /**
     * Share module related data to script.
     */
    protected function shareDataToScript() : void
    {
        Innoclapps::provideToScript([
            'invoice' => []
        ]);
    }

    /**
     * Provide the module name.
     */
    protected function moduleName(): string
    {
        return 'Invoice';
    }

    /**
     * Provide the module name in lowercase.
     */
    protected function moduleNameLower(): string
    {
        return 'invoice';
    }
}
