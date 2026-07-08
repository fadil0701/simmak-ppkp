#!/usr/bin/env python3
"""Convert SIMMAK PPKP Markdown documents to enterprise PDF."""

import re
import sys
from pathlib import Path

import markdown
from xhtml2pdf import pisa

ROOT = Path(__file__).resolve().parent.parent
DOCS_DIR = ROOT / "docs"
PDF_DIR = ROOT / "pdf"
DIAGRAMS_DIR = ROOT / "diagrams"
MOCKUPS_DIR = ROOT / "mockups"

DOCUMENTS = [
    ("BRD.md", "SIMMAK-PPKP_Vol1_BRD.pdf", "Volume 1 — Business Requirement Document"),
    ("PRD.md", "SIMMAK-PPKP_Vol2_PRD.pdf", "Volume 2 — Product Requirement Document"),
    ("SAD.md", "SIMMAK-PPKP_Vol3_SAD.pdf", "Volume 3 — System Analysis Document"),
    ("DDD.md", "SIMMAK-PPKP_Vol4_DDD.pdf", "Volume 4 — Database Design Document"),
    ("UIUX.md", "SIMMAK-PPKP_Vol5_UIUX.pdf", "Volume 5 — UI/UX Design Document"),
    ("ARCHITECTURE.md", "SIMMAK-PPKP_Vol6_ARCHITECTURE.pdf", "Volume 6 — Technical Architecture"),
]

CSS = """
@page {
    size: A4;
    margin: 2cm 1.8cm 2.2cm 1.8cm;
    @frame footer {
        -pdf-frame-content: footerContent;
        bottom: 0.5cm;
        margin-left: 1.8cm;
        margin-right: 1.8cm;
        height: 1cm;
    }
}
body {
    font-family: Helvetica, Arial, sans-serif;
    font-size: 9.5pt;
    line-height: 1.45;
    color: #1a2332;
}
.cover {
    text-align: center;
    padding-top: 6cm;
    page-break-after: always;
}
.cover h1 {
    font-size: 22pt;
    color: #0d7377;
    margin-bottom: 0.4cm;
}
.cover h2 {
    font-size: 14pt;
    color: #323f4b;
    font-weight: normal;
    margin-bottom: 1.2cm;
}
.cover .meta {
    font-size: 10pt;
    color: #52606d;
    line-height: 1.8;
}
h1 {
    font-size: 16pt;
    color: #0d7377;
    border-bottom: 2px solid #0d7377;
    padding-bottom: 4px;
    margin-top: 18px;
    page-break-before: always;
}
h1:first-of-type { page-break-before: avoid; }
h2 {
    font-size: 13pt;
    color: #14919b;
    margin-top: 14px;
    border-bottom: 1px solid #cbd2d9;
    padding-bottom: 2px;
}
h3 { font-size: 11pt; color: #323f4b; margin-top: 10px; }
h4 { font-size: 10pt; color: #52606d; margin-top: 8px; }
p { margin: 4px 0 8px 0; text-align: justify; }
table {
    width: 100%;
    border-collapse: collapse;
    margin: 8px 0 12px 0;
    font-size: 8pt;
}
th {
    background-color: #0d7377;
    color: white;
    padding: 5px 6px;
    text-align: left;
    font-weight: bold;
}
td {
    border: 1px solid #cbd2d9;
    padding: 4px 6px;
    vertical-align: top;
}
tr:nth-child(even) td { background-color: #f0f4f8; }
code, pre {
    font-family: Courier, monospace;
    font-size: 7.5pt;
    background-color: #f0f4f8;
}
pre {
    border: 1px solid #cbd2d9;
    padding: 8px;
    white-space: pre-wrap;
    word-wrap: break-word;
    margin: 8px 0;
    page-break-inside: avoid;
}
code { padding: 1px 3px; }
ul, ol { margin: 4px 0 8px 0; padding-left: 18px; }
li { margin-bottom: 3px; }
hr {
    border: none;
    border-top: 1px solid #cbd2d9;
    margin: 12px 0;
}
blockquote {
    border-left: 3px solid #0d7377;
    margin: 8px 0;
    padding: 4px 10px;
    background: #f0f4f8;
    color: #52606d;
}
.mermaid-note {
    background: #e8f6f6;
    border: 1px dashed #0d7377;
    padding: 6px 10px;
    font-size: 8pt;
    color: #0d7377;
    margin-bottom: 6px;
}
#footerContent {
    font-size: 7pt;
    color: #9aa5b1;
    text-align: center;
}
"""


def preprocess_markdown(content: str) -> str:
    """Normalize markdown for PDF rendering."""
    content = content.replace("\r\n", "\n")

    def mermaid_replacer(match: re.Match) -> str:
        lang = match.group(1) or ""
        body = match.group(2).strip()
        if lang.lower() == "mermaid":
            note = (
                '<div class="mermaid-note">'
                "[Diagram Mermaid — lihat file diagrams/*.mmd untuk versi visual]"
                "</div>\n"
            )
            return note + f"```\n{body}\n```"
        return match.group(0)

    content = re.sub(
        r"```(\w*)\n(.*?)```",
        mermaid_replacer,
        content,
        flags=re.DOTALL,
    )
    return content


def md_to_html(md_content: str, title: str) -> str:
    md_content = preprocess_markdown(md_content)
    body = markdown.markdown(
        md_content,
        extensions=["tables", "fenced_code", "nl2br", "sane_lists"],
    )
    return f"""<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <title>{title}</title>
    <style>{CSS}</style>
</head>
<body>
    <div class="cover">
        <h1>SIMMAK PPKP</h1>
        <h2>{title}</h2>
        <div class="meta">
            <p><strong>PPKP Provinsi DKI Jakarta</strong></p>
            <p>Sistem Informasi Manajemen Mutu, Audit Internal dan Akreditasi</p>
            <p>Tanggal: 25 Juni 2026 | Versi Blueprint: 1.0</p>
            <p>Laravel 12 | PostgreSQL 17 | Redis | Docker | LDAP AD DKI</p>
        </div>
    </div>
    {body}
    <div id="footerContent">
        SIMMAK PPKP — {title} — Dokumen Rahasia Internal PPKP DKI Jakarta
    </div>
</body>
</html>"""


def html_to_pdf(html: str, output_path: Path) -> bool:
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with open(output_path, "wb") as pdf_file:
        status = pisa.CreatePDF(html, dest=pdf_file, encoding="utf-8")
    return not status.err


def convert_document(md_name: str, pdf_name: str, title: str) -> tuple[str, bool, str]:
    md_path = DOCS_DIR / md_name
    pdf_path = PDF_DIR / pdf_name
    if not md_path.exists():
        return pdf_name, False, f"File tidak ditemukan: {md_path}"

    print(f"  Memproses {md_name} ...", flush=True)
    content = md_path.read_text(encoding="utf-8")
    html = md_to_html(content, title)
    ok = html_to_pdf(html, pdf_path)
    if ok:
        size_mb = pdf_path.stat().st_size / (1024 * 1024)
        return pdf_name, True, f"{size_mb:.2f} MB"
    return pdf_name, False, "Konversi gagal"


def create_diagrams_pdf() -> tuple[bool, str]:
  """Create PDF listing diagram sources (mermaid rendered as code blocks)."""
  pdf_path = PDF_DIR / "SIMMAK-PPKP_Diagrams.pdf"
  sections = []
  for mmd in sorted(DIAGRAMS_DIR.glob("*.mmd")):
      content = mmd.read_text(encoding="utf-8")
      sections.append(f"## {mmd.stem.upper()}\n\n```mermaid\n{content}\n```\n")

  md = (
      "# SIMMAK PPKP — Diagram Pack\n\n"
      "Kumpulan diagram arsitektur dan proses bisnis.\n\n"
      + "\n".join(sections)
  )
  html = md_to_html(md, "Diagram Pack — ERD, DFD, BPMN, Architecture")
  ok = html_to_pdf(html, pdf_path)
  if ok:
      return True, f"{pdf_path.stat().st_size / (1024 * 1024):.2f} MB"
  return False, "Gagal"


def main() -> int:
    PDF_DIR.mkdir(parents=True, exist_ok=True)
    print("=== Konversi Markdown ke PDF — SIMMAK PPKP ===\n")

    results = []
    for md_name, pdf_name, title in DOCUMENTS:
        name, ok, msg = convert_document(md_name, pdf_name, title)
        status = "OK" if ok else "GAGAL"
        results.append((name, status, msg))
        print(f"  [{status}] {name} — {msg}", flush=True)

    print("\n  Memproses Diagram Pack ...", flush=True)
    diag_ok, diag_msg = create_diagrams_pdf()
    results.append(("SIMMAK-PPKP_Diagrams.pdf", "OK" if diag_ok else "GAGAL", diag_msg))
    print(f"  [{'OK' if diag_ok else 'GAGAL'}] SIMMAK-PPKP_Diagrams.pdf — {diag_msg}", flush=True)

    failed = [r for r in results if r[1] == "GAGAL"]
    print(f"\nSelesai: {len(results) - len(failed)}/{len(results)} file PDF di {PDF_DIR}")
    return 1 if failed else 0


if __name__ == "__main__":
    sys.exit(main())
