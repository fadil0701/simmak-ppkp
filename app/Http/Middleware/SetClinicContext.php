<?php

namespace App\Http\Middleware;

use App\Models\Clinic;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetClinicContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Administrator sistem bersifat lintas klinik: tidak diikat ke satu
        // klinik. Bila ia memilih klinik tertentu lewat pemilih konteks, pilihan
        // itu dihormati; jika belum memilih, konteks aktif dibiarkan kosong
        // sehingga sistem menampilkan "Semua Klinik".
        if ($user->viewsAllClinics()) {
            $clinicId = $request->session()->get('active_clinic_id');

            if ($clinicId && $user->hasClinicAccess($clinicId)) {
                $request->attributes->set('active_clinic', Clinic::find($clinicId));
            } else {
                $request->session()->forget('active_clinic_id');
            }

            $request->attributes->set('organization', $user->organization);

            return $next($request);
        }

        $clinicId = $request->session()->get('active_clinic_id', $user->default_clinic_id);

        // Manajemen pusat / administrator sistem boleh memakai klinik mana pun
        // yang menjadi bagian dari scope-nya sebagai konteks aktif.
        $allowed = $clinicId ? $user->hasClinicAccess($clinicId) : false;

        if (! $allowed && $user->default_clinic_id && $user->hasClinicAccess($user->default_clinic_id)) {
            $clinicId = $user->default_clinic_id;
            $allowed = true;
        }

        if ($allowed && $clinicId) {
            $request->session()->put('active_clinic_id', $clinicId);
            $request->attributes->set('active_clinic', Clinic::find($clinicId));
        } else {
            $request->session()->forget('active_clinic_id');
        }

        $request->attributes->set('organization', $user->organization);

        return $next($request);
    }
}
