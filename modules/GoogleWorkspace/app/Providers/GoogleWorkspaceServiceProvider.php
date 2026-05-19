<?php

namespace Modules\GoogleWorkspace\Providers;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Modules\Core\Facades\Innoclapps;
use Modules\Core\Settings\SettingsMenuItem;
use Modules\Core\Support\ModuleServiceProvider;
use Modules\Core\Menu\MenuItem;
use Modules\GoogleWorkspace\Http\Middleware\ModuleActivationMiddleware;

class GoogleWorkspaceServiceProvider extends ModuleServiceProvider
{

    protected array $resources = [
        \Modules\GoogleWorkspace\Resources\GoogleDocs::class,
        \Modules\GoogleWorkspace\Resources\GoogleSheets::class,
        \Modules\GoogleWorkspace\Resources\GoogleSlides::class,
        \Modules\GoogleWorkspace\Resources\GoogleForms::class,
        \Modules\GoogleWorkspace\Resources\GoogleDrives::class,
    ];
    /**
     * Bootstrap any module services.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->app->bind('GoogleDocsService', \Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleDocsService::class);
        $this->app->bind('GoogleSheetsService', \Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleSheetsService::class);
        $this->app->bind('GoogleDriveService', \Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleDriveService::class);
        $this->app->bind('GoogleOAuthService', \Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleOAuthService::class);
        $this->app->bind('GoogleSlidesService', \Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleSlidesService::class);
        $this->app->bind('GoogleFormsService', \Modules\GoogleWorkspace\Services\GoogleWorkspace\GoogleFormsService::class);
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
    }

    /**
     * Schedule module tasks.
     */
    protected function scheduleTasks(Schedule $schedule): void
    {
        // $schedule->safeCommand('inspire')->hourly();
    }

    /**
     * Provide the data to share on the front-end.
     */
    protected function scriptData(): Closure|array
    {
        return [
            'googleworkspace' => []
        ];
    }

    /**
     * Provide the module name.
     */
    protected function moduleName(): string
    {
        return 'GoogleWorkspace';
    }

    /**
     * Provide the module name in lowercase.
     */
    protected function moduleNameLower(): string
    {
        return 'googleworkspace';
    }

    protected function menu()
    {
        if (!settings()->get('googleworkspace_module_active'))
            return [
                MenuItem::make('Google Workspace', '/settings/google-workspace/activation')
                    ->icon('Document')
                    ->position(19),
            ];

        return [
            MenuItem::make('Google Workspace', '/google-workspace')
                ->icon('Document')
                ->position(19),
        ];
    }

    public function settingsMenu(): array
    {
        $version = \Modules\Core\Application::VERSION;
        $menus = [];

        if ($version == '1.5.0') {
            if (!settings()->get('googleworkspace_module_active')) {
                $menus[] = SettingsMenuItem::make('Google Workspace', '/settings/google-workspace/activation')
                    ->icon('Document')
                    ->order(41);
            } else {
                $menus[] = SettingsMenuItem::make('Google Workspace', '/settings/google-workspace')
                    ->icon('Document')
                    ->order(41);
            }
        } else {
            if (!settings()->get('googleworkspace_module_active')) {
                $menus[] = SettingsMenuItem::make('google-workspace/activation', 'Google Workspace')
                    ->path('/google-workspace/activation')
                    ->icon('Document')
                    ->order(41);
            } else {
                $menus[] = SettingsMenuItem::make('google-workspace', 'Google Workspace')
                    ->path('/google-workspace')
                    ->icon('Document')
                    ->order(41);
            }
        }

        return $menus;
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName(), 'config/config.php'),
            $this->moduleNameLower()
        );
    }
}
