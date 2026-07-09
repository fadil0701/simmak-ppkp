import Dashboard from "@material-ui/icons/Dashboard";
import Description from "@material-ui/icons/Description";
import Search from "@material-ui/icons/Search";
import Build from "@material-ui/icons/Build";
import Warning from "@material-ui/icons/Warning";
import School from "@material-ui/icons/School";
import TrendingUp from "@material-ui/icons/TrendingUp";
import SimmakDashboard from "views/Dashboard/SimmakDashboard.js";
import PlaceholderPage from "views/Placeholder/PlaceholderPage.js";

const dashboardRoutes = [
  {
    path: "/dashboard",
    name: "Executive Dashboard",
    rtlName: "Dashboard",
    icon: Dashboard,
    component: SimmakDashboard,
    layout: "/admin",
  },
  {
    path: "/dms",
    name: "Dokumen (DMS)",
    rtlName: "DMS",
    icon: Description,
    component: () => <PlaceholderPage title="Document Management System" phase="Fase 2" />,
    layout: "/admin",
  },
  {
    path: "/audit",
    name: "Audit Internal",
    rtlName: "Audit",
    icon: Search,
    component: () => <PlaceholderPage title="Audit Internal" phase="Fase 3" />,
    layout: "/admin",
  },
  {
    path: "/capa",
    name: "CAPA",
    rtlName: "CAPA",
    icon: Build,
    component: () => <PlaceholderPage title="CAPA" phase="Fase 3" />,
    layout: "/admin",
  },
  {
    path: "/risk",
    name: "Manajemen Risiko",
    rtlName: "Risiko",
    icon: Warning,
    component: () => <PlaceholderPage title="Manajemen Risiko" phase="Fase 4" />,
    layout: "/admin",
  },
  {
    path: "/accreditation",
    name: "Akreditasi",
    rtlName: "Akreditasi",
    icon: School,
    component: () => <PlaceholderPage title="Akreditasi FKTP/FKTL" phase="Fase 5" />,
    layout: "/admin",
  },
  {
    path: "/quality",
    name: "Program Mutu",
    rtlName: "Mutu",
    icon: TrendingUp,
    component: () => <PlaceholderPage title="Program Mutu" phase="Fase 4" />,
    layout: "/admin",
  },
];

export default dashboardRoutes;
