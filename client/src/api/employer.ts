import api from "./api";

export type ApiResponse<T> = {
  status: "success" | "error" | string;
  message: string;
  data: T;
};

export type Paginated<T> = {
  data: T[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  links?: {
    next?: string | null;
    prev?: string | null;
  };
};

export type JobListing = {
  id: number;
  title: string;
  location?: string | null;
  status?: string;
};

export type CandidateProfile = {
  location?: string | null;
  experience_years?: number | null;
  skills?: string[] | null;
  bio?: string | null;
};

export type CandidateSummary = {
  id: number;
  name: string;
  avatar_url?: string | null;
  role: string;
  profile?: CandidateProfile | null;
};

export type PaymentSummary = {
  id: number;
  provider: string;
  amount: number;
  currency: string;
  status: string;
  job_id: number;
  candidate_id: number;
  employer_id: number;
  created_at?: string | null;
};

export const getEmployerJobsApi = async (page = 1) => {
  const res = await api.get("/employer/jobs", { params: { page } });
  return res?.data?.data ?? res?.data;
};

export const getCandidatesApi = async (page = 1) => {
  const res = await api.get("/employer/candidates", { params: { page } });
  return res.data as ApiResponse<Paginated<CandidateSummary>>;
};

export const createStripeIntentApi = async (payload: {
  job_id: number;
  candidate_id: number;
}) => {
  const res = await api.post("/employer/payments/stripe/intent", payload);
  return res.data as ApiResponse<{ payment: PaymentSummary; client_secret: string }>;
};

export const getCandidateContactApi = async (candidateId: number, jobId: number) => {
  const res = await api.get(`/employer/candidates/${candidateId}/contact`, {
    params: { job_id: jobId },
  });
  return res.data as ApiResponse<{ id: number; name: string; email: string; phone?: string | null; linkedin_url?: string | null }>;
};

export const getEmployerAnalyticsApi = async () => {
  const res = await api.get("/employer/analytics");
  return res.data as ApiResponse<{
    total_profile_views: number;
    total_applications: number;
    interview_conversion: number;
    jobs: any[];
  }>;
};
