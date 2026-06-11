import api from "./api";

export type JobListing = {
  id: number;
  title: string;
  description?: string;
  location?: string;
  salary_min?: number;
  salary_max?: number;
  work_type?: string;
  deadline?: string;
  status: string;
  views_count?: number;
  applications_count?: number;
  rejection_reason?: string | null;
  approved_at?: string;
  created_at?: string;
  updated_at?: string;
  category?: { id: number; name: string } | null;
};

export type CandidateSummary = {
  id: number;
  name: string;
  profile?: {
    location?: string;
    experience_years?: number;
    skills?: string[];
  };
};

export type ApplicationSummary = {
  id: number;
  candidate_name: string;
  job_title: string;
  amount: number;
  currency: string;
  status: string;
};

export type ContactDetails = {
  email: string;
  phone?: string | null;
};

export type AnalyticsData = {
  views: number;
  applicants: number;
  conversion_rate: number;
  per_listing: {
    title: string;
    views: number;
    applicants: number;
  }[];
  views_over_time: {
    date: string;
    views: number;
  }[];
};

export const getEmployerJobsApi = async (page = 1) => {
  const res = await api.get("/employer/jobs", { params: { page } });
  return res.data as { status: string; data: { data: JobListing[] } };
};

export const getCandidatesApi = async (page = 1) => {
  const res = await api.get("/employer/candidates", { params: { page } });
  return res.data as { status: string; data: { data: CandidateSummary[] } };
};

export const createStripeIntentApi = async (payload: { job_id: number; candidate_id: number }) => {
  const res = await api.post("/employer/payments/stripe/intent", payload);
  return res.data as { status: string; data: { client_secret: string } };
};

export const createPayPalOrderApiV1 = async (payload: { job_id: number; candidate_id: number }) => {
  const res = await api.post("/employer/payments/paypal/order", payload);
  return res.data as { status: string; data: { order_id: string; approval_url: string } };
};

export const getCandidateContactApiV1 = async (candidateId: number, jobId: number) => {
  const res = await api.get(`/employer/candidates/${candidateId}/contact`, { params: { job_id: jobId } });
  return res.data as { status: string; data: { email: string; phone?: string; linkedin_url?: string } };
};

export const getApplicationCheckoutApi = async (applicationId: number) => {
  const res = await api.get(`/applications/${applicationId}/checkout`);
  return res.data as { status: string; data: ApplicationSummary };
};

export const createStripePaymentIntentApi = async (applicationId: number) => {
  const res = await api.post("/payments/stripe", { application_id: applicationId });
  return res.data as { status: string; data: { client_secret: string } };
};

export const createPayPalOrderApi = async (applicationId: number) => {
  const res = await api.post("/payments/paypal", { application_id: applicationId });
  return res.data as { status: string; data: { approve_url: string } };
};

export const getCandidateContactApi = async (applicationId: number) => {
  const res = await api.get(`/applications/${applicationId}/contact`);
  return res.data as { status: string; data: ContactDetails };
};

export const getEmployerAnalyticsApi = async () => {
  const res = await api.get("/employer/analytics");
  return res.data as { status: string; data: AnalyticsData };
};

export const createOfficeApi = async (data: { name: string; address: string; is_headquarters: boolean }) => {
  const res = await api.post("/offices", data);
  return res.data;
};

export const updateOfficeApi = async (id: number, data: { name: string; address: string; is_headquarters: boolean }) => {
  const res = await api.put(`/offices/${id}`, data);
  return res.data;
};

export const deleteOfficeApi = async (id: number) => {
  const res = await api.delete(`/offices/${id}`);
  return res.data;
};

export const uploadGalleryPhotoApi = async (file: File) => {
  const formData = new FormData();
  formData.append("photo", file);
  const res = await api.post("/gallery-photos", formData, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return res.data;
};

export const deleteGalleryPhotoApi = async (id: number) => {
  const res = await api.delete(`/gallery-photos/${id}`);
  return res.data;
};
