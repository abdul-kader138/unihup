<?php

namespace App\Http\Controllers;

use App\Filament\Pages\CompareShortlist;
use App\Models\DegreeProgram;
use App\Support\CompareGrid;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Print-friendly PDF of the signed-in student's Compare view. Scoped to
 * their own shortlist — `programs` in the query string is filtered against
 * it exactly like App\Filament\Pages\CompareShortlist does, so the URL can
 * be shared without leaking anyone else's list.
 */
class ComparePdfController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $requested = collect($request->query('programs', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(CompareShortlist::MAX_COLUMNS)
            ->values();

        $programs = $request->user()->shortlistedPrograms()
            ->with(['university', 'subject'])
            ->whereIn('degree_programs.id', $requested)
            ->get()
            ->sortBy(fn (DegreeProgram $p) => $requested->search($p->id))
            ->values();

        abort_if($programs->isEmpty(), 404);

        $grid = CompareGrid::build($programs);

        return Pdf::loadView('pdf.compare', [
            'grid' => $grid,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape')->download('university-comparison.pdf');
    }
}
