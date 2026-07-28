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
            'discount_type' => ['sometimes', 'in:percentage,fixed'],
            'percentage' => ['nullable', 'required_if:discount_type,percentage', 'numeric', 'min:0.01', 'max:100'],
            'fixed_amount' => ['nullable', 'required_if:discount_type,fixed', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);
        $data['discount_type'] ??= 'percentage';
        $data['percentage'] = $data['discount_type'] === 'percentage' ? $data['percentage'] : 0;
        $data['fixed_amount'] = $data['discount_type'] === 'fixed' ? $data['fixed_amount'] : 0;

        $school->discountRules()->create($data);

        return back()->with('success', 'Le palier de rabais a été ajouté.');
    }

    public function destroy(Request $request, DiscountRule $discountRule): RedirectResponse
    {
        abort_unless($discountRule->school_id === $request->user()->school_id, 404);
        $discountRule->delete();

        return back()->with('success', 'Le palier de rabais a été supprimé.');
    }

    public function update(Request $request, DiscountRule $discountRule): RedirectResponse
    {
        abort_unless($discountRule->school_id === $request->user()->school_id, 404);
        $data = $request->validate([
            'course_count' => ['required', 'integer', 'min:2', 'max:100', Rule::unique('discount_rules')->where('school_id', $request->user()->school_id)->ignore($discountRule->id)],
            'discount_type' => ['sometimes', 'in:percentage,fixed'],
            'percentage' => ['nullable', 'required_if:discount_type,percentage', 'numeric', 'min:0.01', 'max:100'],
            'fixed_amount' => ['nullable', 'required_if:discount_type,fixed', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);
        $data['discount_type'] ??= 'percentage';
        $data['percentage'] = $data['discount_type'] === 'percentage' ? $data['percentage'] : 0;
        $data['fixed_amount'] = $data['discount_type'] === 'fixed' ? $data['fixed_amount'] : 0;
        $discountRule->update($data);
        return back()->with('success', 'Le palier de rabais a été modifié.');
    }
}
