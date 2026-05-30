export type UserProfile = {
  id?: string | number;
  name?: string;
  email?: string;
  role?: 'admin' | 'candidate' | 'employer';
  avatar_url?: string | null;
  experiences?: ExperienceItem[];
  education?: EducationItem[];
  profile?: {
    id?: string | number;
    company_name?: string | null;
    logo_url?: string | null;
    resume_url?: string | null;
    phone?: string | null;
    linkedin_url?: string | null;
    skills?: string[] | null;
    experience_years?: number | null;
    location?: string | null;
    bio?: string | null;
  } | null;
};

export type ExperienceItem = {
  id?: number;
  title: string;
  company: string;
  location?: string | null;
  period?: string | null;
  description?: string | null;
  current?: boolean;
};

export type EducationItem = {
  id?: number;
  title: string;
  school: string;
  period?: string | null;
};
