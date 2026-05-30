<script setup lang="ts">
import { computed, ref } from "vue";
import { useMutation, useQuery, useQueryClient } from "@tanstack/vue-query";
import { Ban, Shield, UserCheck } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import {
  activateUserApi,
  banUserApi,
  getAdminUsersApi,
  suspendUserApi,
  type UserSummary,
} from "@/api/admin";

const queryClient = useQueryClient();
const page = ref(1);
const search = ref("");

const { data, isPending, isError } = useQuery({
  queryKey: ["admin", "users", page],
  queryFn: () => getAdminUsersApi(page.value),
  keepPreviousData: true,
});

const users = computed<UserSummary[]>(() => data.value?.data?.data ?? []);

const filteredUsers = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) return users.value;
  return users.value.filter((user) =>
    [user.name, user.email, user.role, user.status]
      .filter(Boolean)
      .join(" ")
      .toLowerCase()
      .includes(term),
  );
});

const statusStyle = (status?: string) => {
  switch (status) {
    case "banned":
      return "bg-destructive/10 text-destructive";
    case "suspended":
      return "bg-warning/10 text-warning";
    default:
      return "bg-secondary/10 text-secondary";
  }
};

const suspendMutation = useMutation({
  mutationFn: (userId: number) => suspendUserApi(userId),
  onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "users"] }),
});

const banMutation = useMutation({
  mutationFn: (userId: number) => banUserApi(userId),
  onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "users"] }),
});

const activateMutation = useMutation({
  mutationFn: (userId: number) => activateUserApi(userId),
  onSuccess: () => queryClient.invalidateQueries({ queryKey: ["admin", "users"] }),
});

const confirmBan = (user: UserSummary) => {
  if (user.role === "admin") return;
  if (window.confirm(`Ban ${user.name}? This action disables the account.`)) {
    banMutation.mutate(user.id);
  }
};
</script>

<template>
  <div>
    <div class="mb-md flex flex-wrap items-center justify-end gap-sm">
      <div class="relative">
        <input
          v-model="search"
          class="h-9 w-64 rounded-md border border-outline-variant bg-surface-container-lowest px-sm text-sm focus:border-primary focus:outline-none"
          placeholder="Search users"
          type="text"
        />
      </div>
    </div>

    <section class="rounded-2xl border border-outline-variant bg-card shadow-soft">
      <header class="flex items-center justify-between border-b border-outline-variant px-lg py-md">
        <div>
          <h2 class="text-lg font-semibold text-on-surface">All Users</h2>
          <p class="text-sm text-on-surface-variant">Suspend or ban accounts when needed.</p>
        </div>
        <div class="text-sm text-on-surface-variant">
          {{ filteredUsers.length }} results
        </div>
      </header>

      <div v-if="isPending" class="px-lg py-lg text-sm text-on-surface-variant">
        Loading users...
      </div>
      <div v-else-if="isError" class="px-lg py-lg text-sm text-destructive">
        Failed to load users. Please refresh.
      </div>
      <div v-else-if="!filteredUsers.length" class="px-lg py-lg text-sm text-on-surface-variant">
        No users found for that search.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-outline-variant text-sm">
          <thead class="bg-surface-container-lowest text-left text-xs uppercase tracking-wide text-on-surface-variant">
            <tr>
              <th class="px-lg py-sm font-medium">User</th>
              <th class="px-lg py-sm font-medium">Role</th>
              <th class="px-lg py-sm font-medium">Status</th>
              <th class="px-lg py-sm font-medium">Created</th>
              <th class="px-lg py-sm text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant">
            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-surface-container-low">
              <td class="px-lg py-md">
                <div class="font-semibold text-on-surface">{{ user.name }}</div>
                <div class="text-xs text-on-surface-variant">{{ user.email }}</div>
              </td>
              <td class="px-lg py-md capitalize text-on-surface-variant">{{ user.role }}</td>
              <td class="px-lg py-md">
                <span
                  class="rounded-full px-sm py-[2px] text-xs font-medium"
                  :class="statusStyle(user.status)"
                >
                  {{ user.status || (user.is_active ? 'active' : 'inactive') }}
                </span>
              </td>
              <td class="px-lg py-md text-on-surface-variant">
                {{ user.created_at ? new Date(user.created_at).toLocaleDateString() : "" }}
              </td>
              <td class="px-lg py-md">
                <div class="flex items-center justify-end gap-xs">
                  <Button
                    v-if="!user.is_active"
                    size="sm"
                    variant="outline"
                    :disabled="activateMutation.isPending || user.role === 'admin'"
                    @click="activateMutation.mutate(user.id)"
                  >
                    <UserCheck class="h-4 w-4" aria-hidden="true" />
                    Reactivate
                  </Button>
                  <Button
                    v-else
                    size="sm"
                    variant="outline"
                    :disabled="suspendMutation.isPending || user.role === 'admin'"
                    @click="suspendMutation.mutate(user.id)"
                  >
                    <Shield class="h-4 w-4" aria-hidden="true" />
                    Suspend
                  </Button>
                  <Button
                    size="sm"
                    variant="destructive"
                    :disabled="banMutation.isPending || user.role === 'admin' || user.status === 'banned'"
                    @click="confirmBan(user)"
                  >
                    <Ban class="h-4 w-4" aria-hidden="true" />
                    Ban
                  </Button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
