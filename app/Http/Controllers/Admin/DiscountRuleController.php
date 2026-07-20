<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DiscountRuleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $school = $request->user()->school;
        $data = $request->validate([
            'course_count' => [
                'required', 'integer', 'min:2', 'max:100',
                Rule::unique('discount_rules')->where('school_id', $school->id),
            ],
            'percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ]);

        $school->discountRules()->create($data);

        return back()->with('success', 'Le palier de rabais a été ajouté.');
    }

    public function destroy(Request $request, DiscountRule $discountRule): RedirectResponse
    {
        abort_unless($discountRule->school_id === $request->user()->school_id, 404);
        $discountRule->delete();

        return back()->with('success', 'Le palier de rabais a été supprimé.');
    }
}
