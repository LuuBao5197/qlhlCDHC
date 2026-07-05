<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Modules\Schedule\Application\TeachingSupportChangeRequest\TeachingSupportChangeRequestPolicy;
use Modules\Schedule\Application\TeachingSupportRequest\TeachingSupportRequestPolicy;
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
            'vendors/jvectormap/jquery-jvectormap.css',
            'vendors/flag-icon-css/css/flag-icon.min.css',
            'vendors/owl-carousel-2/owl.carousel.min.css',
            'vendors/owl-carousel-2/owl.theme.default.min.css',
            'vendors/bootstrap-datepicker/css/bootstrap-datepicker3.min.css',
            'css/style.css',
            'css/schedule-date-picker.css',
        ]);
        View::share('resourceDashboardScripts', [
            'vendors/js/vendor.bundle.base.js',
            'vendors/bootstrap-datepicker/js/bootstrap-datepicker.min.js',
            'vendors/bootstrap-datepicker/js/bootstrap-datepicker.vi.min.js',
            'vendors/chart.js/Chart.min.js',
            'vendors/progressbar.js/progressbar.min.js',
            'vendors/jvectormap/jquery-jvectormap.min.js',
            'vendors/jvectormap/jquery-jvectormap-world-mill-en.js',
            'vendors/owl-carousel-2/owl.carousel.min.js',
            'js/off-canvas.js',
            'js/hoverable-collapse.js',
            'js/misc.js',
            'js/settings.js',
            'js/todolist.js',
            'js/dashboard.js',
            'js/schedule-date-picker.js',
        ]);
    }
}
