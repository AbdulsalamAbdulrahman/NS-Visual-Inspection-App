<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Public terms, privacy and refund policy pages (needed for Monnify KYC).
 */
class PolicyController extends Controller
{
    public const PAGES = ['terms', 'privacy', 'refunds'];

    public function __invoke(string $page): Response
    {
        return Inertia::render('public/Policy', ['page' => $page]);
    }
}
