import api from "./api";

export async function getExperiencesApi() {
  const res = await api.get("/experiences");
  return res?.data?.data ?? [];
}

export async function createExperienceApi(data: Record<string, unknown>) {
  const res = await api.post("/experiences", data);
  return res?.data?.data ?? res?.data;
}

export async function updateExperienceApi(id: number | string, data: Record<string, unknown>) {
  const res = await api.put(`/experiences/${id}`, data);
  return res?.data?.data ?? res?.data;
}

export async function deleteExperienceApi(id: number | string) {
  await api.delete(`/experiences/${id}`);
}

export async function getEducationApi() {
  const res = await api.get("/education");
  return res?.data?.data ?? [];
}

export async function createEducationApi(data: Record<string, unknown>) {
  const res = await api.post("/education", data);
  return res?.data?.data ?? res?.data;
}

export async function updateEducationApi(id: number | string, data: Record<string, unknown>) {
  const res = await api.put(`/education/${id}`, data);
  return res?.data?.data ?? res?.data;
}

export async function deleteEducationApi(id: number | string) {
  await api.delete(`/education/${id}`);
}
