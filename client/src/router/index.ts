import { createRouter, createWebHistory } from "vue-router";
import type { RouteLocationNormalized } from "vue-router";
import { useAuth } from "@/composables/useAuth";

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
      component: () => import("@/components/admin/AdminLayout.vue"),
      meta: { requiresAuth: true, requiresRole: "admin" },
      redirect: "/admin/dashboard",
      children: [
        {
          path: "dashboard",
          name: "AdminDashboard",
          component: () => import("@/views/Admin/DashboardView.vue"),
          meta: {
            pageTitle: "Platform Overview",
            pageSubtitle: "Monitor activity and manage content across WorkHive.",
          },
        },
        {
          path: "pending-jobs",
          name: "AdminPendingJobs",
          component: () => import("@/views/Admin/PendingJobsView.vue"),
          meta: {
            pageTitle: "Pending Job Approvals",
            pageSubtitle: "Review job listings awaiting admin approval.",
          },
        },
        {
          path: "users",
          name: "AdminUsers",
          component: () => import("@/views/Admin/UserManagementView.vue"),
          meta: {
            pageTitle: "User Management",
            pageSubtitle: "Review accounts, enforce policy, and keep the platform safe.",
          },
        },
        {
          path: "comments",
          name: "AdminComments",
          component: () => import("@/views/Admin/CommentModerationView.vue"),
          meta: {
            pageTitle: "Comment Moderation",
            pageSubtitle: "Review user comments, hide inappropriate content, or delete spam.",
          },
        },
        {
          path: "profile",
          name: "AdminProfile",
          component: () => import("@/views/Admin/ProfileView.vue"),
          meta: {
            pageTitle: "Admin Profile",
            pageSubtitle: "Manage your administrator account.",
          },
        },
        {
          path: "notifications",
          name: "AdminNotifications",
          component: () => import("@/views/Admin/NotificationsView.vue"),
          meta: {
            pageTitle: "Admin Notifications",
            pageSubtitle: "Review platform alerts and activity updates.",
          },
        },
        {
          path: "settings",
          name: "AdminSettings",
          component: () => import("@/views/Admin/SettingsView.vue"),
          meta: {
            pageTitle: "Admin Settings",
            pageSubtitle: "Configure platform administration preferences.",
          },
        },
      ],
    },
    {
      path: "/employer",
      component: () => import("@/layouts/RoleSidebarLayout.vue"),
      meta: { requiresAuth: true, requiresRole: "employer" },
      redirect: "/employer/dashboard",
      children: [
        {
          path: "dashboard",
          name: "EmployerDashboard",
          component: () => import("@/views/Employee/DashboardView.vue"),
        },
        {
          path: "analytics",
          name: "EmployerAnalytics",
          component: () => import("@/views/employer/AnalyticsView.vue"),
        },
        {
          path: "checkout",
          name: "EmployerCheckout",
          component: () => import("@/views/Employee/CheckoutView.vue"),
        },
        {
          path: "profile",
          name: "EmployerProfile",
          component: () => import("@/views/Employee/ProfileView.vue"),
        },
        {
          path: "notifications",
          name: "EmployerNotifications",
          component: () => import("@/views/Employee/NotificationsView.vue"),
        },
        {
          path: "settings",
          name: "EmployerSettings",
          component: () => import("@/views/Employee/SettingsView.vue"),
        },
      ],
    },
    {
      path: "/candidate",
      component: () => import("@/layouts/RoleSidebarLayout.vue"),
      meta: { requiresAuth: true, requiresRole: "candidate" },
      redirect: "/candidate/dashboard",
      children: [
        {
          path: "dashboard",
          name: "CandidateDashboard",
          component: () => import("@/views/Candidate/DashboardView.vue"),
        },
        {
          path: "profile",
          name: "CandidateProfile",
          component: () => import("@/views/Candidate/ProfileView.vue"),
        },
        {
          path: "notifications",
          name: "CandidateNotifications",
          component: () => import("@/views/Candidate/NotificationsView.vue"),
        },
        {
          path: "settings",
          name: "CandidateSettings",
          component: () => import("@/views/Candidate/SettingsView.vue"),
        },
      ],
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

const getRequiredRole = (to: RouteLocationNormalized) =>
  (to.meta.requiresRole as string | undefined) ??
  (to.meta.requiredRole as string | undefined);

const getRoleHomePath = (role?: string | null) => {
  if (role === "admin") return "/admin/dashboard";
  if (role === "employer") return "/employer/dashboard";
  if (role === "candidate") return "/candidate/dashboard";
  return "/";
};

router.beforeEach(async (to) => {
  const token = localStorage.getItem("token");
  const { user, userRole, fetchUserProfile, setUser } = useAuth();
  const requiresAuth = Boolean(to.meta?.requiresAuth);
  const requiresGuest = Boolean(to.meta?.requiresGuest);
  const requiredRole = getRequiredRole(to);

  if (requiresGuest && token) {
    const storedUser = getStoredUser();
    return { path: getRoleHomePath(storedUser?.role ?? userRole.value) };
  }

  if (requiresAuth && !token) {
    return { path: "/sign-in", query: { redirect: to.fullPath } };
  }

  if (token && !user.value) {
    const storedUser = getStoredUser();
    if (storedUser) {
      setUser(storedUser);
    } else {
      try {
        await fetchUserProfile();
        setStoredUser(user.value);
      } catch {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        return { path: "/sign-in", query: { redirect: to.fullPath } };
      }
    }
  }

  if (requiredRole && userRole.value !== requiredRole) {
    return { path: "/" };
  }

  return true;
});

export default router;
