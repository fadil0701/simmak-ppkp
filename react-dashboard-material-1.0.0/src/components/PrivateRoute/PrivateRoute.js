import React from "react";
import { Route, Redirect } from "react-router-dom";
import { useAuth } from "context/AuthContext.js";

export default function PrivateRoute({ component: Component, ...rest }) {
  const { isAuthenticated, loading } = useAuth();

  return (
    <Route
      {...rest}
      render={(props) => {
        if (loading) {
          return <div style={{ padding: 40, textAlign: "center" }}>Memuat...</div>;
        }
        return isAuthenticated ? (
          <Component {...props} />
        ) : (
          <Redirect to="/login" />
        );
      }}
    />
  );
}
