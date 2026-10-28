<?php

namespace Tests\Unit\Ui;

use App\Support\UiAction;
use Tests\TestCase;

/**
 * Mengunci palet warna aksi agar satu aksi selalu memakai warna yang sama
 * di seluruh aplikasi (lihat App\Support\UiAction dan resources/css/app.css).
 */
class ActionPaletteTest extends TestCase
{
    public function test_canonical_actions_map_to_expected_colors(): void
    {
        $expected = [
            'back' => 'back',
            'create' => 'create',
            'save' => 'save',
            'edit' => 'edit',
            'delete' => 'delete',
            'detail' => 'detail',
            'approve' => 'approve',
            'reject' => 'reject',
            'cancel' => 'neutral',
            'upload' => 'upload',
            'download' => 'download',
            'import' => 'upload',
            'export' => 'download',
            'print' => 'print',
        ];

        foreach ($expected as $action => $color) {
            $this->assertSame(
                'sim-btn sim-btn-'.$color,
                UiAction::classes($action),
                "Aksi '{$action}' seharusnya memakai warna '{$color}'."
            );
        }
    }

    public function test_every_action_color_has_a_css_class(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        foreach (UiAction::ACTIONS as $action => $definition) {
            $this->assertStringContainsString(
                '.sim-btn-'.$definition['color'].' {',
                $css,
                "Kelas .sim-btn-{$definition['color']} untuk aksi '{$action}' belum ada di app.css."
            );
        }
    }

    public function test_size_modifier_is_appended(): void
    {
        $this->assertSame('sim-btn sim-btn-delete sim-btn-sm', UiAction::classes('delete', 'sm'));
        $this->assertSame('sim-btn sim-btn-delete sim-btn-icon', UiAction::classes('delete', 'icon'));
    }

    public function test_guess_resolves_action_from_label_before_icon(): void
    {
        // check-circle dipakai "Simpan" dan "Setujui" — teks harus menang.
        $this->assertSame('save', UiAction::guess('check-circle', 'Simpan EP'));
        $this->assertSame('approve', UiAction::guess('check-circle', 'Setujui'));
        $this->assertSame('back', UiAction::guess('chevron-left', 'Kembali'));
        $this->assertSame('edit', UiAction::guess('pencil', 'Edit'));
        $this->assertSame('delete', UiAction::guess('trash', 'Hapus'));
        $this->assertSame('create', UiAction::guess('plus', 'Tambah Standar'));
    }

    public function test_action_component_renders_standard_classes(): void
    {
        $html = $this->blade('<x-action action="save" label="Simpan" />');
        $this->assertStringContainsString('sim-btn sim-btn-save', $html->__toString());

        $html = $this->blade('<x-action action="delete" method="DELETE" href="/x" label="Hapus" />');
        $this->assertStringContainsString('sim-btn sim-btn-delete', $html->__toString());

        $html = $this->blade('<x-action action="back" href="/x" label="Kembali" />');
        $this->assertStringContainsString('sim-btn sim-btn-back', $html->__toString());
    }

    public function test_action_component_ignores_legacy_variant_when_action_given(): void
    {
        $html = $this->blade('<x-action action="edit" variant="danger" href="/x" label="Edit" />')
            ->__toString();

        $this->assertStringContainsString('sim-btn sim-btn-edit', $html);
        $this->assertStringNotContainsString('sim-btn-danger', $html);
    }
}
