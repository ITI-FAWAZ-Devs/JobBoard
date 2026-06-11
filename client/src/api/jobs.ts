import api from "./api";

export type JobFilters = {
  q?: string;
  category_id?: number;
  location?: string;
  work_type?: string;
  salary_min?: number;
  salary_max?: number;
  date_from?: string;
  date_to?: string;
  page?: number;
  per_page?: number;
};

export type JobListingPublic = {
  id: number;
  title: string;
  description: string;
  requirements?: string | null;
  benefits?: string | null;
  location?: string | null;
  salary_min?: number | null;
  salary_max?: number | null;
  work_type: string;
  deadline?: string | null;
  status: string;
  views_count: number;
  applications_count?: number;
  approved_at?: string | null;
  created_at: string;
  updated_at: string;
  employer_profile?: {
    id: number;
    user_id: number;
    company_name: string;
    logo_url?: string | null;
    website?: string | null;
    phone?: string | null;
    location?: string | null;
    description?: string | null;
  } | null;
  category?: {
    id: number;
    name: string;
  } | null;
};

export type PaginatedJobs = {
  data: JobListingPublic[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  links?: Record<string, string | null>;
};

export type CommentData = {
  id: number;
  user_id: number;
  job_listing_id: number;
  content: string;
  is_hidden: boolean;
  is_reported: boolean;
  created_at: string;
  user?: {
    id: number;
    name: string;
    avatar_url?: string | null;
  };
};

export const getJobsApi = async (filters: JobFilters = {}) => {
  const params: Record<string, unknown> = {};
  if (filters.q) params.q = filters.q;
  if (filters.category_id) params.category_id = filters.category_id;
  if (filters.location) params.location = filters.location;
  if (filters.work_type) params.work_type = filters.work_type;
  if (filters.salary_min) params.salary_min = filters.salary_min;
  if (filters.salary_max) params.salary_max = filters.salary_max;
  if (filters.date_from) params.date_from = filters.date_from;
  if (filters.date_to) params.date_to = filters.date_to;
  if (filters.page) params.page = filters.page;
  if (filters.per_page) params.per_page = filters.per_page;

  const res = await api.get("/jobs", { params });
  return res.data as PaginatedJobs;
};

export const getJobDetailApi = async (id: number) => {
  const res = await api.get(`/jobs/${id}`);
  return res.data as { data: JobListingPublic };
};

export const getJobCommentsApi = async (jobId: number) => {
  const res = await api.get(`/jobs/${jobId}/comments`);
  return res.data as { data: CommentData[] };
};

export const postCommentApi = async (jobId: number, content: string) => {
  const res = await api.post(`/jobs/${jobId}/comments`, { content });
  return res.data as { data: CommentData };
};

export const reportCommentApi = async (commentId: number) => {
  const res = await api.patch(`/comments/${commentId}/report`);
  return res.data;
};

export const applyToJobApi = async (jobId: number, data: FormData) => {
  const res = await api.post(`/jobs/${jobId}/apply`, data, {
    headers: { "Content-Type": "multipart/form-data" },
  });
  return res.data;
};

export const getMyApplicationsApi = async (page = 1) => {
  const res = await api.get("/my-applications", { params: { page } });
  return res.data;
};

export const cancelApplicationApi = async (applicationId: number) => {
  const res = await api.delete(`/applications/${applicationId}`);
  return res.data;
};

export const getCategoriesApi = async () => {
  const res = await api.get("/categories");
  return res.data as { data: { id: number; name: string }[] };
};

export const getPublicStatisticsApi = async () => {
  const res = await api.get("/jobs/statistics");
  return res.data as {
    status: string;
    data: {
      jobs_count: number;
      candidates_count: number;
      companies_count: number;
    };
  };
};

export type CandidateDashboardStats = {
  applied_count: number;
  saved_count: number;
  profile_complete_percent: number;
};

export type CandidateDashboardData = {
  stats: CandidateDashboardStats;
  recent_applications: any[];
  saved_jobs: any[];
  recommended_jobs: any[];
  activity: any[];
};

export type SavedJobItem = {
  id: number;
  saved_at: string;
  job: JobListingPublic;
};

export const getCandidateDashboardApi = async () => {
  const res = await api.get("/candidate/dashboard");
  return res.data as { data: CandidateDashboardData };
};

export const getSavedJobsApi = async (page = 1) => {
  const res = await api.get("/saved-jobs", { params: { page } });
  return res.data as {
    data: {
      data: SavedJobItem[];
      meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
      };
    };
  };
};

export const saveJobApi = async (jobId: number) => {
  const res = await api.post(`/jobs/${jobId}/save`);
  return res.data;
};

export const unsaveJobApi = async (jobId: number) => {
  const res = await api.delete(`/jobs/${jobId}/save`);
  return res.data;
};

export type CompanyProfile = {
  id: number;
  company_name: string;
  logo_url?: string | null;
  website?: string | null;
  industry?: string | null;
  employee_count?: string | null;
  location?: string | null;
  description?: string | null;
  open_jobs_count: number;
};

export type PaginatedCompanies = {
  data: CompanyProfile[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export type CompanyFilters = {
  q?: string;
  industry?: string | string[];
  employee_count?: string | string[];
  location?: string | string[];
  sort?: string;
  page?: number;
};

export const getCompaniesApi = async (filters: CompanyFilters = {}) => {
  const params: Record<string, unknown> = {};
  if (filters.q) params.q = filters.q;
  if (filters.sort) params.sort = filters.sort;
  if (filters.page) params.page = filters.page;
  
  if (filters.industry) {
    params.industry = Array.isArray(filters.industry) ? filters.industry.join(',') : filters.industry;
  }
  if (filters.employee_count) {
    params.employee_count = Array.isArray(filters.employee_count) ? filters.employee_count.join(',') : filters.employee_count;
  }
  if (filters.location) {
    params.location = Array.isArray(filters.location) ? filters.location.join(',') : filters.location;
  }

  const res = await api.get("/companies", { params });
  return res.data as { data: PaginatedCompanies };
};
