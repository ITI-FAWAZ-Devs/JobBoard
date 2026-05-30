import api from "./api";

export interface OfficePayload {
  name?: string;
  address: string;
  is_headquarters?: boolean;
}

export const createOfficeApi = (data: OfficePayload) => {
  return api.post("/offices", data);
};

export const updateOfficeApi = (id: number, data: Partial<OfficePayload>) => {
  return api.put(`/offices/${id}`, data);
};

export const deleteOfficeApi = (id: number) => {
  return api.delete(`/offices/${id}`);
};

export const uploadGalleryPhotoApi = (file: File) => {
  const fd = new FormData();
  fd.append("photo", file);
  return api.post("/gallery-photos", fd, {
    headers: { "Content-Type": "multipart/form-data" },
  });
};

export const deleteGalleryPhotoApi = (id: number) => {
  return api.delete(`/gallery-photos/${id}`);
};
