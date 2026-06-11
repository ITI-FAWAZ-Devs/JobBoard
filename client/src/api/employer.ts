import api from "./api";

export type StripeSessionResponse = {
  session_url: string;
  session_id: string;
};

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

export type PaginatedEmployerJobs = {
  data: JobListing[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export const getEmployerJobsApi = async (page = 1) => {
  const res = await api.get("/employer/jobs", { params: { page } });
  return res.data as PaginatedEmployerJobs;
};

export const getCandidatesApi = async (page = 1) => {
  const res = await api.get("/employer/candidates", { params: { page } });
  return res.data as { status: string; data: { data: CandidateSummary[] } };
};

export const getApplicationCheckoutApi = async (applicationId: number) => {
  const res = await api.get(`/applications/${applicationId}/checkout`);
  return res.data as { status: string; data: ApplicationSummary };
};

export const createStripeCheckoutSessionApi = async (applicationId: number) => {
  const res = await api.post("/payments/stripe/session", { application_id: applicationId });
  return res.data as { status: string; data: StripeSessionResponse };
};

export const verifyPaymentStatusApi = async (applicationId: number) => {
  const res = await api.get(`/applications/${applicationId}/payment/status`);
  return res.data as { status: string; data: { paid: boolean } };
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
