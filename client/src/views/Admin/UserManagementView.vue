<script setup lang="ts">
import { computed, onMounted } from "vue";
import { Ban, Shield, UserCheck } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { useAdminUsersStore, type RoleFilter } from "@/stores/adminUsers";

const store = useAdminUsersStore();

onMounted(() => store.fetchUsers());

const users = computed(() => store.filteredUsers);

const statusBadge = (status?: string) => {
  switch (status) {
    case "banned":
      return "bg-error-container text-on-error-container";
    case "suspended":
      return "bg-surface-container-high text-on-surface-variant";
    default:
      return "bg-surface-container text-primary";
  }
};

const filters: { label: string; value: RoleFilter }[] = [
  { label: "All", value: "all" },
  { label: "Employers", value: "employer" },
  { label: "Candidates", value: "candidate" },
];

function confirmBan(userId: number, userName: string) {
  if (window.confirm(`Ban ${userName}? This action disables the account.`)) {
    store.banUser(userId);
  }
}
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl flex flex-col justify-between gap-sm md:flex-row md:items-center">
        <div>
          <h1 class="font-headline-lg text-headline-lg text-on-background">User Management</h1>
          <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
            Review accounts, enforce policy, and keep the platform safe.
          </p>
        </div>
        <div class="font-label-md text-label-md text-on-surface-variant">
          {{ users.length }} result{{ users.length !== 1 ? 's' : '' }}
        </div>
      </div>

      <div class="mb-md flex flex-wrap items-center gap-sm">
        <button
          v-for="f in filters"
          :key="f.value"
          class="rounded-lg px-3 py-1.5 font-label-md text-label-md transition-colors"
          :class="store.roleFilter === f.value
            ? 'bg-primary-container text-on-primary'
            : 'text-on-surface-variant hover:bg-surface-container-high'"
          @click="store.setRoleFilter(f.value)"
        >
          {{ f.label }}
        </button>
      </div>

      <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
        <div class="border-b border-outline-variant p-md">
          <h2 class="font-headline-md text-headline-md text-on-background">All Users</h2>
        </div>

        <div v-if="store.loading" class="divide-y divide-outline-variant">
          <div v-for="i in 4" :key="i" class="flex items-center gap-md p-md animate-pulse">
            <div class="h-5 w-40 rounded bg-surface-container-high"></div>
            <div class="h-5 w-24 rounded bg-surface-container-high"></div>
            <div class="h-5 w-20 rounded bg-surface-container-high"></div>
            <div class="h-5 w-24 rounded bg-surface-container-high"></div>
            <div class="ml-auto h-8 w-48 rounded bg-surface-container-high"></div>
          </div>
        </div>

        <div v-else-if="store.error" class="p-md font-body-sm text-body-sm text-destructive">
          {{ store.error }}
        </div>

        <div v-else-if="!users.length" class="p-md text-center font-body-sm text-body-sm text-on-surface-variant">
          No users found.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr class="border-b border-outline-variant bg-surface">
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Name</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Email</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Role</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Status</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Joined At</th>
                <th class="p-md text-right font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
              <tr v-for="user in users" :key="user.id" class="group transition-colors hover:bg-surface-container-low">
                <td class="p-md font-label-md text-label-md text-on-background">{{ user.name }}</td>
                <td class="p-md font-body-sm text-body-sm text-on-surface-variant">{{ user.email }}</td>
                <td class="p-md">
                  <span class="inline-flex rounded-full bg-primary-container px-2.5 py-0.5 font-label-sm text-label-sm capitalize text-primary">
                    {{ user.role }}
                  </span>
                </td>
                <td class="p-md">
                  <span
                    class="inline-flex rounded-full px-2.5 py-0.5 font-label-sm text-label-sm capitalize"
                    :class="statusBadge(user.status || (user.is_active ? 'active' : 'inactive'))"
                  >
                    {{ user.status || (user.is_active ? 'active' : 'inactive') }}
                  </span>
                </td>
                <td class="p-md font-body-sm text-body-sm text-on-surface-variant">
                  {{ user.created_at ? new Date(user.created_at).toLocaleDateString() : '—' }}
                </td>
                <td class="p-md text-right">
                  <div class="flex items-center justify-end gap-xs">
                    <Button
                      v-if="user.status === 'suspended' || user.status === 'banned' || !user.is_active"
                      size="sm"
                      variant="outline"
                      :disabled="store.actionLoading[user.id]"
                      @click="store.restoreUser(user.id)"
                    >
                      <UserCheck class="h-4 w-4" aria-hidden="true" />
                      Restore
                    </Button>
                    <template v-else>
                      <Button
                        size="sm"
                        variant="outline"
                        :disabled="store.actionLoading[user.id]"
                        @click="store.suspendUser(user.id)"
                      >
                        <Shield class="h-4 w-4" aria-hidden="true" />
                        Suspend
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        class="border-destructive text-destructive hover:bg-destructive/10"
                        :disabled="store.actionLoading[user.id]"
                        @click="confirmBan(user.id, user.name)"
                      >
                        <Ban class="h-4 w-4" aria-hidden="true" />
                        Ban
                      </Button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</template>
