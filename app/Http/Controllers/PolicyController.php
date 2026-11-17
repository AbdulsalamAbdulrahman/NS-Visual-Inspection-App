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
    /** 'refunds' is hidden until the refund terms are confirmed; its text stays in Policy.svelte. */
    public const PAGES = ['terms', 'privacy'];

    public function __invoke(string $page): Response
    {
        return Inertia::render('public/Policy', ['page' => $page]);
    }
}
