<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SeasonController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('seasons')->where('school_id', $request->user()->school_id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['required', 'boolean'],
        ]);
        $request->user()->school->seasons()->create($data);

        return back()->with('success', 'La saison a été créée. Vous pouvez maintenant y rattacher des cours.');
    }

    public function update(Request $request, Season $season): RedirectResponse
    {
        abort_unless($season->school_id === $request->user()->school_id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('seasons')->where('school_id', $request->user()->school_id)->ignore($season)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['required', 'boolean'],
        ]);
        $season->update($data);

        return back()->with('success', 'La saison a été mise à jour.');
    }

    public function destroy(Request $request, Season $season): RedirectResponse
    {
        abort_unless($season->school_id === $request->user()->school_id, 404);
        $season->delete();

        return back()->with('success', 'La saison a été supprimée.');
    }
}
