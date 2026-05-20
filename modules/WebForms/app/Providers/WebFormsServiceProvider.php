<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.7.0
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2025 KONKORD DIGITAL
 */

namespace Modules\WebForms\Providers;

use Modules\Core\Settings\SettingsMenuItem;
use Modules\Core\Support\ModuleServiceProvider;
use Modules\Users\Events\TransferringUserData;
use Modules\WebForms\Listeners\TransferWebFormUserData;

class WebFormsServiceProvider extends ModuleServiceProvider
{
    protected bool $withViews = true;

    protected array $mailableTemplates = [
        \Modules\WebForms\Mail\WebFormSubmitted::class,
    ];

    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        $this->app['events']->listen(TransferringUserData::class, TransferWebFormUserData::class);
    }

    /**
     * Register any module services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Provide the settins menu items.
     */
    protected function settingsMenu(): SettingsMenuItem
    {
        return SettingsMenuItem::make('web-forms', __('webforms::form.forms'))
            ->withChildren([
                SettingsMenuItem::make('opnform-dashboard', 'Forms Dashboard')
                    ->path('/forms')
                    ->icon('ViewGrid')
                    ->order(10),
                SettingsMenuItem::make('opnform-create', 'Create Form')
                    ->path('/forms/create')
                    ->icon('Plus')
                    ->order(20),
                SettingsMenuItem::make('opnform-integrations', 'Integrations')
                    ->path('/forms/integrations')
                    ->icon('Puzzle')
                    ->order(30),
                SettingsMenuItem::make('opnform-account', 'Form Account')
                    ->path('/forms/account')
                    ->icon('User')
                    ->order(40),
            ])
            ->icon('MenuAlt3')
            ->order(30);
    }

    /**
     * Provide the module name.
     */
    protected function moduleName(): string
    {
        return 'WebForms';
    }

    /**
     * Provide the module name in lowercase.
     */
    protected function moduleNameLower(): string
    {
        return 'webforms';
    }
}
