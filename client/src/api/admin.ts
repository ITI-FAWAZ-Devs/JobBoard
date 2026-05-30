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

export type EmployerProfile = {
  id: number;
  company_name: string;
  location?: string | null;
};

export type Category = {
  id: number;
  name: string;
};

export type JobListing = {
  id: number;
  title: string;
  location: string;
  status: string;
  created_at?: string | null;
  rejection_reason?: string | null;
  employer_profile?: EmployerProfile | null;
  category?: Category | null;
};

export type UserSummary = {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar_url?: string | null;
  is_active: boolean;
  status?: string;
  suspended_at?: string | null;
  banned_at?: string | null;
  created_at?: string | null;
};

export const getPendingJobsApi = async (page = 1) => {
  const res = await api.get("/admin/jobs/pending", { params: { page } });
  return res.data as ApiResponse<Paginated<JobListing>>;
};

export const approveJobApi = async (jobId: number) => {
  const res = await api.patch(`/admin/jobs/${jobId}/approve`);
  return res.data as ApiResponse<JobListing>;
};

export const rejectJobApi = async (jobId: number, reason: string) => {
  const res = await api.patch(`/admin/jobs/${jobId}/reject`, { reason });
  return res.data as ApiResponse<JobListing>;
};

export const getAdminUsersApi = async (page = 1) => {
  const res = await api.get("/admin/users", { params: { page } });
  return res.data as ApiResponse<Paginated<UserSummary>>;
};

export const suspendUserApi = async (userId: number) => {
  const res = await api.patch(`/admin/users/${userId}/suspend`);
  return res.data as ApiResponse<UserSummary>;
};

export const banUserApi = async (userId: number) => {
  const res = await api.patch(`/admin/users/${userId}/ban`);
  return res.data as ApiResponse<UserSummary>;
};

export const activateUserApi = async (userId: number) => {
  const res = await api.patch(`/admin/users/${userId}/activate`);
  return res.data as ApiResponse<UserSummary>;
};

export type Comment = {
  id: number;
  user_id: number;
  job_listing_id: number;
  content: string;
  is_hidden: boolean;
  is_reported: boolean;
  created_at?: string;
  user?: UserSummary;
  job_listing?: JobListing;
};

export const getAdminCommentsApi = async (page = 1, reported = false) => {
  const res = await api.get("/admin/comments", { params: { page, reported: reported ? 'true' : 'false' } });
  return res.data as ApiResponse<Paginated<Comment>>;
};

export const hideCommentApi = async (commentId: number) => {
  const res = await api.patch(`/admin/comments/${commentId}/hide`);
  return res.data as ApiResponse<Comment>;
};

export const deleteCommentApi = async (commentId: number) => {
  const res = await api.delete(`/admin/comments/${commentId}`);
  return res.data as ApiResponse<null>;
};
