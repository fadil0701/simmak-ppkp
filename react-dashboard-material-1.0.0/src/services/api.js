const API_BASE = process.env.REACT_APP_API_URL || "http://localhost:8000/api/v1";

class ApiService {
  constructor() {
    this.baseUrl = API_BASE;
  }

  getToken() {
    return localStorage.getItem("simmak_token");
  }

  setToken(token) {
    if (token) {
      localStorage.setItem("simmak_token", token);
    } else {
      localStorage.removeItem("simmak_token");
    }
  }

  async request(path, options = {}) {
    const headers = {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(options.headers || {}),
    };

    const token = this.getToken();
    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(`${this.baseUrl}${path}`, {
      ...options,
      headers,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      const message = data.message || data.errors?.email?.[0] || "Permintaan gagal.";
      throw new Error(message);
    }

    return data;
  }

  login(email, password, authProvider = "local") {
    return this.request("/auth/login", {
      method: "POST",
      body: JSON.stringify({
        email,
        password,
        auth_provider: authProvider,
        device_name: "simmak-react-dashboard",
      }),
    });
  }

  me() {
    return this.request("/auth/me");
  }

  logout() {
    return this.request("/auth/logout", { method: "POST" });
  }

  getExecutiveDashboard() {
    return this.request("/dashboard/executive");
  }
}

export default new ApiService();
