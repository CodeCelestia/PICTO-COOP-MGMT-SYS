<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $yesNo = [Rule::in(['Yes', 'No'])];

        return [
            'download' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in(['draft', 'final'])],

            'surname' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'name_extension' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['required', 'date'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'sex' => ['required', Rule::in(['Male', 'Female'])],
            'civil_status' => ['required', 'string', 'max:100'],
            'citizenship' => ['required', 'string', 'max:100'],
            'dual_country' => ['nullable', 'string', 'max:100'],
            'dual_citizenship_type' => ['nullable', Rule::in(['By Birth', 'By Naturalization'])],
            'height' => ['nullable', 'string', 'max:20'],
            'weight' => ['nullable', 'string', 'max:20'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'umid_id' => ['nullable', 'string', 'max:100'],
            'pagibig_id' => ['nullable', 'string', 'max:100'],
            'philhealth_no' => ['nullable', 'string', 'max:100'],
            'philsys_no' => ['nullable', 'string', 'max:100'],
            'tin_no' => ['nullable', 'string', 'max:100'],
            'agency_employee_no' => ['nullable', 'string', 'max:100'],
            'telephone_no' => ['nullable', 'string', 'max:50'],
            'mobile_no' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],

            'res_house_no' => ['nullable', 'string', 'max:255'],
            'res_street' => ['nullable', 'string', 'max:255'],
            'res_subdivision' => ['nullable', 'string', 'max:255'],
            'res_barangay' => ['nullable', 'string', 'max:255'],
            'res_city' => ['nullable', 'string', 'max:255'],
            'res_province' => ['nullable', 'string', 'max:255'],
            'res_zipcode' => ['nullable', 'string', 'max:20'],

            'perm_house_no' => ['nullable', 'string', 'max:255'],
            'perm_street' => ['nullable', 'string', 'max:255'],
            'perm_subdivision' => ['nullable', 'string', 'max:255'],
            'perm_barangay' => ['nullable', 'string', 'max:255'],
            'perm_city' => ['nullable', 'string', 'max:255'],
            'perm_province' => ['nullable', 'string', 'max:255'],
            'perm_zipcode' => ['nullable', 'string', 'max:20'],

            'spouse_surname' => ['nullable', 'string', 'max:255'],
            'spouse_firstname' => ['nullable', 'string', 'max:255'],
            'spouse_middlename' => ['nullable', 'string', 'max:255'],
            'spouse_extension' => ['nullable', 'string', 'max:50'],
            'spouse_occupation' => ['nullable', 'string', 'max:255'],
            'spouse_employer' => ['nullable', 'string', 'max:255'],
            'spouse_business_addr' => ['nullable', 'string', 'max:255'],
            'spouse_telephone' => ['nullable', 'string', 'max:50'],

            'father_surname' => ['nullable', 'string', 'max:255'],
            'father_firstname' => ['nullable', 'string', 'max:255'],
            'father_middlename' => ['nullable', 'string', 'max:255'],
            'father_extension' => ['nullable', 'string', 'max:50'],
            'mother_surname' => ['nullable', 'string', 'max:255'],
            'mother_firstname' => ['nullable', 'string', 'max:255'],
            'mother_middlename' => ['nullable', 'string', 'max:255'],

            'children' => ['nullable', 'array'],
            'children.*.name' => ['nullable', 'string', 'max:255'],
            'children.*.date_of_birth' => ['nullable', 'date'],

            'education' => ['nullable', 'array'],
            'education.*.school_name' => ['nullable', 'string', 'max:255'],
            'education.*.degree' => ['nullable', 'string', 'max:255'],
            'education.*.from' => ['nullable', 'string', 'max:20'],
            'education.*.to' => ['nullable', 'string', 'max:20'],
            'education.*.units' => ['nullable', 'string', 'max:100'],
            'education.*.year_graduated' => ['nullable', 'string', 'max:20'],
            'education.*.honors' => ['nullable', 'string', 'max:255'],

            'eligibility' => ['nullable', 'array'],
            'eligibility.*.name' => ['nullable', 'string', 'max:255'],
            'eligibility.*.rating' => ['nullable', 'string', 'max:50'],
            'eligibility.*.exam_date' => ['nullable', 'date'],
            'eligibility.*.exam_place' => ['nullable', 'string', 'max:255'],
            'eligibility.*.license_number' => ['nullable', 'string', 'max:100'],
            'eligibility.*.license_validity' => ['nullable', 'date'],

            'work_experience' => ['nullable', 'array'],
            'work_experience.*.date_from' => ['nullable', 'date'],
            'work_experience.*.date_to' => ['nullable', 'date'],
            'work_experience.*.position_title' => ['nullable', 'string', 'max:255'],
            'work_experience.*.dept_agency' => ['nullable', 'string', 'max:255'],
            'work_experience.*.monthly_salary' => ['nullable', 'string', 'max:100'],
            'work_experience.*.salary_grade' => ['nullable', 'string', 'max:100'],
            'work_experience.*.status_appointment' => ['nullable', 'string', 'max:100'],
            'work_experience.*.govt_service' => ['nullable', Rule::in(['Y', 'N'])],

            'voluntary_work' => ['nullable', 'array'],
            'voluntary_work.*.organization' => ['nullable', 'string', 'max:255'],
            'voluntary_work.*.date_from' => ['nullable', 'date'],
            'voluntary_work.*.date_to' => ['nullable', 'date'],
            'voluntary_work.*.hours' => ['nullable', 'string', 'max:50'],
            'voluntary_work.*.position' => ['nullable', 'string', 'max:255'],

            'learning_development' => ['nullable', 'array'],
            'learning_development.*.title' => ['nullable', 'string', 'max:255'],
            'learning_development.*.date_from' => ['nullable', 'date'],
            'learning_development.*.date_to' => ['nullable', 'date'],
            'learning_development.*.hours' => ['nullable', 'string', 'max:50'],
            'learning_development.*.type' => ['nullable', 'string', 'max:100'],
            'learning_development.*.conducted_by' => ['nullable', 'string', 'max:255'],

            'special_skills' => ['nullable', 'array'],
            'special_skills.*' => ['nullable', 'string', 'max:255'],
            'recognitions' => ['nullable', 'array'],
            'recognitions.*' => ['nullable', 'string', 'max:255'],
            'memberships' => ['nullable', 'array'],
            'memberships.*' => ['nullable', 'string', 'max:255'],

            'q34' => $yesNo,
            'q35' => $yesNo,
            'q36' => $yesNo,
            'q37' => $yesNo,
            'q38a' => $yesNo,
            'q38b' => $yesNo,
            'q39' => $yesNo,
            'q40a' => $yesNo,
            'q40b' => $yesNo,
            'q41' => $yesNo,
            'q34_details' => ['nullable', 'string'],
            'q35_details' => ['nullable', 'string'],
            'q36_details' => ['nullable', 'string'],
            'q37_details' => ['nullable', 'string'],
            'q38a_details' => ['nullable', 'string'],
            'q38b_details' => ['nullable', 'string'],
            'q39_details' => ['nullable', 'string'],
            'q40a_details' => ['nullable', 'string'],
            'q40b_details' => ['nullable', 'string'],
            'q41_details' => ['nullable', 'string'],

            'references' => ['nullable', 'array'],
            'references.*.name' => ['nullable', 'string', 'max:255'],
            'references.*.address' => ['nullable', 'string', 'max:255'],
            'references.*.tel_no' => ['nullable', 'string', 'max:100'],

            'govt_id_type' => ['nullable', 'string', 'max:255'],
            'govt_id_no' => ['nullable', 'string', 'max:255'],
            'id_issue_date' => ['nullable', 'date'],
            'id_issue_place' => ['nullable', 'string', 'max:255'],
            'signature_date' => ['nullable', 'date'],
        ];
    }
}
