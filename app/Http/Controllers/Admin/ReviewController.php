<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Reviews\ApproveInspection;
use App\Actions\Reviews\RequestChanges;
use App\Http\Controllers\Controller;
use App\Models\Inspection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * NSD review of a submitted report: approve (issues the certificate) or send
 * it back with a reason.
 */
class ReviewController extends Controller
{
    public function approve(Request $request, Inspection $inspection, ApproveInspection $approve): RedirectResponse
    {
        Gate::authorize('review', $inspection);

        $approve->handle($inspection, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Approved. Certificate {$inspection->ticket_no} issued."]);

        return back();
    }

    public function requestChanges(Request $request, Inspection $inspection, RequestChanges $requestChanges): RedirectResponse
    {
        Gate::authorize('review', $inspection);

        $validated = $request->validate(
            ['reason' => ['required', 'string', 'min:10', 'max:2000']],
            ['reason.required' => 'Tell the contractor what to change.', 'reason.min' => 'Give the contractor a little more detail.'],
        );

        $requestChanges->handle($inspection, $request->user(), trim($validated['reason']));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sent back to the contractor with your note.']);

        return back();
    }
}
