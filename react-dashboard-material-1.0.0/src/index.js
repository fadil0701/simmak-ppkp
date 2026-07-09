import React from "react";
import ReactDOM from "react-dom";
import { createBrowserHistory } from "history";
import { Router, Route, Switch, Redirect } from "react-router-dom";
import { AuthProvider } from "context/AuthContext.js";
import PrivateRoute from "components/PrivateRoute/PrivateRoute.js";
import Admin from "layouts/Admin.js";
import Login from "views/Login/Login.js";
import "assets/css/material-dashboard-react.css?v=1.9.0";

const hist = createBrowserHistory();

ReactDOM.render(
  <AuthProvider>
    <Router history={hist}>
      <Switch>
        <Route exact path="/login" component={Login} />
        <PrivateRoute path="/admin" component={Admin} />
        <Redirect from="/" to="/login" />
      </Switch>
    </Router>
  </AuthProvider>,
  document.getElementById("root")
);
