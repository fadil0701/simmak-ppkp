@php
    $savedDocuments = $program?->documents ?? collect();
@endphp

<div class="rounded-lg border border-sim-border p-3 space-y-3">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h4 class="text-sm font-semibold text-sim-ink">Dokumen Acuan Program Audit</h4>
            <p class="text-xs text-sim-muted">Judul dan berkas wajib untuk setiap dokumen baru. Keterangan opsional. Maksimal 10 MB (PDF, gambar, atau Office).</p>
        </div>
        <button type="button" id="add-program-document" class="sim-btn sim-btn-create sim-btn-sm">
            Tambah Dokumen (+)
        </button>
    </div>

    <div id="program-documents" class="space-y-3">
        @foreach ($savedDocuments as $index => $document)
            <div class="rounded-lg border border-sim-border-light bg-sim-surface-alt p-3 space-y-2" data-doc-row data-saved="1">
                <input type="hidden" name="documents[{{ $index }}][id]" value="{{ $document->id }}">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium text-sim-muted">Dokumen tersimpan</span>
                </div>
                <div class="sim-form-group">
                    <label class="sim-form-label">Judul Dokumen <span class="sim-form-label-required"></span></label>
                    <input type="text" name="documents[{{ $index }}][title]" maxlength="255" required
                           value="{{ old('documents.'.$index.'.title', $document->title) }}"
                           class="sim-form-input {{ $errors->has('documents.'.$index.'.title') ? 'border-sim-danger' : '' }}">
                    @error('documents.'.$index.'.title')<p class="sim-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="sim-form-group">
                    <label class="sim-form-label">File Dokumen</label>
                    <p class="mb-1 text-xs text-sim-muted">Saat ini: {{ $document->original_filename }}. Kosongkan jika tidak diganti.</p>
                    <input type="file" name="documents[{{ $index }}][file]"
                           class="sim-form-input {{ $errors->has('documents.'.$index.'.file') ? 'border-sim-danger' : '' }}"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.txt">
                    @error('documents.'.$index.'.file')<p class="sim-form-error">{{ $message }}</p>@enderror
                </div>
                <div class="sim-form-group">
                    <label class="sim-form-label">Keterangan</label>
                    <textarea name="documents[{{ $index }}][description]" rows="2" maxlength="500"
                              class="sim-form-textarea">{{ old('documents.'.$index.'.description', $document->description) }}</textarea>
                </div>
            </div>
        @endforeach
    </div>
</div>

<template id="program-document-row-template">
    <div class="rounded-lg border border-sim-border-light p-3 space-y-2" data-doc-row>
        <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-medium text-sim-muted">Dokumen baru</span>
            <button type="button" class="text-xs font-medium text-sim-danger hover:underline" data-remove-doc>Hapus baris</button>
        </div>
        <div class="sim-form-group">
            <label class="sim-form-label">Judul Dokumen <span class="sim-form-label-required"></span></label>
            <input type="text" data-name="title" maxlength="255" required class="sim-form-input" placeholder="Judul dokumen acuan">
        </div>
        <div class="sim-form-group">
            <label class="sim-form-label">File Dokumen <span class="sim-form-label-required"></span></label>
            <input type="file" data-name="file" required class="sim-form-input"
                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.txt">
        </div>
        <div class="sim-form-group">
            <label class="sim-form-label">Keterangan</label>
            <textarea data-name="description" rows="2" maxlength="500" class="sim-form-textarea" placeholder="Keterangan (opsional)"></textarea>
        </div>
    </div>
</template>

<script>
    (function () {
        var wrap = document.getElementById('program-documents');
        var template = document.getElementById('program-document-row-template');
        var addBtn = document.getElementById('add-program-document');
        if (!wrap || !template || !addBtn) {
            return;
        }

        var nextIndex = wrap.querySelectorAll('[data-doc-row]').length;

        var bindNames = function (row, index) {
            row.querySelectorAll('[data-name]').forEach(function (input) {
                input.setAttribute('name', 'documents[' + index + '][' + input.getAttribute('data-name') + ']');
            });
        };

        addBtn.addEventListener('click', function () {
            var node = template.content.cloneNode(true);
            var row = node.querySelector('[data-doc-row]');
            bindNames(row, nextIndex);
            nextIndex += 1;
            wrap.appendChild(node);
        });

        wrap.addEventListener('click', function (event) {
            var button = event.target.closest('[data-remove-doc]');
            if (!button) {
                return;
            }
            var row = button.closest('[data-doc-row]');
            if (row && row.getAttribute('data-saved') !== '1') {
                row.remove();
            }
        });
    })();
</script>
