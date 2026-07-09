<?php

namespace App\Http\Controllers;

use App\Models\OrganizationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = $request->user()->organization_id;

        return view('settings.index', [
            'app_name' => $this->settingValue($orgId, 'app_name', 'SIMMAK PPKP'),
            'accreditation_target' => $this->settingValue($orgId, 'accreditation_target', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $orgId = $request->user()->organization_id;

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'accreditation_target' => ['required', 'date'],
        ]);

        $this->upsertSetting($orgId, 'app_name', $validated['app_name'], 'string');
        $this->upsertSetting($orgId, 'accreditation_target', $validated['accreditation_target'], 'date');

        return back()->with('status', 'Pengaturan organisasi berhasil disimpan.');
    }

    private function settingValue(string $orgId, string $key, string $default): string
    {
        return OrganizationSetting::forOrganization($orgId)
            ->where('group', 'general')
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    private function upsertSetting(string $orgId, string $key, string $value, string $valueType): void
    {
        OrganizationSetting::updateOrCreate(
            ['organization_id' => $orgId, 'group' => 'general', 'key' => $key],
            ['value' => $value, 'value_type' => $valueType]
        );
    }
}
