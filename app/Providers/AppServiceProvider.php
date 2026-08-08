<?php

namespace App\Providers;

use App\Support\NotificationPresenter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Modules\Schedule\Application\DepartmentMonthlyAssignmentBatch\DepartmentMonthlyAssignmentBatchPolicy;
use Modules\Schedule\Application\MonthlyAssignmentDossier\MonthlyAssignmentDossierPolicy;
use Modules\Schedule\Application\TeachingSupportChangeRequest\TeachingSupportChangeRequestPolicy;
use Modules\Schedule\Application\TeachingSupportRequest\TeachingSupportRequestPolicy;
use Modules\Schedule\Models\DepartmentMonthlyAssignmentBatch;
use Modules\Schedule\Models\MonthlyAssignmentDossier;
use Modules\Schedule\Models\TeachingSupportChangeRequest;
use Modules\Schedule\Models\TeachingSupportRequest;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(TeachingSupportRequest::class, TeachingSupportRequestPolicy::class);
        Gate::policy(TeachingSupportChangeRequest::class, TeachingSupportChangeRequestPolicy::class);
        Gate::policy(DepartmentMonthlyAssignmentBatch::class, DepartmentMonthlyAssignmentBatchPolicy::class);
        Gate::policy(MonthlyAssignmentDossier::class, MonthlyAssignmentDossierPolicy::class);

        Factory::guessFactoryNamesUsing(static function (string $modelName): string {
            if (Str::startsWith($modelName, 'Modules\\') && Str::contains($modelName, '\\Models\\')) {
                return Str::replace('\\Models\\', '\\Database\\Factories\\', $modelName).'Factory';
            }

            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });

        $assetRoot = resource_path('assets');
        $mimeTypes = [
            'css' => 'text/css',
            'eot' => 'application/vnd.ms-fontobject',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'ttf' => 'font/ttf',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
        ];

        $assetPath = static function (string $relativePath) use ($assetRoot): ?string {
            $root = realpath($assetRoot);
            $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
            $path = realpath($assetRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));

            if (! $root || ! $path) {
                return null;
            }

            return ($path === $root || str_starts_with($path, $root.DIRECTORY_SEPARATOR)) ? $path : null;
        };

        $mimeType = static function (string $path) use ($mimeTypes): string {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            return $mimeTypes[$extension] ?? 'application/octet-stream';
        };

        $resourceAsset = static function (string $relativePath) use ($assetPath, $mimeType): string {
            $path = $assetPath($relativePath);

            if (! $path || ! is_file($path)) {
                return '';
            }

            return 'data:'.$mimeType($path).';base64,'.base64_encode(file_get_contents($path));
        };

        $normalizeAssetPath = static function (string $relativePath): string {
            $parts = [];

            foreach (explode('/', str_replace('\\', '/', $relativePath)) as $part) {
                if ($part === '' || $part === '.') {
                    continue;
                }

                if ($part === '..') {
                    array_pop($parts);
                    continue;
                }

                $parts[] = $part;
            }

            return implode('/', $parts);
        };

        $resourceInlineCss = static function (string $relativePath) use ($assetPath, $resourceAsset, $normalizeAssetPath): string {
            $path = $assetPath($relativePath);

            if (! $path || ! is_file($path)) {
                return '';
            }

            $css = file_get_contents($path);
            $css = preg_replace('/\/\*# sourceMappingURL=.*?\*\//', '', $css);
            $cssDirectory = str_replace('\\', '/', dirname($relativePath));

            return preg_replace_callback('/url\((["\']?)(?!data:|https?:|\/\/|#)([^)\'"]+)\1\)/i', static function (array $matches) use ($cssDirectory, $resourceAsset, $normalizeAssetPath): string {
                $url = trim($matches[2]);
                $urlPath = preg_split('/[?#]/', $url, 2)[0] ?? $url;
                $assetPath = $normalizeAssetPath($cssDirectory.'/'.$urlPath);
                $dataUri = $resourceAsset($assetPath);

                return $dataUri !== '' ? 'url("'.$dataUri.'")' : $matches[0];
            }, $css);
        };

        $resourceInlineJs = static function (string $relativePath) use ($assetPath): string {
            $path = $assetPath($relativePath);

            if (! $path || ! is_file($path)) {
                return '';
            }

            return preg_replace('/^\s*\/\/# sourceMappingURL=.*$/m', '', file_get_contents($path));
        };

        View::share('resourceAsset', $resourceAsset);
        View::share('resourceInlineCss', $resourceInlineCss);
        View::share('resourceInlineJs', $resourceInlineJs);
        View::share('resourceCoreStyles', [
            'vendors/mdi/css/materialdesignicons.min.css',
            'vendors/css/vendor.bundle.base.css',
            'css/style.css',
        ]);
        View::share('resourceCoreScripts', [
            'vendors/js/vendor.bundle.base.js',
            'js/off-canvas.js',
            'js/hoverable-collapse.js',
            'js/misc.js',
            'js/settings.js',
            'js/todolist.js',
        ]);
        View::share('resourceDashboardStyles', [
            'vendors/mdi/css/materialdesignicons.min.css',
            'vendors/css/vendor.bundle.base.css',
            'vendors/bootstrap-datepicker/css/bootstrap-datepicker3.min.css',
            'css/style.css',
            'css/schedule-date-picker.css',
        ]);
        View::share('resourceDashboardScripts', [
            'vendors/js/vendor.bundle.base.js',
            'vendors/bootstrap-datepicker/js/bootstrap-datepicker.min.js',
            'vendors/bootstrap-datepicker/js/bootstrap-datepicker.vi.min.js',
            'js/off-canvas.js',
            'js/hoverable-collapse.js',
            'js/misc.js',
            'js/settings.js',
            'js/schedule-date-picker.js',
        ]);

        View::composer('partials._navbar', function ($view): void {
            $user = auth()->user();

            if (! $user) {
                $view->with([
                    'dashboardNotifications' => collect(),
                    'dashboardUnreadNotificationCount' => 0,
                ]);

                return;
            }

            if (! Schema::hasTable('notifications')) {
                $view->with([
                    'dashboardNotifications' => collect(),
                    'dashboardUnreadNotificationCount' => 0,
                ]);

                return;
            }

            $notifications = $user->notifications()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($notification) => NotificationPresenter::present($notification));

            $view->with([
                'dashboardNotifications' => $notifications,
                'dashboardUnreadNotificationCount' => $user->unreadNotifications()->count(),
            ]);
        });
    }
}
