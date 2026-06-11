import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { toast } from "vue-sonner";
import {
  getAdminUsersApi,
  suspendUserApi,
  banUserApi,
  restoreUserApi,
  type UserSummary,
  type Paginated,
} from "@/api/admin";

export type RoleFilter = "all" | "employer" | "candidate";

export const useAdminUsersStore = defineStore("adminUsers", () => {
  const users = ref<UserSummary[]>([]);
  const meta = ref<Paginated<UserSummary>["meta"] | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const actionLoading = ref<Record<number, boolean>>({});
  const roleFilter = ref<RoleFilter>("all");

  const filteredUsers = computed(() => {
    if (roleFilter.value === "all") return users.value;
    return users.value.filter((u) => u.role === roleFilter.value);
  });

  async function fetchUsers(page = 1) {
    loading.value = true;
    error.value = null;
    try {
      const res = await getAdminUsersApi(page);
      users.value = res.data.data;
      meta.value = res.data.meta ?? null;
    } catch (e: any) {
      error.value = e?.response?.data?.message || "Failed to load users";
      toast.error(error.value!);
    } finally {
      loading.value = false;
    }
  }

  async function suspendUser(userId: number) {
    actionLoading.value[userId] = true;
    try {
      await suspendUserApi(userId);
      await fetchUsers();
      toast.success("User suspended");
    } catch (e: any) {
      toast.error(e?.response?.data?.message || "Failed to suspend user");
    } finally {
      actionLoading.value[userId] = false;
    }
  }

  async function banUser(userId: number) {
    actionLoading.value[userId] = true;
    try {
      await banUserApi(userId);
      await fetchUsers();
      toast.success("User banned");
    } catch (e: any) {
      toast.error(e?.response?.data?.message || "Failed to ban user");
    } finally {
      actionLoading.value[userId] = false;
    }
  }

  async function restoreUser(userId: number) {
    actionLoading.value[userId] = true;
    try {
      await restoreUserApi(userId);
      await fetchUsers();
      toast.success("User restored");
    } catch (e: any) {
      toast.error(e?.response?.data?.message || "Failed to restore user");
    } finally {
      actionLoading.value[userId] = false;
    }
  }

  function setRoleFilter(filter: RoleFilter) {
    roleFilter.value = filter;
  }

  return {
    users,
    filteredUsers,
    meta,
    loading,
    error,
    actionLoading,
    roleFilter,
    fetchUsers,
    suspendUser,
    banUser,
    restoreUser,
    setRoleFilter,
  };
});
