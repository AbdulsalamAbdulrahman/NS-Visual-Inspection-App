<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Accounts\ImportContractors;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk registration of contractors from the CSV template: upload, check
 * every row, preview, then register the valid ones.
 */
class ContractorImportController extends Controller
{
    private const SESSION_KEY = 'admin.contractor_import';

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, ImportContractors::COLUMNS, escape: '');
            // Dates are optional: a contractor without them adds them at first login.
            fputcsv($out, ['Aisha Lawal', 'aisha.lawal@example.com', '08062201145', 'Kaduna', 'Cat B', 'NEMSAB20220388', '', '', '', '', '15/01/2025', '14/01/2028'], escape: '');
            fputcsv($out, ['Danladi Electrical Services', 'info@danladi.example.com', '08027004410', 'Kaduna', 'Corporate', 'CC/CE1F/PR/08634', 'R.09931', 'Danladi Electrical Services Ltd', 'Engr. Ibrahim Danladi', 'COREN/R.4417', '', ''], escape: '');
            fclose($out);
        }, 'kens-contractors-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Check the uploaded CSV and show the preview; nothing is saved yet. */
    public function upload(Request $request, ImportContractors $import): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:2048', 'mimes:csv,txt']], ['file.mimes' => 'Upload the CSV template (.csv).']);

        try {
            $rows = $import->read($request->file('file')->getRealPath());
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $request->session()->put(self::SESSION_KEY, ['name' => $request->file('file')->getClientOriginalName(), 'rows' => $rows]);

        return to_route('admin.contractors.import');
    }

    public function preview(Request $request): Response
    {
        $import = $request->session()->get(self::SESSION_KEY);
        /** @var list<array{line: int, summary: array<string, string|null>, errors: list<string>}> $rows */
        $rows = is_array($import) ? $import['rows'] : [];

        return Inertia::render('admin/ContractorImport', [
            'fileName' => is_array($import) ? $import['name'] : null,
            'rows' => array_map(fn (array $row): array => [
                'line' => $row['line'],
                ...$row['summary'],
                'errors' => $row['errors'],
            ], $rows),
            'columns' => ImportContractors::COLUMNS,
            'maxRows' => ImportContractors::MAX_ROWS,
        ]);
    }

    public function confirm(Request $request, ImportContractors $import): RedirectResponse
    {
        $pending = $request->session()->pull(self::SESSION_KEY);

        if (! is_array($pending) || empty($pending['rows'])) {
            return to_route('admin.contractors.import');
        }

        $created = $import->create($pending['rows']);
        $skipped = count($pending['rows']) - $created;

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$created} contractor".($created === 1 ? '' : 's').' registered and emailed their login details'.($skipped > 0 ? ", {$skipped} skipped" : '').'.',
        ]);

        return to_route('admin.contractors.index');
    }

    public function discard(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return to_route('admin.contractors.import');
    }
}
