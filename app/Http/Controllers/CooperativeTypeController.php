<?php

namespace App\Http\Controllers;

use App\Models\CooperativeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CooperativeTypeController extends Controller
{
    public function index()
    {
        $types = CooperativeType::with('children.children')
            ->where('level', 'region')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Cooperatives/Types/Index', [
            'types' => $types,
        ]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cooperative_types,slug',
            'description' => 'nullable|string',
            'level' => 'required|in:region,province,municipality',
            'parent_id' => 'nullable|exists:cooperative_types,id',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $v->validated();

        if ($data['level'] === 'province' && !$data['parent_id']) {
            throw ValidationException::withMessages(['parent_id' => 'Province type must have a parent region.']);
        }

        if ($data['level'] === 'municipality' && !$data['parent_id']) {
            throw ValidationException::withMessages(['parent_id' => 'Municipality type must have a parent province.']);
        }

        if ($data['level'] === 'province') {
            $parent = CooperativeType::find($data['parent_id']);
            if ($parent && $parent->level !== 'region') {
                throw ValidationException::withMessages(['parent_id' => 'Province parent must be a region type.']);
            }
        }

        if ($data['level'] === 'municipality') {
            $parent = CooperativeType::find($data['parent_id']);
            if ($parent && $parent->level !== 'province') {
                throw ValidationException::withMessages(['parent_id' => 'Municipality parent must be a province type.']);
            }
        }

        CooperativeType::create($data);

        return redirect()->back()->with('success', 'Cooperative type created.');
    }

    public function update(Request $request, CooperativeType $cooperativeType)
    {
        $v = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:cooperative_types,slug,' . $cooperativeType->id,
            'description' => 'nullable|string',
            'level' => 'sometimes|required|in:region,province,municipality',
            'parent_id' => 'nullable|exists:cooperative_types,id',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $v->validated();

        // same hierarchy checks as store
        if (array_key_exists('level', $data) || array_key_exists('parent_id', $data)) {
            $level = $data['level'] ?? $cooperativeType->level;
            $parentId = $data['parent_id'] ?? $cooperativeType->parent_id;

            if ($level === 'province' && !$parentId) {
                throw ValidationException::withMessages(['parent_id' => 'Province type must have a parent region.']);
            }

            if ($level === 'municipality' && !$parentId) {
                throw ValidationException::withMessages(['parent_id' => 'Municipality type must have a parent province.']);
            }

            if ($parentId) {
                $parent = CooperativeType::find($parentId);
                if ($parent && ($level === 'province' && $parent->level !== 'region' || $level === 'municipality' && $parent->level !== 'province')) {
                    throw ValidationException::withMessages(['parent_id' => 'Invalid parent level for selected hierarchy.']);
                }
            }
        }

        $cooperativeType->update($data);

        return redirect()->back()->with('success', 'Cooperative type updated.');
    }

    public function destroy(CooperativeType $cooperativeType)
    {
        $cooperativeType->delete();

        return redirect()->back()->with('success', 'Cooperative type deleted.');
    }
}
