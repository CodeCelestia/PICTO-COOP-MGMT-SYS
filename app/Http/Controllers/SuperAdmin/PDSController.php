<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PersonalDataSheet;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PDSController extends Controller
{
    public function index(Request $request)
    {
        $pdsRecords = PersonalDataSheet::with('user')
            ->when($request->search, function ($q) use ($request) {
                $s = $request->search;
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            })
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('super-admin/PDS/Index', [
            'pdsRecords' => $pdsRecords,
            'filters'    => ['search' => $request->search ?? ''],
        ]);
    }

    public function create()
    {
        return Inertia::render('super-admin/PDS/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'last_name'         => 'required|string|max:255',
            'name_extension'    => 'nullable|string|max:20',
            'date_of_birth'     => 'required|date',
            'place_of_birth'    => 'nullable|string|max:255',
            'gender'            => 'required|in:Male,Female,Other',
            'civil_status'      => 'nullable|string|max:50',
            'height'            => 'nullable|numeric',
            'weight'            => 'nullable|numeric',
            'blood_type'        => 'nullable|string|max:5',
            'citizenship'       => 'required|string|max:255',
            'dual_citizenship_type' => 'nullable|string|max:50',
            'dual_country'      => 'nullable|string|max:100',
            'gsis_id'           => 'nullable|string|max:50',
            'sss_no'            => 'nullable|string|max:50',
            'philhealth_no'     => 'nullable|string|max:50',
            'pagibig_no'        => 'nullable|string|max:50',
            'tin_no'            => 'nullable|string|max:50',
            'telephone_no'      => 'nullable|string|max:20',
            'phone_number'      => 'nullable|string|max:20',
            'email'             => 'required|email|unique:personal_data_sheets,email',
            // Residential
            'region_code'       => 'nullable|string|max:20',
            'region_name'       => 'nullable|string|max:255',
            'province_code'     => 'nullable|string|max:20',
            'province_name'     => 'nullable|string|max:255',
            'city_municipality_code' => 'nullable|string|max:20',
            'city_municipality_name' => 'nullable|string|max:255',
            'barangay_code'     => 'nullable|string|max:20',
            'barangay_name'     => 'nullable|string|max:255',
            'street_address'    => 'nullable|string|max:255',
            'res_house'         => 'nullable|string|max:100',
            'res_subdivision'   => 'nullable|string|max:255',
            'res_zip'           => 'nullable|string|max:10',
            // Permanent
            'perm_same_as_res'  => 'nullable|boolean',
            'perm_house'        => 'nullable|string|max:100',
            'perm_street'       => 'nullable|string|max:255',
            'perm_subdivision'  => 'nullable|string|max:255',
            'perm_zip'          => 'nullable|string|max:10',
            'perm_region_code'  => 'nullable|string|max:20',
            'perm_region_name'  => 'nullable|string|max:255',
            'perm_province_code'=> 'nullable|string|max:20',
            'perm_province_name'=> 'nullable|string|max:255',
            'perm_city_municipality_code' => 'nullable|string|max:20',
            'perm_city_municipality_name' => 'nullable|string|max:255',
            'perm_barangay_code'=> 'nullable|string|max:20',
            'perm_barangay_name'=> 'nullable|string|max:255',
            // Family
            'spouse_surname'    => 'nullable|string|max:255',
            'spouse_first_name' => 'nullable|string|max:255',
            'spouse_middle_name'=> 'nullable|string|max:255',
            'spouse_name_extension' => 'nullable|string|max:20',
            'spouse_occupation' => 'nullable|string|max:255',
            'spouse_employer'   => 'nullable|string|max:255',
            'spouse_business_address' => 'nullable|string|max:255',
            'spouse_telephone'  => 'nullable|string|max:20',
            'father_surname'    => 'nullable|string|max:255',
            'father_first_name' => 'nullable|string|max:255',
            'father_middle_name'=> 'nullable|string|max:255',
            'father_name_extension' => 'nullable|string|max:20',
            'mother_surname'    => 'nullable|string|max:255',
            'mother_first_name' => 'nullable|string|max:255',
            'mother_middle_name'=> 'nullable|string|max:255',
            // JSON sub-records
            'children'          => 'nullable|array',
            'education'         => 'nullable|array',
            'eligibilities'     => 'nullable|array',
            'work_experiences'  => 'nullable|array',
            'voluntary_works'   => 'nullable|array',
            'learning_developments' => 'nullable|array',
            'references_list'   => 'nullable|array',
            // Other info
            'special_skills'    => 'nullable|string',
            'non_academic_distinctions' => 'nullable|string',
            'memberships'       => 'nullable|string',
            'questions'         => 'nullable|array',
            'government_issued_id' => 'nullable|string|max:255',
            'id_no'             => 'nullable|string|max:100',
            'id_issue_place'    => 'nullable|string|max:255',
            'date_accomplished' => 'nullable|date',
        ]);

        $pds = PersonalDataSheet::create($validated);

        return redirect()->route('super-admin.pds.show', $pds)
            ->with('success', 'PDS saved successfully.');
    }

    public function show(PersonalDataSheet $pd)
    {
        $pd->load('user');
        return Inertia::render('super-admin/PDS/Show', ['pds' => $pd]);
    }

    public function edit(PersonalDataSheet $pd)
    {
        return Inertia::render('super-admin/PDS/Edit', ['pds' => $pd]);
    }

    public function update(Request $request, PersonalDataSheet $pd)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'middle_name'       => 'nullable|string|max:255',
            'last_name'         => 'required|string|max:255',
            'name_extension'    => 'nullable|string|max:20',
            'date_of_birth'     => 'required|date',
            'place_of_birth'    => 'nullable|string|max:255',
            'gender'            => 'required|in:Male,Female,Other',
            'civil_status'      => 'nullable|string|max:50',
            'height'            => 'nullable|numeric',
            'weight'            => 'nullable|numeric',
            'blood_type'        => 'nullable|string|max:5',
            'citizenship'       => 'required|string|max:255',
            'dual_citizenship_type' => 'nullable|string|max:50',
            'dual_country'      => 'nullable|string|max:100',
            'gsis_id'           => 'nullable|string|max:50',
            'sss_no'            => 'nullable|string|max:50',
            'philhealth_no'     => 'nullable|string|max:50',
            'pagibig_no'        => 'nullable|string|max:50',
            'tin_no'            => 'nullable|string|max:50',
            'telephone_no'      => 'nullable|string|max:20',
            'phone_number'      => 'nullable|string|max:20',
            'email'             => 'required|email|unique:personal_data_sheets,email,' . $pd->id,
            'region_code'       => 'nullable|string|max:20',
            'region_name'       => 'nullable|string|max:255',
            'province_code'     => 'nullable|string|max:20',
            'province_name'     => 'nullable|string|max:255',
            'city_municipality_code' => 'nullable|string|max:20',
            'city_municipality_name' => 'nullable|string|max:255',
            'barangay_code'     => 'nullable|string|max:20',
            'barangay_name'     => 'nullable|string|max:255',
            'street_address'    => 'nullable|string|max:255',
            'res_house'         => 'nullable|string|max:100',
            'res_subdivision'   => 'nullable|string|max:255',
            'res_zip'           => 'nullable|string|max:10',
            'perm_same_as_res'  => 'nullable|boolean',
            'perm_house'        => 'nullable|string|max:100',
            'perm_street'       => 'nullable|string|max:255',
            'perm_subdivision'  => 'nullable|string|max:255',
            'perm_zip'          => 'nullable|string|max:10',
            'perm_region_code'  => 'nullable|string|max:20',
            'perm_region_name'  => 'nullable|string|max:255',
            'perm_province_code'=> 'nullable|string|max:20',
            'perm_province_name'=> 'nullable|string|max:255',
            'perm_city_municipality_code' => 'nullable|string|max:20',
            'perm_city_municipality_name' => 'nullable|string|max:255',
            'perm_barangay_code'=> 'nullable|string|max:20',
            'perm_barangay_name'=> 'nullable|string|max:255',
            'spouse_surname'    => 'nullable|string|max:255',
            'spouse_first_name' => 'nullable|string|max:255',
            'spouse_middle_name'=> 'nullable|string|max:255',
            'spouse_name_extension' => 'nullable|string|max:20',
            'spouse_occupation' => 'nullable|string|max:255',
            'spouse_employer'   => 'nullable|string|max:255',
            'spouse_business_address' => 'nullable|string|max:255',
            'spouse_telephone'  => 'nullable|string|max:20',
            'father_surname'    => 'nullable|string|max:255',
            'father_first_name' => 'nullable|string|max:255',
            'father_middle_name'=> 'nullable|string|max:255',
            'father_name_extension' => 'nullable|string|max:20',
            'mother_surname'    => 'nullable|string|max:255',
            'mother_first_name' => 'nullable|string|max:255',
            'mother_middle_name'=> 'nullable|string|max:255',
            'children'          => 'nullable|array',
            'education'         => 'nullable|array',
            'eligibilities'     => 'nullable|array',
            'work_experiences'  => 'nullable|array',
            'voluntary_works'   => 'nullable|array',
            'learning_developments' => 'nullable|array',
            'references_list'   => 'nullable|array',
            'special_skills'    => 'nullable|string',
            'non_academic_distinctions' => 'nullable|string',
            'memberships'       => 'nullable|string',
            'questions'         => 'nullable|array',
            'government_issued_id' => 'nullable|string|max:255',
            'id_no'             => 'nullable|string|max:100',
            'id_issue_place'    => 'nullable|string|max:255',
            'date_accomplished' => 'nullable|date',
        ]);

        $pd->update($validated);

        return redirect()->route('super-admin.pds.show', $pd)
            ->with('success', 'PDS updated successfully.');
    }

    public function destroy(PersonalDataSheet $pd)
    {
        $pd->delete();
        return redirect()->route('super-admin.pds.index')
            ->with('success', 'PDS deleted.');
    }
}