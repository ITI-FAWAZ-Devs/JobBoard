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
