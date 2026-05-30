import api from "./api";
import type { UserProfile } from "@/types/profile";

export const getProfileApi = async (): Promise<UserProfile> => {
  const res = await api.get("/auth/me");
  return (res?.data?.data ?? res?.data) as UserProfile;
};

export const updateProfileApi = async (
  data: FormData | Record<string, unknown>,
): Promise<UserProfile> => {
  const isFormData = data instanceof FormData;

  if (isFormData) {
    data.append("_method", "PATCH");
    const res = await api.post("/auth/me", data, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    return (res?.data?.data ?? res?.data) as UserProfile;
  }

  const res = await api.patch("/auth/me", data);
  return (res?.data?.data ?? res?.data) as UserProfile;
};
