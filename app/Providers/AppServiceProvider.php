<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use App\Services\QuizValidationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(QuizValidationService::class, function ($app) {
            return new QuizValidationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );
            })
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo) {
                $routeMiddleware = $routeInfo->route->gatherMiddleware();
                $hasAuthMiddleware = collect($routeMiddleware)->contains(
                    fn($m) => Str::contains($m, 'auth:'),
                );

                if (!$hasAuthMiddleware) {
                    $operation->security = [];
                }
            });

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return config('app.frontend_url') . '/reset-password?token=' . $token;
        });
    }
}
