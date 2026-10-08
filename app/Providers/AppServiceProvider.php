<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

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
        $this->configureDefaults();
        $this->configureErrorPages();
    }

    /**
     * Branded error pages for page visits; JSON callers (auto-save, uploads)
     * keep plain JSON errors. An expired page (419) goes back with a toast.
     */
    protected function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $status = $response->statusCode();

            if ($response->request->expectsJson() && ! $response->request->header('X-Inertia')) {
                return null;
            }

            if ($status === 419) {
                Inertia::flash('toast', ['type' => 'error', 'message' => 'This page had expired. Please try again.']);

                return back();
            }

            // Developers keep the detailed error screen.
            if ($status >= 500 && config('app.debug')) {
                return null;
            }

            if (in_array($status, [403, 404, 429, 500, 503], true)) {
                return $response->render('Error', ['status' => $status])->withSharedData();
            }

            return null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Inertia props are shaped by resources; no `data` envelope.
        JsonResource::withoutWrapping();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // N+1 guard: lazy loading throws in development and tests, and is only
        // logged in production so a missed eager load never breaks a page.
        Model::preventLazyLoading();
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        if (app()->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
                Log::warning('Lazy loading', ['model' => $model::class, 'relation' => $relation, 'url' => request()->fullUrl()]);
            });
        }

        // Mirrors the AU-03 checklist: 8+ characters, a number, upper & lower case, a symbol.
        Password::defaults(fn (): Password => Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols());
    }
}
