import { createRouter, createWebHistory } from "vue-router";
import { getProfileApi } from "@/api/profile";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "home",
      component: () => import("@/views/HomeView.vue"),
    },
    {
      path: "/sign-in",
      name: "Login",
      component: () => import("@/views/LoginView.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/role-select",
      name: "RoleSelect",
      component: () => import("@/views/RoleSelectView.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/sign-up",
      name: "Register",
      component: () => import("@/views/RegisterView.vue"),
      meta: { requiresGuest: true },
    },
    {
      path: "/admin",
      name: "AdminDashboard",
      component: () => import("@/views/Admin/DashboardView.vue"),
      meta: { requiresAuth: true, requiresRole: "admin" },
    },
    {
      path: "/admin/pending-jobs",
      name: "AdminPendingJobs",
      component: () => import("@/views/Admin/PendingJobsView.vue"),
      meta: { requiresAuth: true, requiresRole: "admin" },
    },
    {
      path: "/admin/users",
      name: "AdminUsers",
      component: () => import("@/views/Admin/UserManagementView.vue"),
      meta: { requiresAuth: true, requiresRole: "admin" },
    },
    {
      path: "/employer/analytics",
      name: "EmployerAnalytics",
      component: () => import("@/views/employer/AnalyticsView.vue"),
      meta: { requiresAuth: true, requiresRole: "employer" },
    },
    {
      path: "/employer/checkout",
      name: "EmployerCheckout",
      component: () => import("@/views/Employee/CheckoutView.vue"),
      meta: { requiresAuth: true, requiresRole: "employer" },
    },
    {
      path: "/admin/comments",
      name: "AdminComments",
      component: () => import("@/views/admin/CommentModerationView.vue"),
      meta: { requiresAuth: true, requiresRole: "admin" },
    },
    {
      path: "/:pathMatch(.*)*",
      name: "NotFound",
      component: () => import("@/views/NotFoundView.vue"),
    },
  ],
});

const getStoredUser = () => {
  const raw = localStorage.getItem("user");
  if (!raw) return null;
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
};

const setStoredUser = (user: unknown) => {
  if (!user) return;
  localStorage.setItem("user", JSON.stringify(user));
};

router.beforeEach(async (to) => {
  const token = localStorage.getItem("token");
  let user = getStoredUser();
  const requiresAuth = Boolean(to.meta?.requiresAuth);
  const requiresGuest = Boolean(to.meta?.requiresGuest);
  const requiredRole = (to.meta?.requiresRole as string | undefined) ?? undefined;

  if (requiresGuest && token) {
    return { path: "/" };
  }

  if (requiresAuth && !token) {
    return { path: "/sign-in", query: { redirect: to.fullPath } };
  }

  if (token && !user) {
    try {
      user = await getProfileApi();
      setStoredUser(user);
    } catch {
      localStorage.removeItem("token");
      localStorage.removeItem("user");
      if (requiresAuth) {
        return { path: "/sign-in", query: { redirect: to.fullPath } };
      }
    }
  }

  if (requiredRole && user?.role !== requiredRole) {
    return { path: "/" };
  }

  return true;
});

export default router;
