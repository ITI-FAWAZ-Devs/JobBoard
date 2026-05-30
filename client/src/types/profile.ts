export type UserProfile = {
  id?: string | number;
  name?: string;
  email?: string;
  role?: 'admin' | 'candidate' | 'employer';
  avatar_url?: string | null;
  experiences?: ExperienceItem[];
  education?: EducationItem[];
  offices?: OfficeItem[];
  gallery_photos?: GalleryPhotoItem[];
  profile?: {
    id?: string | number;
    company_name?: string | null;
    logo?: string | null;
    logo_url?: string | null;
    cover_photo?: string | null;
    cover_photo_url?: string | null;
    website?: string | null;
    industry?: string | null;
    employee_count?: string | null;
    phone?: string | null;
    linkedin_url?: string | null;
    skills?: string[] | null;
    experience_years?: number | null;
    location?: string | null;
    bio?: string | null;
    description?: string | null;
    perks?: string[];
    resume_url?: string | null;
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

export type OfficeItem = {
  id?: number;
  name?: string | null;
  address: string;
  is_headquarters?: boolean;
};

export type GalleryPhotoItem = {
  id?: number;
  photo?: string | null;
  photo_url?: string | null;
};
