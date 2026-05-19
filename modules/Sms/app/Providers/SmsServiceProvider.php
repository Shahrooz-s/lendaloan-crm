<?php

namespace Modules\Sms\Providers;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Settings\SettingsMenuItem;
use Modules\Core\Support\ModuleServiceProvider;
use Modules\Core\Facades\Menu;
use Modules\Core\Menu\MenuItem;
use Modules\Sms\Console\SendScheduledSms;
use Modules\Sms\Http\Middleware\ModuleActivationMiddleware;
use Modules\Sms\Models\Sms;
use Illuminate\Routing\Router;
use Modules\MailClient\Console\Commands\SendScheduledEmails;
use Modules\Sms\Http\Middleware\TwilioRequestIsValid;

class SmsServiceProvider extends ModuleServiceProvider
{
    protected array $resources = [
        \Modules\Sms\Resources\Sms::class,
        \Modules\Sms\Resources\SmsTemplate::class,
    ];

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        $this->registerCommands();

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('is-twilio-request', TwilioRequestIsValid::class);

        $this->app->bind('TwilioService', \Modules\Sms\Services\TwilioService::class);

        // Innoclapps::permissions(function ($manager) {
        //     $group = ['name' => 'sms', 'as' => 'sms'];

        //     $manager->group($group, function ($manager){
        //         $manager->view('view', [
        //             'as' => __('core::role.capabilities.view'),
        //             'permissions' => [
        //                 'view own sms' => __('core::role.capabilities.owning_only'),
        //                 'view all sms' => __('core::role.capabilities.all', ['resourceName' => 'Sms']),
        //                 'view team sms' => __('users::team.capabilities.team_only'),
        //             ],
        //         ]);

        //         $manager->view('edit', [
        //             'as' => __('core::role.capabilities.edit'),
        //             'permissions' => [
        //                 'edit own sms' => __('core::role.capabilities.owning_only'),
        //                 'edit all sms' => __('core::role.capabilities.all', ['resourceName' => 'Sms']),
        //                 'edit team sms' => __('users::team.capabilities.team_only'),
        //             ],
        //         ]);

        //         $manager->view('delete', [
        //             'as' => __('core::role.capabilities.delete'),
        //             'revokeable' => true,
        //             'permissions' => [
        //                 'delete own sms' => __('core::role.capabilities.owning_only'),
        //                 'delete any sms' => __('core::role.capabilities.all', ['resourceName' => 'Sms']),
        //                 'delete team sms' => __('users::team.capabilities.team_only'),
        //             ],
        //         ]);

        //         $manager->view('bulk_delete', [
        //             'permissions' => [
        //                 'bulk delete sms' => __('core::role.capabilities.bulk_delete'),
        //             ],
        //         ]);
        //     });
        // });
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
        Innoclapps::vite('resources/js/app.js', [
            'buildDirectory' => 'modules/' . $this->moduleNameLower() . '/build',
            'hotFile' => storage_path('hot-' . $this->moduleNameLower())
        ]);
    }

    /**
     * Register module commands.
     */
    protected function registerCommands(): void
    {
        $kernel = $this->app->make(Kernel::class);

        $kernel->appendMiddlewareToGroup('web', ModuleActivationMiddleware::class);
        $this->commands([
            SendScheduledSms::class,
        ]);
    }

    /**
     * Schedule module tasks.
     */
    protected function scheduleTasks(Schedule $schedule): void
    {
        $schedule->job(SendScheduledSms::class)->withoutOverlapping()->everyMinute();
    }

    /**
     * Provide the data to share on the front-end.
     */
    protected function scriptData(): Closure|array
    {
        return [
            'sms' => []
        ];
    }

    /**
     * Provide the module name.
     */
    protected function moduleName(): string
    {
        return 'Sms';
    }

    /**
     * Provide the module name in lowercase.
     */
    protected function moduleNameLower(): string
    {
        return 'sms';
    }

    protected function menu()
    {
        if (!settings()->get('sms_module_active')) {
            $menuItem = MenuItem::make(__('sms::sms.sms'), '/settings/sms')
                ->icon('ChatBubbleBottomCenterText')
                ->position(16)
                ->badgeVariant('info');
        } else {
            $menuItem = MenuItem::make(__('sms::sms.sms'), '/sms')
                ->icon('ChatBubbleBottomCenterText')
                ->position(16)
                ->badgeVariant('info');
        }

        return [$menuItem];
    }

    /**
     * Provide the module name.
     */
    protected function name(): string
    {
        return 'Sms';
    }

    /**
     * Register the settings menu items for the resource
     */
    public function settingsMenu(): array
    {
        $version = \Modules\Core\Application::VERSION;
        $menus = [];

        if ($version == '1.5.0') {
            if (!settings()->get('sms_module_active')) {
                $menus[] = SettingsMenuItem::make(__('sms::sms.settings.title'), '/settings/sms')
                    ->icon('ChatBubbleBottomCenterText')
                    ->order(41);
            } else {
                $menus[] = SettingsMenuItem::make(__('sms::sms.sms_templates'), '/settings/sms-templates')
                    ->icon('ChatBubbleBottomCenterText')
                    ->order(41);
            }
        } else {
            if (!settings()->get('sms_module_active')) {
                $menus[] = SettingsMenuItem::make($this->name(), __('sms::sms.settings.title'))
                    ->path('/sms')
                    ->icon('ChatBubbleBottomCenterText')
                    ->order(41);
            } else {
                $menus[] = SettingsMenuItem::make($this->name(), __('sms::sms.sms_templates'))
                    ->path('/sms-templates')
                    ->icon('ChatBubbleBottomCenterText')
                    ->order(41);
            }
        }

        return $menus;
    }
}
