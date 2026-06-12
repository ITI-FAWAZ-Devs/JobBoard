import api from "./api";

export const loginApi = (data: { email: string; password: string }) => {
  return api.post("/auth/login", data);
};

export const logoutApi = () => {
  return api.post("/auth/logout");
};

export type RegisterPayload = {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: "candidate" | "employer";
  company_name?: string;
};

export const registerApi = (data: RegisterPayload) => {
  return api.post("/auth/register", data);
};

export type OAuthProvider = "google" | "linkedin";

export const getOAuthRedirectUrl = (
  provider: OAuthProvider,
  role: "candidate" | "employer" = "candidate",
) => {
  return `${api.defaults.baseURL}/auth/oauth/${provider}/redirect?role=${role}`;
};

export const getOAuthConnectUrl = (provider: OAuthProvider) => {
  const token = localStorage.getItem("token") ?? "";
  const params = new URLSearchParams({ mode: "connect", connect_token: token });
  return `${api.defaults.baseURL}/auth/oauth/${provider}/redirect?${params.toString()}`;
};
