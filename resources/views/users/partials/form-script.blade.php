{{--
  Perilaku formulir pengguna: sumber klinik (pegawai vs manual), tampil/sembunyikan
  kata sandi, aturan kata sandi, dan penghitung pilihan.
--}}
<script>
    (function () {
        var form = document.getElementById('user-form');
        if (!form) {
            return;
        }

        var employeeSelect = document.getElementById('employee_id');
        var clinicInputs = Array.prototype.slice.call(form.querySelectorAll('input[name="clinic_ids[]"]'));
        var clinicCards = Array.prototype.slice.call(form.querySelectorAll('[data-clinic-card]'));
        var defaultClinic = document.getElementById('default_clinic_id');
        var sourceNote = document.getElementById('clinic-source-note');
        var manualNote = document.getElementById('clinic-manual-note');
        var bulkWrap = form.querySelector('[data-clinic-bulk]');
        var clinicCount = form.querySelector('[data-clinic-count]');
        var roleCount = form.querySelector('[data-role-count]');
        var scopeCount = form.querySelector('[data-scope-count]');

        // Pilihan manual disimpan agar tidak hilang saat pegawai dipilih lalu dilepas.
        var manualSelection = {};
        clinicInputs.forEach(function (input) {
            manualSelection[input.value] = input.checked;
        });

        var derivedMode = false;

        function selectedEmployeeOption() {
            if (!employeeSelect || employeeSelect.value === '') {
                return null;
            }

            return employeeSelect.options[employeeSelect.selectedIndex] || null;
        }

        function setCardDisabled(card, off) {
            card.classList.toggle('sim-check-card-off', off);

            if (off) {
                card.setAttribute('aria-disabled', 'true');
            } else {
                card.removeAttribute('aria-disabled');
            }
        }

        function updateClinicCount() {
            if (!clinicCount) {
                return;
            }

            var total = clinicInputs.filter(function (input) {
                return input.checked;
            }).length;

            clinicCount.textContent = total + ' klinik dipilih';
        }

        function applyClinicSource() {
            var option = selectedEmployeeOption();
            derivedMode = option !== null;

            if (derivedMode) {
                clinicInputs.forEach(function (input) {
                    manualSelection[input.value] = input.checked;
                });

                var derivedIds = (option.getAttribute('data-clinics') || '').split(',').filter(Boolean);

                clinicInputs.forEach(function (input) {
                    input.checked = derivedIds.indexOf(input.value) !== -1;
                    input.disabled = true;
                });

                clinicCards.forEach(function (card) {
                    setCardDisabled(card, true);
                });

                if (defaultClinic) {
                    defaultClinic.value = option.getAttribute('data-clinic') || '';
                    defaultClinic.disabled = true;
                }
            } else {
                clinicInputs.forEach(function (input) {
                    input.disabled = false;
                    input.checked = Object.prototype.hasOwnProperty.call(manualSelection, input.value)
                        ? manualSelection[input.value]
                        : input.checked;
                });

                clinicCards.forEach(function (card) {
                    setCardDisabled(card, false);
                });

                if (defaultClinic) {
                    defaultClinic.disabled = false;
                }
            }

            if (sourceNote) {
                sourceNote.hidden = !derivedMode;
            }

            if (manualNote) {
                manualNote.hidden = derivedMode;
            }

            if (bulkWrap) {
                bulkWrap.hidden = derivedMode;
            }

            updateClinicCount();
        }

        // Hanya mengisi kolom kosong agar nilai lama (old()) tidak tertimpa.
        function fillIdentityFromEmployee() {
            var option = selectedEmployeeOption();

            if (!option) {
                return;
            }

            var nameInput = document.getElementById('name');
            var emailInput = document.getElementById('email');

            if (nameInput && nameInput.value.trim() === '' && option.getAttribute('data-name')) {
                nameInput.value = option.getAttribute('data-name');
            }

            if (emailInput && emailInput.value.trim() === '' && option.getAttribute('data-email')) {
                emailInput.value = option.getAttribute('data-email');
            }
        }

        function updateRoleCount() {
            if (!roleCount) {
                return;
            }

            var total = form.querySelectorAll('input[name="role_ids[]"]:checked').length;
            roleCount.textContent = total + ' peran dipilih';
        }

        function updateScopeCount() {
            if (!scopeCount) {
                return;
            }

            var total = form.querySelectorAll('input[name="scope_names[]"]:checked').length;
            scopeCount.textContent = total + ' scope dipilih';
        }

        function setRule(ruleName, ok) {
            var rule = form.querySelector('[data-rule="' + ruleName + '"]');

            if (!rule) {
                return;
            }

            rule.setAttribute('data-ok', ok ? '1' : '0');

            var mark = rule.querySelector('[data-rule-mark]');

            if (mark) {
                mark.textContent = ok ? '\u2713' : '\u2022';
            }
        }

        function checkPasswordRules() {
            var password = document.getElementById('password');
            var confirmation = document.getElementById('password_confirmation');
            var value = password ? password.value : '';
            var repeat = confirmation ? confirmation.value : '';

            setRule('min8', value.length >= 8);
            setRule('match', value !== '' && repeat !== '' && value === repeat);
        }

        form.querySelectorAll('[data-pw-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.getElementById(button.getAttribute('data-pw-toggle'));

                if (!target) {
                    return;
                }

                var reveal = target.type === 'password';
                target.type = reveal ? 'text' : 'password';

                button.setAttribute('aria-pressed', reveal ? 'true' : 'false');
                button.setAttribute('aria-label', reveal ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');

                var iconShow = button.querySelector('[data-pw-icon="show"]');
                var iconHide = button.querySelector('[data-pw-icon="hide"]');

                if (iconShow) {
                    iconShow.hidden = reveal;
                }

                if (iconHide) {
                    iconHide.hidden = !reveal;
                }
            });
        });

        form.querySelectorAll('[data-clinic-bulk-all]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (derivedMode) {
                    return;
                }

                clinicInputs.forEach(function (input) {
                    input.checked = true;
                    manualSelection[input.value] = true;
                });

                updateClinicCount();
            });
        });

        form.querySelectorAll('[data-clinic-bulk-none]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (derivedMode) {
                    return;
                }

                clinicInputs.forEach(function (input) {
                    input.checked = false;
                    manualSelection[input.value] = false;
                });

                updateClinicCount();
            });
        });

        if (employeeSelect) {
            employeeSelect.addEventListener('change', function () {
                applyClinicSource();
                fillIdentityFromEmployee();
            });
        }

        clinicInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                if (!derivedMode) {
                    manualSelection[input.value] = input.checked;
                }

                updateClinicCount();
            });
        });

        form.addEventListener('change', function (event) {
            var name = event.target.getAttribute('name');

            if (name === 'role_ids[]') {
                updateRoleCount();
            }

            if (name === 'scope_names[]') {
                updateScopeCount();
            }
        });

        ['password', 'password_confirmation'].forEach(function (id) {
            var field = document.getElementById(id);

            if (field) {
                field.addEventListener('input', checkPasswordRules);
            }
        });

        applyClinicSource();
        fillIdentityFromEmployee();
        updateRoleCount();
        updateScopeCount();
        checkPasswordRules();
    })();
</script>
