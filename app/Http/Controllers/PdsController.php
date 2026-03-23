<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePdsRequest;
use App\Models\Cooperative;
use App\Models\PdsSubmission;
use App\Models\User;
use App\Services\PdsExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PdsController extends Controller
{
    public function __construct(private readonly PdsExportService $exportService)
    {
    }

    private function isCoopAdmin(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->hasRole('Coop Admin') || $user->account_type === 'Coop Admin')
            : false;
    }

    private function isProvincialAdmin(): bool
    {
        $user = auth()->user();

        return $user
            ? ($user->hasRole('Provincial Admin') || $user->account_type === 'Provincial Admin')
            : false;
    }

    private function canManageAny(): bool
    {
        return $this->isProvincialAdmin() || $this->isCoopAdmin();
    }

    private function currentUserRole(): string
    {
        if ($this->isProvincialAdmin()) {
            return 'Provincial Admin';
        }

        if ($this->isCoopAdmin()) {
            return 'Coop Admin';
        }

        return 'User';
    }

    private function resolvedCooperativeId(User $user): ?int
    {
        if ($user->coop_id) {
            return (int) $user->coop_id;
        }

        $assignmentCoopId = $user->coopAssignments()
            ->where(function (Builder $query) {
                $query->whereNull('status')
                    ->orWhere('status', 'active')
                    ->orWhere('status', 'Active');
            })
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', now()->toDateString());
            })
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->value('coop_id');

        return $assignmentCoopId ? (int) $assignmentCoopId : null;
    }

    private function canAccessSubmission(PdsSubmission $pds): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($this->isProvincialAdmin()) {
            return true;
        }

        if ($this->isCoopAdmin()) {
            $coopId = $this->resolvedCooperativeId($user);

            return $coopId && $pds->cooperative_id === $coopId;
        }

        return $pds->user_id === $user->id;
    }

    public function index(Request $request): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user && ($user->hasRole('Member') || $user->account_type === 'Member')) {
            return redirect()->route('pds.my');
        }

        $search = trim((string) $request->string('search'));
        $status = $request->string('status')->toString();
        $filterCoopId = $request->integer('coop_id') ?: null;

        $query = PdsSubmission::query()
            ->with([
                'user:id,name,coop_id',
                'cooperative:id,name',
            ]);

        if ($this->isProvincialAdmin()) {
            if ($filterCoopId) {
                $query->where('cooperative_id', $filterCoopId);
            }
        } elseif ($this->isCoopAdmin()) {
            $coopId = $this->resolvedCooperativeId($user);
            $query->where('cooperative_id', $coopId ?: 0);
        } else {
            $query->forUser($user->id);
        }

        if (in_array($status, ['draft', 'final'], true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function (Builder $builder) use ($search, $like) {
                if (is_numeric($search)) {
                    $builder->where('pds_submissions.id', (int) $search)
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(c1_data, '$.surname')) LIKE ?", [$like])
                        ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(c1_data, '$.first_name')) LIKE ?", [$like])
                        ->orWhereHas('user', function (Builder $q) use ($like) {
                            $q->where('name', 'like', $like);
                        })
                        ->orWhereHas('cooperative', function (Builder $q) use ($like) {
                            $q->where('name', 'like', $like);
                        });

                    return;
                }

                $builder
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(c1_data, '$.surname')) LIKE ?", [$like])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(c1_data, '$.first_name')) LIKE ?", [$like])
                    ->orWhereHas('user', function (Builder $q) use ($like) {
                        $q->where('name', 'like', $like);
                    })
                    ->orWhereHas('cooperative', function (Builder $q) use ($like) {
                        $q->where('name', 'like', $like);
                    });
            });
        }

        $submissions = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $cooperatives = Cooperative::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->when($this->isCoopAdmin(), function (Builder $q) use ($user) {
                $coopId = $this->resolvedCooperativeId($user);
                $q->where('id', $coopId ?: 0);
            })
            ->get();

        return Inertia::render('Pds/Index', [
            'submissions' => $submissions,
            'userRole' => $this->currentUserRole(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'coop_id' => $filterCoopId ? (string) $filterCoopId : '',
            ],
            'cooperatives' => $cooperatives,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Pds/Form', [
            'pds' => null,
            'isEdit' => false,
        ]);
    }

    public function myPds(): Response
    {
        $user = auth()->user();

        $pds = PdsSubmission::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->first();

        return Inertia::render('Pds/MyPds', [
            'pds' => $pds,
            'isEdit' => !is_null($pds),
        ]);
    }

    public function myPdsUpdate(StorePdsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $pds = PdsSubmission::query()
            ->where('user_id', $user->id)
            ->latest('updated_at')
            ->first();

        $coopId = $this->resolvedCooperativeId($user);

        $status = $request->boolean('download') || ($validated['status'] ?? 'draft') === 'final'
            ? 'final'
            : 'draft';

        $data = [
            'user_id' => $user->id,
            'cooperative_id' => $coopId,
            'status' => $status,
            'c1_data' => $this->c1Data($validated),
            'c2_data' => $this->c2Data($validated),
            'c3_data' => $this->c3Data($validated),
            'c4_data' => $this->c4Data($validated),
            'submitted_at' => $status === 'final' ? now() : null,
        ];

        if ($pds) {
            $pds->update($data);
        } else {
            $pds = PdsSubmission::create($data);
        }

        if ($request->boolean('download')) {
            session()->flash('pds_id', $pds->id);
            session()->flash('trigger_download', true);
        }

        return redirect()->route('pds.my')
            ->with('success', 'Your PDS has been saved.');
    }

    public function store(StorePdsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $coopId = $this->resolvedCooperativeId($user);

        $status = $request->boolean('download') || ($validated['status'] ?? 'draft') === 'final'
            ? 'final'
            : 'draft';

        $submission = PdsSubmission::create([
            'user_id' => $user->id,
            'cooperative_id' => $coopId,
            'status' => $status,
            'c1_data' => $this->c1Data($validated),
            'c2_data' => $this->c2Data($validated),
            'c3_data' => $this->c3Data($validated),
            'c4_data' => $this->c4Data($validated),
            'submitted_at' => $status === 'final' ? now() : null,
        ]);

        $message = $status === 'final'
            ? 'PDS saved as final submission.'
            : 'PDS draft saved successfully.';

        if ($request->boolean('download')) {
            return redirect()->route('pds.index')->with([
                'success' => $message,
                'trigger_download' => true,
                'pds_id' => $submission->id,
            ]);
        }

        return redirect()->route('pds.index')->with('success', $message);
    }

    public function edit(PdsSubmission $pds): Response
    {
        abort_unless($this->canAccessSubmission($pds), 403);

        return Inertia::render('Pds/Form', [
            'pds' => $pds,
            'isEdit' => true,
        ]);
    }

    public function update(StorePdsRequest $request, PdsSubmission $pds): RedirectResponse
    {
        abort_unless($this->canAccessSubmission($pds), 403);

        $validated = $request->validated();
        $status = $request->boolean('download') || ($validated['status'] ?? $pds->status) === 'final'
            ? 'final'
            : 'draft';

        $pds->update([
            'status' => $status,
            'c1_data' => $this->c1Data($validated),
            'c2_data' => $this->c2Data($validated),
            'c3_data' => $this->c3Data($validated),
            'c4_data' => $this->c4Data($validated),
            'submitted_at' => $status === 'final' ? now() : null,
        ]);

        $message = $status === 'final'
            ? 'PDS updated and marked as final.'
            : 'PDS draft updated successfully.';

        if ($request->boolean('download')) {
            return redirect()->route('pds.index')->with([
                'success' => $message,
                'trigger_download' => true,
                'pds_id' => $pds->id,
            ]);
        }

        return redirect()->route('pds.index')->with('success', $message);
    }

    public function download(PdsSubmission $pds)
    {
        abort_unless($this->canAccessSubmission($pds), 403);

        $outputPath = $this->exportService->generate($pds->toArray());

        $downloadName = 'CS_Form_212_Revised_2025_PDS_' . $pds->id . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->download(
            $outputPath,
            $downloadName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        )->deleteFileAfterSend(true);
    }

    public function destroy(PdsSubmission $pds): RedirectResponse
    {
        abort_unless($this->canAccessSubmission($pds), 403);

        $pds->delete();

        return back()->with('success', 'PDS submission deleted successfully.');
    }

    private function c1Data(array $validated): array
    {
        return [
            'surname' => $validated['surname'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'middle_name' => $validated['middle_name'] ?? null,
            'name_extension' => $validated['name_extension'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'place_of_birth' => $validated['place_of_birth'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'citizenship' => $validated['citizenship'] ?? null,
            'dual_country' => $validated['dual_country'] ?? null,
            'dual_citizenship_type' => $validated['dual_citizenship_type'] ?? null,
            'height' => $validated['height'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
            'umid_id' => $validated['umid_id'] ?? null,
            'pagibig_id' => $validated['pagibig_id'] ?? null,
            'philhealth_no' => $validated['philhealth_no'] ?? null,
            'philsys_no' => $validated['philsys_no'] ?? null,
            'tin_no' => $validated['tin_no'] ?? null,
            'agency_employee_no' => $validated['agency_employee_no'] ?? null,
            'telephone_no' => $validated['telephone_no'] ?? null,
            'mobile_no' => $validated['mobile_no'] ?? null,
            'email' => $validated['email'] ?? null,
            'res_house_no' => $validated['res_house_no'] ?? null,
            'res_street' => $validated['res_street'] ?? null,
            'res_subdivision' => $validated['res_subdivision'] ?? null,
            'res_barangay' => $validated['res_barangay'] ?? null,
            'res_city' => $validated['res_city'] ?? null,
            'res_province' => $validated['res_province'] ?? null,
            'res_zipcode' => $validated['res_zipcode'] ?? null,
            'perm_house_no' => $validated['perm_house_no'] ?? null,
            'perm_street' => $validated['perm_street'] ?? null,
            'perm_subdivision' => $validated['perm_subdivision'] ?? null,
            'perm_barangay' => $validated['perm_barangay'] ?? null,
            'perm_city' => $validated['perm_city'] ?? null,
            'perm_province' => $validated['perm_province'] ?? null,
            'perm_zipcode' => $validated['perm_zipcode'] ?? null,
            'spouse_surname' => $validated['spouse_surname'] ?? null,
            'spouse_firstname' => $validated['spouse_firstname'] ?? null,
            'spouse_middlename' => $validated['spouse_middlename'] ?? null,
            'spouse_extension' => $validated['spouse_extension'] ?? null,
            'spouse_occupation' => $validated['spouse_occupation'] ?? null,
            'spouse_employer' => $validated['spouse_employer'] ?? null,
            'spouse_business_addr' => $validated['spouse_business_addr'] ?? null,
            'spouse_telephone' => $validated['spouse_telephone'] ?? null,
            'father_surname' => $validated['father_surname'] ?? null,
            'father_firstname' => $validated['father_firstname'] ?? null,
            'father_middlename' => $validated['father_middlename'] ?? null,
            'father_extension' => $validated['father_extension'] ?? null,
            'mother_surname' => $validated['mother_surname'] ?? null,
            'mother_firstname' => $validated['mother_firstname'] ?? null,
            'mother_middlename' => $validated['mother_middlename'] ?? null,
            'children' => $validated['children'] ?? [],
            'education' => $validated['education'] ?? [],
        ];
    }

    private function c2Data(array $validated): array
    {
        return [
            'eligibility' => $validated['eligibility'] ?? [],
            'work_experience' => $validated['work_experience'] ?? [],
        ];
    }

    private function c3Data(array $validated): array
    {
        return [
            'voluntary_work' => $validated['voluntary_work'] ?? [],
            'learning_development' => $validated['learning_development'] ?? [],
        ];
    }

    private function c4Data(array $validated): array
    {
        return [
            'special_skills' => $validated['special_skills'] ?? [],
            'recognitions' => $validated['recognitions'] ?? [],
            'memberships' => $validated['memberships'] ?? [],
            'q34' => $validated['q34'] ?? null,
            'q34_details' => $validated['q34_details'] ?? null,
            'q35' => $validated['q35'] ?? null,
            'q35_details' => $validated['q35_details'] ?? null,
            'q36' => $validated['q36'] ?? null,
            'q36_details' => $validated['q36_details'] ?? null,
            'q37' => $validated['q37'] ?? null,
            'q37_details' => $validated['q37_details'] ?? null,
            'q38a' => $validated['q38a'] ?? null,
            'q38a_details' => $validated['q38a_details'] ?? null,
            'q38b' => $validated['q38b'] ?? null,
            'q38b_details' => $validated['q38b_details'] ?? null,
            'q39' => $validated['q39'] ?? null,
            'q39_details' => $validated['q39_details'] ?? null,
            'q40a' => $validated['q40a'] ?? null,
            'q40a_details' => $validated['q40a_details'] ?? null,
            'q40b' => $validated['q40b'] ?? null,
            'q40b_details' => $validated['q40b_details'] ?? null,
            'q41' => $validated['q41'] ?? null,
            'q41_details' => $validated['q41_details'] ?? null,
            'references' => $validated['references'] ?? [],
            'govt_id_type' => $validated['govt_id_type'] ?? null,
            'govt_id_no' => $validated['govt_id_no'] ?? null,
            'id_issue_date' => $validated['id_issue_date'] ?? null,
            'id_issue_place' => $validated['id_issue_place'] ?? null,
            'signature_date' => $validated['signature_date'] ?? null,
        ];
    }
}
