import React, { useEffect, useState } from "react";
import { makeStyles } from "@material-ui/core/styles";
import Icon from "@material-ui/core/Icon";
import Assignment from "@material-ui/icons/Assignment";
import Warning from "@material-ui/icons/Warning";
import CheckCircle from "@material-ui/icons/CheckCircle";
import Description from "@material-ui/icons/Description";
import GridItem from "components/Grid/GridItem.js";
import GridContainer from "components/Grid/GridContainer.js";
import Card from "components/Card/Card.js";
import CardHeader from "components/Card/CardHeader.js";
import CardIcon from "components/Card/CardIcon.js";
import CardBody from "components/Card/CardBody.js";
import CardFooter from "components/Card/CardFooter.js";
import Table from "components/Table/Table.js";
import Danger from "components/Typography/Danger.js";
import Success from "components/Typography/Success.js";
import api from "services/api.js";
import { useAuth } from "context/AuthContext.js";
import styles from "assets/jss/material-dashboard-react/views/dashboardStyle.js";

const useStyles = makeStyles(styles);

export default function SimmakDashboard() {
  const classes = useStyles();
  const { user } = useAuth();
  const [data, setData] = useState(null);
  const [error, setError] = useState("");

  useEffect(() => {
    api
      .getExecutiveDashboard()
      .then(setData)
      .catch((err) => setError(err.message));
  }, []);

  if (error) {
    return <div style={{ padding: 24 }}>Gagal memuat dashboard: {error}</div>;
  }

  if (!data) {
    return <div style={{ padding: 24 }}>Memuat dashboard...</div>;
  }

  const { stats, clinic_ranking: ranking, modules, implementation } = data;

  const rankingTable = ranking.slice(0, 10).map((row) => [
    `#${row.rank}`,
    row.name,
    `${row.readiness}%`,
    row.open_findings,
    row.accreditation_status,
  ]);

  return (
    <div>
      <GridContainer>
        <GridItem xs={12} sm={6} md={3}>
          <Card>
            <CardHeader color="info" stats icon>
              <CardIcon color="info"><CheckCircle /></CardIcon>
              <p className={classes.cardCategory}>Kesiapan Akreditasi</p>
              <h3 className={classes.cardTitle}>{stats.readiness}%</h3>
            </CardHeader>
            <CardFooter stats>
              <div className={classes.stats}><Success />Lintas 17 entitas</div>
            </CardFooter>
          </Card>
        </GridItem>
        <GridItem xs={12} sm={6} md={3}>
          <Card>
            <CardHeader color="warning" stats icon>
              <CardIcon color="warning"><Warning /></CardIcon>
              <p className={classes.cardCategory}>Temuan Terbuka</p>
              <h3 className={classes.cardTitle}>{stats.open_findings}</h3>
            </CardHeader>
            <CardFooter stats>
              <div className={classes.stats}><Assignment />Audit internal</div>
            </CardFooter>
          </Card>
        </GridItem>
        <GridItem xs={12} sm={6} md={3}>
          <Card>
            <CardHeader color="primary" stats icon>
              <CardIcon color="primary"><Icon>build</Icon></CardIcon>
              <p className={classes.cardCategory}>CAPA Berjalan</p>
              <h3 className={classes.cardTitle}>{stats.capa_open}</h3>
            </CardHeader>
            <CardFooter stats>
              <div className={classes.stats}>Progress {stats.capa_progress}%</div>
            </CardFooter>
          </Card>
        </GridItem>
        <GridItem xs={12} sm={6} md={3}>
          <Card>
            <CardHeader color="danger" stats icon>
              <CardIcon color="danger"><Description /></CardIcon>
              <p className={classes.cardCategory}>Dokumen Kedaluwarsa</p>
              <h3 className={classes.cardTitle}>{stats.expired_documents}</h3>
            </CardHeader>
            <CardFooter stats>
              <div className={classes.stats}><Danger />Perlu review</div>
            </CardFooter>
          </Card>
        </GridItem>
      </GridContainer>

      <GridContainer>
        <GridItem xs={12} md={8}>
          <Card>
            <CardHeader color="primary">
              <h4 className={classes.cardTitleWhite}>Ranking Klinik — Kesiapan Mutu</h4>
            </CardHeader>
            <CardBody>
              <Table
                tableHeaderColor="primary"
                tableHead={["Rank", "Klinik", "Readiness", "Temuan", "Status Akreditasi"]}
                tableData={rankingTable}
              />
            </CardBody>
          </Card>
        </GridItem>
        <GridItem xs={12} md={4}>
          <Card>
            <CardHeader color="info">
              <h4 className={classes.cardTitleWhite}>Status Implementasi</h4>
            </CardHeader>
            <CardBody>
              <p><strong>Fase:</strong> {implementation.current_phase}</p>
              <p><strong>Progress:</strong> {implementation.overall_progress}%</p>
              <p><strong>Pengguna:</strong> {user?.name}</p>
              <p><strong>Organisasi:</strong> {user?.organization?.name}</p>
              <p><strong>Klinik default:</strong> {user?.default_clinic?.name || "—"}</p>
              <hr />
              {modules.map((mod) => (
                <p key={mod.code} style={{ margin: "6px 0", fontSize: 13 }}>
                  <strong>{mod.name}</strong> — {mod.status.replace("_", " ")} ({mod.progress}%)
                </p>
              ))}
            </CardBody>
          </Card>
        </GridItem>
      </GridContainer>
    </div>
  );
}
