import React, { useState } from "react";
import { makeStyles } from "@material-ui/core/styles";
import Card from "components/Card/Card.js";
import CardBody from "components/Card/CardBody.js";
import Button from "components/CustomButtons/Button.js";
import CustomInput from "components/CustomInput/CustomInput.js";
import GridContainer from "components/Grid/GridContainer.js";
import GridItem from "components/Grid/GridItem.js";
import { useAuth } from "context/AuthContext.js";

const useStyles = makeStyles({
  page: {
    minHeight: "100vh",
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    background: "linear-gradient(135deg, #0d7377 0%, #074548 50%, #1a2332 100%)",
    padding: 24,
  },
  card: {
    maxWidth: 440,
    width: "100%",
    margin: "0 auto",
  },
  title: {
    textAlign: "center",
    marginBottom: 8,
    color: "#0d7377",
    fontWeight: 700,
    fontSize: 28,
  },
  subtitle: {
    textAlign: "center",
    color: "#64748b",
    marginBottom: 24,
    fontSize: 14,
  },
  error: {
    background: "#fef2f2",
    color: "#b91c1c",
    padding: "10px 14px",
    borderRadius: 8,
    marginBottom: 16,
    fontSize: 13,
  },
  footer: {
    textAlign: "center",
    marginTop: 16,
    fontSize: 12,
    color: "#94a3b8",
  },
});

export default function Login() {
  const classes = useStyles();
  const { login } = useAuth();
  const [email, setEmail] = useState("admin@simmak.ppkp.local");
  const [password, setPassword] = useState("password");
  const [authProvider, setAuthProvider] = useState("local");
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setSubmitting(true);
    try {
      await login(email, password, authProvider);
      window.location.href = "/admin/dashboard";
    } catch (err) {
      setError(err.message);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className={classes.page}>
      <div className={classes.card}>
        <div className={classes.title}>SIMMAK PPKP</div>
        <div className={classes.subtitle}>
          Sistem Informasi Manajemen Mutu, Audit & Akreditasi
          <br />
          PPKP Provinsi DKI Jakarta
        </div>
        <Card>
          <CardBody>
            {error && <div className={classes.error}>{error}</div>}
            <form onSubmit={handleSubmit}>
              <GridContainer>
                <GridItem xs={12}>
                  <CustomInput
                    labelText="Email / Username"
                    id="email"
                    formControlProps={{ fullWidth: true }}
                    inputProps={{
                      value: email,
                      onChange: (e) => setEmail(e.target.value),
                      required: true,
                    }}
                  />
                </GridItem>
                <GridItem xs={12}>
                  <CustomInput
                    labelText="Password"
                    id="password"
                    formControlProps={{ fullWidth: true }}
                    inputProps={{
                      type: "password",
                      value: password,
                      onChange: (e) => setPassword(e.target.value),
                      required: true,
                    }}
                  />
                </GridItem>
                <GridItem xs={12}>
                  <label style={{ fontSize: 12, color: "#64748b" }}>Metode Login</label>
                  <select
                    value={authProvider}
                    onChange={(e) => setAuthProvider(e.target.value)}
                    style={{
                      width: "100%",
                      padding: "10px 12px",
                      marginTop: 6,
                      borderRadius: 4,
                      border: "1px solid #cbd5e1",
                    }}
                  >
                    <option value="local">Login Lokal</option>
                    <option value="ldap">LDAP / Active Directory</option>
                  </select>
                </GridItem>
                <GridItem xs={12}>
                  <Button color="info" type="submit" disabled={submitting} block>
                    {submitting ? "Memproses..." : "Masuk"}
                  </Button>
                </GridItem>
              </GridContainer>
            </form>
          </CardBody>
        </Card>
        <div className={classes.footer}>Development — MySQL | Production — PostgreSQL</div>
      </div>
    </div>
  );
}
