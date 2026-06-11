import api from "./api";

export type NotificationData = {
  id: string;
  type: string;
  data: Record<string, any>;
  read_at?: string | null;
  created_at: string;
};

export type PaginatedNotifications = {
  data: NotificationData[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    unread_count: number;
  };
};

export const getNotificationsApi = async (page = 1) => {
  const res = await api.get("/notifications", { params: { page } });
  return res.data as { status: string; data: PaginatedNotifications };
};

export const markNotificationReadApi = async (id: string) => {
  const res = await api.patch(`/notifications/${id}/read`);
  return res.data;
};

export const markAllNotificationsReadApi = async () => {
  const res = await api.post("/notifications/mark-all-read");
  return res.data;
};
