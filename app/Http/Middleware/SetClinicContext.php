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

        $clinicId = $request->session()->get('active_clinic_id', $user->default_clinic_id);

        if ($clinicId && $user->clinics()->where('clinics.id', $clinicId)->exists()) {
            $request->session()->put('active_clinic_id', $clinicId);
            $request->attributes->set('active_clinic', Clinic::find($clinicId));
        } elseif ($user->default_clinic_id) {
            $request->session()->put('active_clinic_id', $user->default_clinic_id);
            $request->attributes->set('active_clinic', $user->defaultClinic);
        }

        $request->attributes->set('organization', $user->organization);

        return $next($request);
    }
}
