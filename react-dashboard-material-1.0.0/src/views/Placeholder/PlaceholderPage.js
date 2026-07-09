import React from "react";
import Card from "components/Card/Card.js";
import CardBody from "components/Card/CardBody.js";

export default function PlaceholderPage({ title, phase }) {
  return (
    <Card>
      <CardBody>
        <h3>{title}</h3>
        <p>Modul ini akan diimplementasikan pada <strong>{phase}</strong>.</p>
        <p style={{ color: "#64748b", fontSize: 14 }}>
          Lihat <code>docs/PROGRESS_TIMELINE.md</code> untuk jadwal pembangunan.
        </p>
      </CardBody>
    </Card>
  );
}
