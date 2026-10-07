<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Http\Resources\AuthUserResource;
use App\Models\FeeSchedule;
use App\Models\ServiceArea;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => fn () => $this->authUser($request),
            ],
            'adminNav' => fn () => $request->user()?->isAdmin() ? $this->adminNav() : null,
            'nsd' => [
                'phone' => config('kens.nsd.phone'),
                'email' => config('kens.nsd.email'),
            ],
        ];
    }

    /**
     * Sidebar counts and the current fee for the admin shell (AD sidebar, AM-08).
     *
     * @return array<string, int|string|null>
     */
    private function adminNav(): array
    {
        $fee = FeeSchedule::currentAmountKobo();

        return [
            'contractors' => User::query()->role(Role::Contractor)->count(),
            'reps' => User::query()->role(Role::Rep)->count(),
            'areas' => ServiceArea::query()->count(),
            'fee' => $fee === null ? null : Money::format($fee),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authUser(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $user->loadMissing(match (true) {
            $user->isContractor() => ['contractorProfile'],
            $user->isRep() => ['serviceAreas'],
            default => [],
        });

        return AuthUserResource::make($user)->resolve($request);
    }
}
