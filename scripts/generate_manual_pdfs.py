#!/usr/bin/env python3
"""Generate SIMMAK PPKP user-manual PDFs from docs/manual/*.md"""

from __future__ import annotations

import re
from pathlib import Path

from fpdf import FPDF

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "docs" / "manual"
OUT = ROOT / "pdf" / "manual"

# Prefer Windows fonts with Indonesian/Latin support
FONT_CANDIDATES = [
    Path(r"C:\Windows\Fonts\arial.ttf"),
    Path(r"C:\Windows\Fonts\calibri.ttf"),
    Path(r"C:\Windows\Fonts\segoeui.ttf"),
]
FONT_BOLD_CANDIDATES = [
    Path(r"C:\Windows\Fonts\arialbd.ttf"),
    Path(r"C:\Windows\Fonts\calibrib.ttf"),
    Path(r"C:\Windows\Fonts\segoeuib.ttf"),
]

MANUALS = [
    ("01_ROLE_DAN_AKSES.md", "01_Role_dan_Akses.pdf", "Role, Permission & Matriks Akses"),
    ("02_LOGIN.md", "02_Login_dan_Autentikasi.pdf", "Login & Autentikasi"),
    ("03_DASHBOARD_EKSEKUTIF.md", "03_Dashboard_Eksekutif.pdf", "Dashboard Eksekutif"),
    ("04_DASHBOARD_KLINIK.md", "04_Dashboard_Klinik.pdf", "Dashboard Klinik"),
    ("05_DOKUMEN_DMS.md", "05_Dokumen_DMS.pdf", "Dokumen (DMS)"),
    ("06_AUDIT_INTERNAL.md", "06_Audit_Internal.pdf", "Audit Internal"),
    ("07_TEMUAN_AUDIT.md", "07_Temuan_Audit.pdf", "Temuan Audit"),
    ("08_CAPA.md", "08_CAPA.pdf", "CAPA"),
    ("09_MANAJEMEN_RISIKO.md", "09_Manajemen_Risiko.pdf", "Manajemen Risiko"),
    ("10_PROGRAM_MUTU.md", "10_Program_Mutu.pdf", "Program Mutu"),
    ("11_AKREDITASI.md", "11_Akreditasi.pdf", "Akreditasi FKTP/FKTL"),
    ("12_PELAPORAN.md", "12_Pelaporan.pdf", "Pelaporan"),
    ("13_PENGGUNA_DAN_PENGATURAN.md", "13_Pengguna_dan_Pengaturan.pdf", "Pengguna & Pengaturan"),
]


def pick_font() -> tuple[Path, Path | None]:
    regular = next((p for p in FONT_CANDIDATES if p.exists()), None)
    if not regular:
        raise SystemExit("Font Unicode (Arial/Calibri/Segoe UI) tidak ditemukan di Windows/Fonts.")
    bold = next((p for p in FONT_BOLD_CANDIDATES if p.exists()), None)
    return regular, bold


def strip_md_inline(text: str) -> str:
    text = re.sub(r"\*\*(.+?)\*\*", r"\1", text)
    text = re.sub(r"`(.+?)`", r"\1", text)
    text = re.sub(r"\[(.+?)\]\(.+?\)", r"\1", text)
    return text.strip()


class ManualPDF(FPDF):
    def __init__(self, title: str, font_regular: Path, font_bold: Path | None):
        super().__init__(format="A4")
        self.doc_title = title
        self.set_auto_page_break(auto=True, margin=18)
        self.add_font("Body", "", str(font_regular))
        if font_bold:
            self.add_font("Body", "B", str(font_bold))
        else:
            self.add_font("Body", "B", str(font_regular))

    def header(self) -> None:
        if self.page_no() == 1:
            return
        self.set_font("Body", "B", 8)
        self.set_text_color(15, 118, 110)
        self.cell(0, 6, "SIMMAK PPKP — Panduan Pengguna", new_x="LMARGIN", new_y="NEXT")
        self.set_draw_color(20, 184, 166)
        self.set_line_width(0.3)
        self.line(10, self.get_y(), 200, self.get_y())
        self.ln(4)

    def footer(self) -> None:
        self.set_y(-14)
        self.set_font("Body", "", 8)
        self.set_text_color(100, 116, 139)
        self.cell(0, 8, f"Halaman {self.page_no()} — {self.doc_title}", align="C")

    def cover(self) -> None:
        self.add_page()
        self.set_fill_color(15, 39, 68)
        self.rect(0, 0, 210, 55, "F")
        self.set_xy(14, 18)
        self.set_font("Body", "B", 18)
        self.set_text_color(255, 255, 255)
        self.multi_cell(180, 9, "SIMMAK PPKP")
        self.set_x(14)
        self.set_font("Body", "", 11)
        self.set_text_color(203, 213, 225)
        self.multi_cell(180, 6, "Sistem Manajemen Mutu, Audit Internal & Akreditasi")

        self.set_xy(14, 70)
        self.set_text_color(15, 23, 42)
        self.set_font("Body", "B", 16)
        self.multi_cell(180, 9, self.doc_title)

        self.set_font("Body", "", 11)
        self.set_text_color(71, 85, 105)
        self.ln(4)
        self.set_x(14)
        self.multi_cell(
            180,
            6,
            "Panduan fitur: kegunaan, cara pengisian/penggunaan, dan akses per role.\n"
            "PPKP Provinsi DKI Jakarta — Versi 1.0.0 — Juli 2026",
        )
        self.ln(6)
        self.set_draw_color(20, 184, 166)
        self.set_line_width(0.8)
        self.line(14, self.get_y(), 90, self.get_y())
        self.ln(10)

    def h1(self, text: str) -> None:
        self.set_font("Body", "B", 14)
        self.set_text_color(15, 39, 68)
        self.multi_cell(0, 8, strip_md_inline(text))
        self.ln(2)

    def h2(self, text: str) -> None:
        self.ln(2)
        self.set_font("Body", "B", 12)
        self.set_text_color(13, 148, 136)
        self.multi_cell(0, 7, strip_md_inline(text))
        self.ln(1)

    def h3(self, text: str) -> None:
        self.ln(1)
        self.set_font("Body", "B", 11)
        self.set_text_color(30, 58, 95)
        self.multi_cell(0, 6, strip_md_inline(text))
        self.ln(0.5)

    def para(self, text: str) -> None:
        self.set_font("Body", "", 10)
        self.set_text_color(51, 65, 85)
        self.multi_cell(0, 5.5, strip_md_inline(text))
        self.ln(1.5)

    def bullet(self, text: str) -> None:
        self.set_font("Body", "", 10)
        self.set_text_color(51, 65, 85)
        self.set_x(14)
        self.multi_cell(0, 5.5, f"•  {strip_md_inline(text)}")
        self.ln(0.5)

    def numbered(self, text: str) -> None:
        self.set_font("Body", "", 10)
        self.set_text_color(51, 65, 85)
        self.set_x(14)
        self.multi_cell(0, 5.5, strip_md_inline(text))
        self.ln(0.5)

    def code_block(self, lines: list[str]) -> None:
        self.set_fill_color(241, 245, 249)
        self.set_font("Body", "", 9)
        self.set_text_color(30, 41, 59)
        block = "\n".join(lines)
        self.multi_cell(0, 5, block, fill=True)
        self.ln(2)

    def table(self, rows: list[list[str]]) -> None:
        if not rows:
            return
        col_count = max(len(r) for r in rows)
        usable = 190
        col_w = usable / col_count
        for i, row in enumerate(rows):
            # Ensure row length
            while len(row) < col_count:
                row.append("")
            # Estimate height
            self.set_font("Body", "B" if i == 0 else "", 8)
            heights = []
            for cell in row:
                heights.append(self.get_string_width(strip_md_inline(cell)) / (col_w - 2) * 4.2 + 6)
            row_h = max(6.5, min(22, max(heights)))
            if self.get_y() + row_h > 275:
                self.add_page()
            x0 = self.get_x()
            y0 = self.get_y()
            if i == 0:
                self.set_fill_color(15, 118, 110)
                self.set_text_color(255, 255, 255)
            elif i % 2 == 0:
                self.set_fill_color(248, 250, 252)
                self.set_text_color(51, 65, 85)
            else:
                self.set_fill_color(255, 255, 255)
                self.set_text_color(51, 65, 85)
            for j, cell in enumerate(row):
                x = x0 + j * col_w
                self.set_xy(x, y0)
                self.rect(x, y0, col_w, row_h, "F")
                self.set_xy(x + 1, y0 + 1.2)
                self.multi_cell(col_w - 2, 3.8, strip_md_inline(cell))
            self.set_y(y0 + row_h)
        self.ln(3)


def parse_table_row(line: str) -> list[str] | None:
    if "|" not in line:
        return None
    if re.match(r"^\s*\|?\s*:?-{2,}", line):
        return None
    cells = [c.strip() for c in line.strip().strip("|").split("|")]
    return cells


def render_markdown(pdf: ManualPDF, content: str) -> None:
    lines = content.splitlines()
    i = 0
    # Skip first H1 (used as cover title)
    while i < len(lines) and not lines[i].startswith("# "):
        i += 1
    if i < len(lines) and lines[i].startswith("# "):
        i += 1

    in_code = False
    code_buf: list[str] = []
    table_buf: list[list[str]] = []

    def flush_table() -> None:
        nonlocal table_buf
        if table_buf:
            pdf.table(table_buf)
            table_buf = []

    while i < len(lines):
        line = lines[i]
        if line.startswith("```"):
            flush_table()
            if in_code:
                pdf.code_block(code_buf)
                code_buf = []
                in_code = False
            else:
                in_code = True
            i += 1
            continue
        if in_code:
            code_buf.append(line)
            i += 1
            continue

        if line.startswith("|"):
            row = parse_table_row(line)
            if row is not None:
                table_buf.append(row)
            i += 1
            continue
        else:
            flush_table()

        if not line.strip():
            i += 1
            continue
        if line.startswith("### "):
            pdf.h3(line[4:])
        elif line.startswith("## "):
            pdf.h2(line[3:])
        elif line.startswith("# "):
            pdf.h1(line[2:])
        elif re.match(r"^\d+\.\s+", line.strip()):
            pdf.numbered(line.strip())
        elif line.strip().startswith("- "):
            pdf.bullet(line.strip()[2:])
        else:
            pdf.para(line)
        i += 1

    flush_table()


def build_index_pdf(font_regular: Path, font_bold: Path | None) -> None:
    pdf = ManualPDF("Indeks Panduan Pengguna", font_regular, font_bold)
    pdf.cover()
    pdf.h2("Daftar Dokumen PDF")
    rows = [["No", "Judul", "File PDF"]]
    for idx, (_, filename, title) in enumerate(MANUALS, start=1):
        rows.append([str(idx).zfill(2), title, filename])
    pdf.table(rows)
    pdf.h2("Akun Demo")
    pdf.para("Password semua akun berasal dari environment DEMO_ACCOUNT_PASSWORD (tidak ditulis di codebase). Jika kosong, seeder membuat password acak dan mencetaknya sekali ke console.")
    pdf.table(
        [
            ["Email", "Role"],
            ["admin@simmak.ppkp.local", "super-admin"],
            ["mutu@simmak.ppkp.local", "admin-mutu"],
            ["kepala@simmak.ppkp.local", "kepala-klinik"],
            ["pjmutu@simmak.ppkp.local", "pj-mutu"],
            ["akreditasi@simmak.ppkp.local", "tim-akreditasi"],
            ["auditor@simmak.ppkp.local", "auditor"],
            ["staff@simmak.ppkp.local", "staff"],
        ]
    )
    pdf.h2("Cara Generate Ulang")
    pdf.code_block(["python scripts/generate_manual_pdfs.py"])
    out = OUT / "00_Indeks_Panduan_Pengguna.pdf"
    pdf.output(str(out))
    print(f"OK  {out.name}")


def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    font_regular, font_bold = pick_font()
    build_index_pdf(font_regular, font_bold)

    for md_name, pdf_name, title in MANUALS:
        md_path = SRC / md_name
        if not md_path.exists():
            print(f"SKIP missing {md_name}")
            continue
        content = md_path.read_text(encoding="utf-8")
        # Prefer H1 from file if present
        m = re.search(r"^#\s+(.+)$", content, re.M)
        doc_title = m.group(1).strip() if m else title
        pdf = ManualPDF(doc_title, font_regular, font_bold)
        pdf.cover()
        # Replace cover title already set; start content on same page after cover block
        render_markdown(pdf, content)
        out = OUT / pdf_name
        pdf.output(str(out))
        print(f"OK  {pdf_name}")

    print(f"\nSelesai. Output: {OUT}")


if __name__ == "__main__":
    main()
