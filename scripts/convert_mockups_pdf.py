#!/usr/bin/env python3
"""Generate mockup showcase PDF for SIMMAK PPKP dashboards."""

import base64
from pathlib import Path
from xhtml2pdf import pisa

ROOT = Path(__file__).resolve().parent.parent
MOCKUPS = ROOT / "mockups"
PDF_DIR = ROOT / "pdf"

MOCKUP_FILES = [
    ("dashboard.png", "Executive Dashboard", "Readiness Akreditasi, Open Findings, CAPA Progress, Risk Heatmap"),
    ("clinic-dashboard.png", "Dashboard Klinik", "Audit, CAPA, Risiko, Akreditasi, Mutu per klinik"),
    ("accreditation-dashboard.png", "Dashboard Akreditasi", "FKTP/FKTL readiness, gap analysis, evidence repository"),
]

CSS = """
@page { size: A4 landscape; margin: 1.5cm; }
body { font-family: Helvetica, Arial, sans-serif; color: #1a2332; }
h1 { color: #0d7377; font-size: 18pt; text-align: center; }
h2 { color: #14919b; font-size: 13pt; margin-top: 20px; }
p { font-size: 10pt; color: #52606d; }
img { max-width: 100%; border: 1px solid #cbd2d9; margin: 10px 0; }
.page { page-break-after: always; }
"""


def main():
    PDF_DIR.mkdir(parents=True, exist_ok=True)
    sections = [
        '<div class="page">',
        "<h1>SIMMAK PPKP — UI Mockup Showcase</h1>",
        "<p>PPKP Provinsi DKI Jakarta | 25 Juni 2026</p>",
        "</div>",
    ]
    for fname, title, desc in MOCKUP_FILES:
        img_path = MOCKUPS / fname
        if not img_path.exists():
            continue
        b64 = base64.b64encode(img_path.read_bytes()).decode("ascii")
        data_uri = f"data:image/png;base64,{b64}"
        sections.append(f"""
        <div class="page">
            <h2>{title}</h2>
            <p>{desc}</p>
            <img src="{data_uri}" alt="{title}"/>
        </div>
        """)

    html = f"<!DOCTYPE html><html><head><meta charset='UTF-8'/><style>{CSS}</style></head><body>{''.join(sections)}</body></html>"
    out = PDF_DIR / "SIMMAK-PPKP_Mockups.pdf"
    with open(out, "wb") as f:
        pisa.CreatePDF(html, dest=f, encoding="utf-8")
    print(f"Created {out} ({out.stat().st_size/1024:.0f} KB)")


if __name__ == "__main__":
    main()
