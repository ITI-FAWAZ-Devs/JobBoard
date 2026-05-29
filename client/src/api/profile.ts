import api from "./api";

export const getProfileApi = async () => {
	const res = await api.get("/auth/me");
	return res?.data?.data ?? res?.data;
};

export const updateProfileApi = async (data: FormData | Record<string, unknown>) => {
	const isFormData = data instanceof FormData;

	if (isFormData) {
		data.append("_method", "PATCH");
		const res = await api.post("/auth/me", data, {
			headers: { "Content-Type": "multipart/form-data" },
		});
		return res?.data?.data ?? res?.data;
	}

	const res = await api.patch("/auth/me", data);
	return res?.data?.data ?? res?.data;
};