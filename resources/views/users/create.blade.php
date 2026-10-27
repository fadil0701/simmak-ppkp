@extends('layouts.simmak')

@section('title', 'Tambah Pengguna — SIMMAK PPKP')
@section('page-title', 'Tambah Pengguna')
@section('page-subtitle', 'Buat akun pengguna lokal, tetapkan peran, dan atur akses kliniknya.')

@section('breadcrumb')
    <li class="flex items-center gap-1 text-sim-muted">
        <a href="{{ route('users.index') }}">Pengguna</a>
        <svg class="h-3.5 w-3.5 text-sim-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        <span>Tambah</span>
    </li>
@endsection

@section('content')
    <div class="sim-card max-w-3xl">
        <div class="sim-card-header">
            <h3 class="sim-card-title">Data Pengguna</h3>
        </div>
        <div class="sim-card-body">
            @if ($errors->any())
                <div class="sim-alert sim-alert-error mb-5" role="alert">
                    <span class="sim-alert-icon">!</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('users.store') }}" class="space-y-5">
                @csrf

                <div class="sim-form-group" x-data="{ applyEmployee() {
                    const select = document.getElementById('employee_id');
                    if (!select || !select.value) return;
                    const option = select.options[select.selectedIndex];
                    if (option.dataset.name) { document.getElementById('name').value = option.dataset.name; }
                    if (option.dataset.email) { document.getElementById('email').value = option.dataset.email; }
                    if (option.dataset.clinic) {
                        const clinicSelect = document.getElementById('default_clinic_id');
                        if (clinicSelect && !clinicSelect.value) { clinicSelect.value = option.dataset.clinic; }
                    }
                } }" x-init="applyEmployee()">
                    <label class="sim-form-label" for="employee_id">Pegawai (Master)</label>
                    <select id="employee_id" name="employee_id" @change="applyEmployee()"
                            class="sim-form-select {{ $errors->has('employee_id') ? 'border-sim-danger' : '' }}">
                        <option value="">— Tanpa data pegawai —</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}"
                                    data-name="{{ $employee->full_name }}"
                                    data-email="{{ $employee->email }}"
                                    data-clinic="{{ $employee->clinic_id }}"
                                    @selected(old('employee_id', $prefilledEmployeeId) === $employee->id)>
                                {{ $employee->employee_number }} — {{ $employee->full_name }} ({{ $employee->position ?? 'Tanpa jabatan' }})
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')<p class="sim-form-error">{{ $message }}</p>@enderror
                    <p class="sim-form-hint">
                        Memilih pegawai mengisi nama &amp; surel secara otomatis.
                        <a href="{{ route('employees.create') }}" class="text-sim-primary hover:underline">Tambah data pegawai baru</a>.
                    </p>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label" for="name">Nama Lengkap <span class="sim-form-label-required"></span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="sim-form-input {{ $errors->has('name') ? 'border-sim-danger' : '' }}">
                    @error('name')<p class="sim-form-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="username">Username <span class="sim-form-label-required"></span></label>
                        <input type="text" id="username" name="username" value="{{ old('username') }}" required
                               class="sim-form-input {{ $errors->has('username') ? 'border-sim-danger' : '' }}">
                        @error('username')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="email">Surel <span class="sim-form-label-required"></span></label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                               class="sim-form-input {{ $errors->has('email') ? 'border-sim-danger' : '' }}">
                        @error('email')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="password">Kata Sandi <span class="sim-form-label-required"></span></label>
                        <input type="password" id="password" name="password" required autocomplete="new-password"
                               class="sim-form-input {{ $errors->has('password') ? 'border-sim-danger' : '' }}">
                        @error('password')<p class="sim-form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="password_confirmation">Konfirmasi Kata Sandi <span class="sim-form-label-required"></span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                               class="sim-form-input">
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sim-form-group">
                        <span class="sim-form-label">Peran <span class="sim-form-label-required"></span></span>
                        <div class="grid gap-2 rounded-lg border border-sim-border bg-sim-surface-alt p-3">
                            @foreach ($roles as $role)
                                <label class="flex items-center gap-2 text-sm text-sim-ink">
                                    <input type="checkbox" name="role_ids[]" value="{{ $role->name }}"
                                           @checked(collect(old('role_ids', []))->contains($role->name))
                                           class="h-4 w-4 rounded border-sim-border text-sim-primary focus:ring-sim-primary">
                                    <span>
                                        {{ \App\Support\RoleLabel::for($role->name) }}
                                        <span class="text-sim-text">({{ $role->name }})</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('role_ids')<p class="sim-form-error">{{ $message }}</p>@enderror
                        @error('role_ids.*')<p class="sim-form-error">{{ $message }}</p>@enderror
                        <p class="sim-form-hint">
                            Satu akun boleh memegang lebih dari satu peran; hak akses efektif adalah
                            gabungan seluruh peran. Jabatan di master pegawai tidak mengubah peran ini.
                        </p>
                    </div>
                    <div class="sim-form-group">
                        <label class="sim-form-label" for="default_clinic_id">Klinik Default</label>
                        <select id="default_clinic_id" name="default_clinic_id" class="sim-form-select">
                            <option value="">— Tidak ada (lintas klinik) —</option>
                            @foreach ($clinics as $clinic)
                                <option value="{{ $clinic->id }}" @selected(old('default_clinic_id') == $clinic->id)>
                                    {{ $clinic->name }} ({{ $clinic->code }})
                                </option>
                            @endforeach
                        </select>
                        <p class="sim-form-hint">Konteks klinik yang dipakai saat pengguna masuk.</p>
                    </div>
                </div>

                <div class="sim-form-group">
                    <label class="sim-form-label">Akses Klinik</label>
                    <div class="grid gap-2 rounded-lg border border-sim-border bg-sim-surface-alt p-3 sm:grid-cols-2">
                        @foreach ($clinics as $clinic)
                            <label class="flex items-center gap-2 text-sm text-sim-ink">
                                <input type="checkbox" name="clinic_ids[]" value="{{ $clinic->id }}"
                                       @checked(collect(old('clinic_ids', []))->contains($clinic->id))
                                       class="h-4 w-4 rounded border-sim-border text-sim-primary focus:ring-sim-primary">
                                <span>{{ $clinic->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p id="clinic-source-note" class="sim-form-hint" hidden>
                        Akses klinik mengikuti master pegawai. Semua klinik yang dipegang pegawai
                        diberikan otomatis, dan klinik default mengikuti klinik utama pegawai.
                    </p>
                    <p id="clinic-manual-note" class="sim-form-hint">
                        Akses klinik mengikuti master pegawai. Pilih data pegawai terlebih dahulu;
                        bila akun tanpa pegawai (akun teknis), pilih klinik secara manual.
                    </p>
                </div>

                <div class="sim-form-group">
                    <span class="sim-form-label">Scope Sistem</span>
                    <div class="grid gap-2 rounded-lg border border-sim-border bg-sim-surface-alt p-3">
                        @foreach ($scopes as $scope => $label)
                            <label class="flex items-start gap-2 text-sm text-sim-ink">
                                <input type="checkbox" name="scope_names[]" value="{{ $scope }}"
                                       @checked(collect(old('scope_names', []))->contains($scope))
                                       class="mt-0.5 h-4 w-4 rounded border-sim-border text-sim-primary focus:ring-sim-primary">
                                <span>
                                    {{ $label }}
                                    <span class="block text-xs text-sim-text">{{ $scope }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('scope_names')<p class="sim-form-error">{{ $message }}</p>@enderror
                    @error('scope_names.*')<p class="sim-form-error">{{ $message }}</p>@enderror
                    <p class="sim-form-hint">
                        Scope menambah izin fungsional di luar peran struktural. Scope tidak melewati
                        batas klinik maupun segregasi tugas (menyetujui program, menutup temuan).
                    </p>
                </div>

                <div class="sim-form-group">
                    <label class="flex items-center gap-2 text-sm font-medium text-sim-ink">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                               class="h-4 w-4 rounded border-sim-border text-sim-primary focus:ring-sim-primary">
                        Akun aktif
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t border-sim-border-light pt-4">
                    <x-action href="{{ route('users.index') }}" icon="chevron-left" label="Batal" variant="outline" />
                    <x-action icon="check-circle" label="Simpan Pengguna" />
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const employeeSelect = document.getElementById('employee_id');
            if (!employeeSelect) {
                return;
            }

            const clinicInputs = document.querySelectorAll('input[name="clinic_ids[]"]');
            const defaultClinic = document.getElementById('default_clinic_id');
            const derivedNote = document.getElementById('clinic-source-note');
            const manualNote = document.getElementById('clinic-manual-note');

            const toggleClinicSource = function () {
                const derived = employeeSelect.value !== '';

                clinicInputs.forEach(function (input) {
                    input.disabled = derived;
                });

                if (defaultClinic) {
                    defaultClinic.disabled = derived;
                }

                if (derivedNote) {
                    derivedNote.hidden = !derived;
                }

                if (manualNote) {
                    manualNote.hidden = derived;
                }
            };

            employeeSelect.addEventListener('change', toggleClinicSource);
            toggleClinicSource();
        })();
    </script>
@endsection
