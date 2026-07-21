<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DanceDiscipline;
use App\Models\DanceLevel;
use App\Models\PricingCategory;
use App\Models\SchoolLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogReferenceController extends Controller
{
    private const TYPES = [
        'locations' => SchoolLocation::class,
        'disciplines' => DanceDiscipline::class,
        'levels' => DanceLevel::class,
        'categories' => PricingCategory::class,
    ];

    public function store(Request $request, string $type): RedirectResponse
    {
        $model = $this->model($type);
        $table = (new $model)->getTable();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique($table)->where('school_id', $request->user()->school_id)],
        ]);

        $attributes = ['school_id' => $request->user()->school_id, 'name' => $data['name']];
        $model::create($attributes);

        return back()->with('success', 'L’élément a été ajouté.');
    }

    public function destroy(Request $request, string $type, int $reference): RedirectResponse
    {
        $item = $this->model($type)::findOrFail($reference);
        abort_unless($item->school_id === $request->user()->school_id, 404);
        $item->delete();

        return back()->with('success', 'L’élément a été supprimé.');
    }

    /** @return class-string<Model> */
    private function model(string $type): string
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        return self::TYPES[$type];
    }
}
