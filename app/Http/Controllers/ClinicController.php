<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClinicController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $clinics = Clinic::forUserClinics($user)
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('clinic.index', compact('clinics'));
    }

    public function create(Request $request): View
    {
        return view('clinic.form', [
            'clinic' => null,
            'clinicTypes' => [
                'pratama' => 'Klinik Pratama',
                'utama' => 'Klinik Utama',
                'pusat' => 'Manajemen Pusat',
            ],
            'accreditationTypes' => ['FKTP', 'FKTL'],
            'accreditationStatuses' => ['not_started', 'in_progress', 'akreditasi'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80', Rule::unique('clinics', 'code')->where('organization_id', $orgId)],
            'name' => ['required', 'string', 'max:255'],
            'clinic_type' => ['required', 'string', 'max:50', Rule::in(['pratama', 'utama', 'pusat'])],
            'is_main_clinic' => ['nullable', 'boolean'],
            'accreditation_type' => ['nullable', 'string', 'max:20'],
            'accreditation_status' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Clinic::create([
            'organization_id' => $orgId,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'clinic_type' => $validated['clinic_type'],
            'is_main_clinic' => (bool) ($validated['is_main_clinic'] ?? false),
            'accreditation_type' => $validated['accreditation_type'] ?? null,
            'accreditation_status' => $validated['accreditation_status'] ?? 'not_started',
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('clinics.index')->with('status', 'Klinik berhasil ditambahkan.');
    }

    public function edit(Request $request, Clinic $clinic): View
    {
        abort_unless($request->user()->belongsToOrganization($clinic->organization_id), 403);

        return view('clinic.form', [
            'clinic' => $clinic,
            'clinicTypes' => [
                'pratama' => 'Klinik Pratama',
                'utama' => 'Klinik Utama',
                'pusat' => 'Manajemen Pusat',
            ],
            'accreditationTypes' => ['FKTP', 'FKTL'],
            'accreditationStatuses' => ['not_started', 'in_progress', 'akreditasi'],
        ]);
    }

    public function update(Request $request, Clinic $clinic): RedirectResponse
    {
        $orgId = $request->user()->organization_id;
        abort_unless($request->user()->belongsToOrganization($clinic->organization_id), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80', Rule::unique('clinics', 'code')->where('organization_id', $clinic->organization_id)->ignore($clinic->id)],
            'name' => ['required', 'string', 'max:255'],
            'clinic_type' => ['required', 'string', 'max:50', Rule::in(['pratama', 'utama', 'pusat'])],
            'is_main_clinic' => ['nullable', 'boolean'],
            'accreditation_type' => ['nullable', 'string', 'max:20'],
            'accreditation_status' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $clinic->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'clinic_type' => $validated['clinic_type'],
            'is_main_clinic' => (bool) ($validated['is_main_clinic'] ?? false),
            'accreditation_type' => $validated['accreditation_type'] ?? null,
            'accreditation_status' => $validated['accreditation_status'] ?? 'not_started',
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('clinics.index')->with('status', 'Klinik berhasil diperbarui.');
    }

    public function switch(Request $request, Clinic $clinic): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->belongsToOrganization($clinic->organization_id), 403);
        abort_unless($user->hasClinicAccess($clinic->id), 403);

        $request->session()->put('active_clinic_id', $clinic->id);

        return back()->with('status', 'Klinik aktif diubah ke '.$clinic->name);
    }

    /**
     * Kembalikan administrator sistem ke mode lintas klinik ("Semua Klinik").
     */
    public function switchAll(Request $request): RedirectResponse
    {
        abort_unless($request->user()->viewsAllClinics(), 403);

        $request->session()->forget('active_clinic_id');

        return back()->with('status', 'Konteks diubah ke Semua Klinik.');
    }

    public function destroy(Request $request, Clinic $clinic): RedirectResponse
    {
        abort_unless($request->user()->belongsToOrganization($clinic->organization_id), 403);

        $clinic->delete();

        return redirect()->route('clinics.index')->with('status', 'Klinik berhasil dihapus.');
    }
}
