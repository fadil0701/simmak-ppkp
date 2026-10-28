{{--
  Badan formulir pengguna — dipakai bersama oleh users/create dan users/edit.

  Variabel wajib:
    $action, $method, $submitLabel, $cancelUrl
    $employees, $clinics, $roles, $scopes
  Variabel opsional (mode ubah):
    $user, $currentRoles, $currentScopes, $scopedClinicIds, $derivedClinicIds
    $prefilledEmployeeId (mode tambah, dari ?employee_id=)
--}}
@php
    $user = $user ?? null;
    $isEdit = $user !== null;

    $katalogPeran = \App\Support\SimmakRoles::assignableNames();

    $peranTerpilih = collect(old('role_ids', $currentRoles ?? []))->all();
    $scopeTerpilih = collect(old('scope_names', $currentScopes ?? []))->all();
    $klinikTerpilih = collect(old('clinic_ids', $scopedClinicIds ?? []))->all();
    $pegawaiTerpilih = old('employee_id', $isEdit ? $user->employee_id : ($prefilledEmployeeId ?? null));
    $klinikDefaultTerpilih = old('default_clinic_id', $isEdit ? $user->default_clinic_id : null);
    $akunAktif = old('is_active', $isEdit ? $user->is_active : true);

    $peranResmi = $roles->filter(fn ($role) => in_array($role->name, $katalogPeran, true));
    $peranLama = $roles->filter(fn ($role) => ! in_array($role->name, $katalogPeran, true));

    $scopeTerpilihCount = count($scopeTerpilih);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-0" id="user-form">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="sim-alert sim-alert-error mb-5" role="alert" aria-live="polite">
            <div class="flex gap-2">
                <span class="sim-alert-icon" aria-hidden="true">!</span>
                <div class="min-w-0">
                    <p class="font-semibold">
                        Formulir belum dapat disimpan — {{ $errors->count() }} hal perlu diperbaiki.
                    </p>
                    <ul class="mt-2 list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $pesan)
                            <li>{{ $pesan }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <x-form-section step="1" title="Data Dasar"
                    description="Pilih pegawai agar nama, surel, dan akses klinik terisi otomatis. Kolom bertanda * wajib diisi.">
        <div class="sim-form-group">
            <label class="sim-form-label" for="employee_id">Pegawai (master)</label>
            <select id="employee_id" name="employee_id"
                    class="sim-form-select {{ $errors->has('employee_id') ? 'border-sim-danger' : '' }}">
                <option value="">— Akun teknis tanpa data pegawai —</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}"
                            data-name="{{ $employee->full_name }}"
                            data-email="{{ $employee->email }}"
                            data-clinic="{{ $employee->clinic_id }}"
                            data-clinics="{{ implode(',', $employee->clinicScopeIds()) }}"
                            @selected($pegawaiTerpilih === $employee->id)>
                        {{ $employee->employee_number }} — {{ $employee->full_name }} ({{ $employee->position ?? 'Tanpa jabatan' }})
                    </option>
                @endforeach
            </select>
            @error('employee_id')<p class="sim-form-error">{{ $message }}</p>@enderror
            <p class="sim-form-hint">
                Nama, surel, dan klinik akan mengikuti master pegawai.
                <a href="{{ route('employees.create') }}" class="text-sim-primary hover:underline" class="sim-btn sim-btn-create">Tambah data pegawai baru</a>.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sim-form-group">
                <label class="sim-form-label" for="name">Nama Lengkap <span class="sim-form-label-required"></span></label>
                <input type="text" id="name" name="name" required autocomplete="name"
                       value="{{ old('name', $user?->name) }}"
                       class="sim-form-input {{ $errors->has('name') ? 'border-sim-danger' : '' }}"
                       @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                @error('name')<p class="sim-form-error" id="name-error">{{ $message }}</p>@enderror
            </div>

            <div class="sim-form-group">
                <label class="sim-form-label" for="username">Username <span class="sim-form-label-required"></span></label>
                <input type="text" id="username" name="username" required autocomplete="username"
                       value="{{ old('username', $user?->username) }}"
                       class="sim-form-input {{ $errors->has('username') ? 'border-sim-danger' : '' }}"
                       @error('username') aria-invalid="true" aria-describedby="username-error" @enderror>
                @error('username')<p class="sim-form-error" id="username-error">{{ $message }}</p>@enderror
                <p class="sim-form-hint">Dipakai untuk masuk bila login memakai username.</p>
            </div>
        </div>

        <div class="sim-form-group">
            <label class="sim-form-label" for="email">Surel <span class="sim-form-label-required"></span></label>
            <input type="email" id="email" name="email" required autocomplete="email"
                   value="{{ old('email', $user?->email) }}"
                   class="sim-form-input {{ $errors->has('email') ? 'border-sim-danger' : '' }}"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p class="sim-form-error" id="email-error">{{ $message }}</p>@enderror
        </div>
    </x-form-section>

    <x-form-section step="2" title="Kredensial Masuk"
                    :description="$isEdit
                        ? 'Biarkan kosong bila kata sandi tidak diubah. Aturan saat ini: minimal 8 karakter dan konfirmasi harus sama.'
                        : 'Aturan saat ini: minimal 8 karakter dan konfirmasi harus sama.'">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sim-form-group">
                <label class="sim-form-label" for="password">
                    {{ $isEdit ? 'Kata Sandi Baru' : 'Kata Sandi' }}
                    @unless ($isEdit)<span class="sim-form-label-required"></span>@endunless
                </label>
                <div class="sim-input-affix">
                    <input type="password" id="password" name="password" autocomplete="new-password"
                           @unless ($isEdit) required @endunless
                           class="sim-form-input {{ $errors->has('password') ? 'border-sim-danger' : '' }}"
                           aria-describedby="password-rules"
                           @error('password') aria-invalid="true" @enderror>
                    <button type="button" class="sim-input-affix-btn" data-pw-toggle="password"
                            aria-controls="password" aria-pressed="false" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
                        <span class="inline-flex" data-pw-icon="show">@include('partials.icons.eye')</span>
                        <span class="inline-flex" data-pw-icon="hide" hidden>@include('partials.icons.x-mark')</span>
                    </button>
                </div>
                @error('password')<p class="sim-form-error">{{ $message }}</p>@enderror
            </div>

            <div class="sim-form-group">
                <label class="sim-form-label" for="password_confirmation">
                    Konfirmasi {{ $isEdit ? 'Kata Sandi Baru' : 'Kata Sandi' }}
                    @unless ($isEdit)<span class="sim-form-label-required"></span>@endunless
                </label>
                <div class="sim-input-affix">
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                           @unless ($isEdit) required @endunless
                           class="sim-form-input {{ $errors->has('password_confirmation') ? 'border-sim-danger' : '' }}"
                           aria-describedby="password-rules">
                    <button type="button" class="sim-input-affix-btn" data-pw-toggle="password_confirmation"
                            aria-controls="password_confirmation" aria-pressed="false" aria-label="Tampilkan konfirmasi kata sandi" title="Tampilkan konfirmasi kata sandi">
                        <span class="inline-flex" data-pw-icon="show">@include('partials.icons.eye')</span>
                        <span class="inline-flex" data-pw-icon="hide" hidden>@include('partials.icons.x-mark')</span>
                    </button>
                </div>
                @error('password_confirmation')<p class="sim-form-error">{{ $message }}</p>@enderror
            </div>
        </div>

        <ul class="sim-rule-list" id="password-rules">
            <li class="sim-rule" data-rule="min8">
                <span aria-hidden="true" data-rule-mark>•</span>
                <span>Minimal 8 karakter</span>
            </li>
            <li class="sim-rule" data-rule="match">
                <span aria-hidden="true" data-rule-mark>•</span>
                <span>Konfirmasi sama dengan kata sandi</span>
            </li>
        </ul>
    </x-form-section>
    <x-form-section step="3" title="Peran &amp; Kewenangan"
                    description="Satu akun boleh memegang lebih dari satu peran. Hak akses efektif adalah gabungan seluruh peran yang dicentang.">
        <div class="sim-form-group">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="sim-form-label mb-0">
                    Peran <span class="sim-form-label-required"></span>
                </span>
                <span class="sim-badge border-sim-border-light bg-sim-surface-alt font-normal text-sim-muted" data-role-count>
                    {{ count($peranTerpilih) }} peran dipilih
                </span>
            </div>

            <div class="sim-check-grid mt-1.5">
                @foreach ($peranResmi as $role)
                    <x-check-card
                        name="role_ids[]"
                        :value="$role->name"
                        :checked="in_array($role->name, $peranTerpilih, true)"
                        :title="\App\Support\RoleLabel::for($role->name)"
                        :description="\App\Support\SimmakRoles::description($role->name)"
                        :meta="$role->name === \App\Support\SimmakRoles::SUPER_ADMIN ? 'akses penuh' : null"
                        :tone="$role->name === \App\Support\SimmakRoles::SUPER_ADMIN ? 'warning' : 'default'"
                        data-role-card />
                @endforeach
            </div>

            @error('role_ids')<p class="sim-form-error">{{ $message }}</p>@enderror
            @error('role_ids.*')<p class="sim-form-error">{{ $message }}</p>@enderror
        </div>

        @if ($peranLama->isNotEmpty())
            <div class="sim-form-group">
                <span class="sim-form-label">Peran lama pada akun ini</span>
                <div class="sim-check-grid">
                    @foreach ($peranLama as $role)
                        <x-check-card
                            name="role_ids[]"
                            :value="$role->name"
                            :checked="in_array($role->name, $peranTerpilih, true)"
                            :title="\App\Support\RoleLabel::for($role->name)"
                            :description="\App\Support\SimmakRoles::description($role->name) ?? 'Peran ini tidak lagi diberikan ke akun baru; fungsinya dipindahkan ke Scope Sistem.'"
                            meta="peran lama"
                            tone="warning"
                            data-role-card />
                    @endforeach
                </div>
                <p class="sim-form-hint">
                    Peran lama tidak dapat ditambahkan ke akun lain dan tidak dapat dipilih ulang
                    setelah dilepas. Gantikan dengan peran resmi atau Scope Sistem di bawah.
                </p>
            </div>
        @endif

        <div class="sim-form-group">
            <label class="sim-form-label" for="default_clinic_id">Klinik Default</label>
            <select id="default_clinic_id" name="default_clinic_id"
                    class="sim-form-select {{ $errors->has('default_clinic_id') ? 'border-sim-danger' : '' }}">
                <option value="">— Tidak ada (lintas klinik) —</option>
                @foreach ($clinics as $clinic)
                    <option value="{{ $clinic->id }}" @selected((string) $klinikDefaultTerpilih === (string) $clinic->id)>
                        {{ $clinic->name }} ({{ $clinic->code }})
                    </option>
                @endforeach
            </select>
            @error('default_clinic_id')<p class="sim-form-error">{{ $message }}</p>@enderror
            <p class="sim-form-hint">Konteks klinik yang aktif saat pengguna masuk.</p>
        </div>
    </x-form-section>

    <x-form-section step="4" title="Akses Klinik"
                    description="Akun hanya dapat menyentuh data klinik yang dicentang di sini.">
        <div class="sim-form-group">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="sim-form-label mb-0">Klinik yang dapat diakses</span>
                <span class="flex items-center gap-2">
                    <span class="sim-badge border-sim-border-light bg-sim-surface-alt font-normal text-sim-muted" data-clinic-count>
                        {{ count($klinikTerpilih) }} klinik dipilih
                    </span>
                    <span class="flex items-center gap-1" data-clinic-bulk>
                        <button type="button" class="text-xs text-sim-primary underline-offset-2 hover:underline" data-clinic-bulk-all>Pilih semua</button>
                        <span class="text-xs text-sim-muted" aria-hidden="true">·</span>
                        <button type="button" class="text-xs text-sim-primary underline-offset-2 hover:underline" data-clinic-bulk-none>Kosongkan</button>
                    </span>
                </span>
            </div>

            <div class="sim-form-alert-inline mt-1.5 hidden rounded-lg border border-sim-info/30 bg-sim-info-bg px-3 py-2 text-xs text-sim-info"
                 id="clinic-source-note" hidden>
                Akses klinik mengikuti master pegawai yang dipilih. Daftar di bawah hanya menampilkan
                hasilnya dan tidak dapat diubah dari sini — ubah penugasan klinik pada data pegawai.
            </div>

            <p class="sim-form-hint" id="clinic-manual-note">
                Akun ini tidak terhubung ke pegawai, jadi klinik dipilih manual di bawah.
            </p>

            <div class="sim-check-grid mt-2" id="clinic-options">
                @foreach ($clinics as $clinic)
                    <x-check-card
                        name="clinic_ids[]"
                        :value="$clinic->id"
                        :checked="in_array($clinic->id, $klinikTerpilih, true)"
                        :title="$clinic->name"
                        :description="trim(($clinic->code ?? '').($clinic->city ? ' · '.$clinic->city : '')) ?: null"
                        :meta="\App\Models\Clinic::typeOptions()[$clinic->clinic_type] ?? null"
                        data-clinic-card />
                @endforeach
            </div>

            @error('clinic_ids')<p class="sim-form-error">{{ $message }}</p>@enderror
            @error('clinic_ids.*')<p class="sim-form-error">{{ $message }}</p>@enderror
        </div>
    </x-form-section>

    <x-form-section step="5" title="Scope Sistem (opsional)"
                    description="Scope menambah izin fungsional di luar peran struktural. Scope tidak melewati batas klinik maupun segregasi tugas (menyetujui program, menutup temuan).">
        <div class="sim-form-group">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="sim-form-label mb-0">Scope yang diberikan</span>
                <span class="sim-badge border-sim-border-light bg-sim-surface-alt font-normal text-sim-muted" data-scope-count>
                    {{ $scopeTerpilihCount }} scope dipilih
                </span>
            </div>

            <div class="sim-check-grid mt-1.5">
                @foreach ($scopes as $scope => $label)
                    @php
                        $bagian = preg_split('/\s+—\s+/u', $label, 2);
                        $judulScope = $bagian[0] ?? $label;
                        $ketScope = $bagian[1] ?? null;
                    @endphp

                    <x-check-card
                        name="scope_names[]"
                        :value="$scope"
                        :checked="in_array($scope, $scopeTerpilih, true)"
                        :title="$judulScope"
                        :description="$ketScope"
                        :meta="count(\App\Support\SimmakScopes::permissionsFor($scope)).' izin'" />
                @endforeach
            </div>

            @error('scope_names')<p class="sim-form-error">{{ $message }}</p>@enderror
            @error('scope_names.*')<p class="sim-form-error">{{ $message }}</p>@enderror
        </div>
    </x-form-section>

    <x-form-section step="6" title="Status Akun">
        <input type="hidden" name="is_active" value="0">
        <x-check-card
            name="is_active"
            value="1"
            :checked="(bool) $akunAktif"
            title="Akun aktif"
            description="Akun nonaktif tidak dapat masuk, tetapi seluruh riwayat dan penugasannya tetap tersimpan." />
    </x-form-section>

    <div class="sim-form-actions">
        <p class="text-xs text-sim-muted">
            <span class="sim-form-label-required"></span> wajib diisi.
        </p>
        <div class="flex flex-wrap justify-end gap-3">
            <x-action action="cancel" :href="$cancelUrl" icon="chevron-left" label="Batal" />
            <x-action action="save" icon="check-circle" :label="$submitLabel" />
        </div>
    </div>
</form>

@include('users.partials.form-script')



