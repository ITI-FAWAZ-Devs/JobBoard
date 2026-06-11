<script setup lang="ts">
import { computed, ref } from "vue";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { Bell, Check, CheckCheck, Clock, AlertCircle, Briefcase, ChevronLeft, ChevronRight } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import { getNotificationsApi, markNotificationReadApi, markAllNotificationsReadApi, type NotificationData } from "@/api/notifications";

const page = ref(1);
const queryClient = useQueryClient();

const { data, isPending, isError } = useQuery({
  queryKey: ["notifications", page],
  queryFn: () => getNotificationsApi(page.value),
});

const notifications = computed<NotificationData[]>(() => data.value?.data?.data ?? []);
const meta = computed(() => data.value?.data?.meta ?? null);
const unreadCount = computed(() => meta.value?.unread_count ?? 0);

const markReadMutation = useMutation({
  mutationFn: (id: string) => markNotificationReadApi(id),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: ["notifications"] });
  },
  onError: () => toast.error("Failed to mark notification as read."),
});

const markAllReadMutation = useMutation({
  mutationFn: () => markAllNotificationsReadApi(),
  onSuccess: () => {
    toast.success("All notifications marked as read.");
    queryClient.invalidateQueries({ queryKey: ["notifications"] });
  },
  onError: () => toast.error("Failed to mark all as read."),
});

function formatTime(dateStr: string) {
  const date = new Date(dateStr);
  const diff = Date.now() - date.getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return "Just now";
  if (mins < 60) return `${mins}m ago`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  if (days === 1) return "Yesterday";
  if (days < 7) return `${days}d ago`;
  return date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

function getNotificationText(n: NotificationData): string {
  if (n.type === "JobStatusChanged") {
    const status = n.data.status === "approved" ? "approved" : "rejected";
    return `Your job listing "${n.data.job_title}" has been ${status}.` + (n.data.reason ? ` Reason: ${n.data.reason}` : "");
  }
  if (n.type === "UserStatusChanged") {
    return `Your user account status has been updated to: ${n.data.status}.`;
  }
  return n.data.message || "New update received.";
}
</script>

<template>
  <main class="min-h-screen bg-background p-md text-on-background md:p-lg">
    <div class="mb-xl flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="font-headline-lg text-headline-lg text-on-background">Notifications</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
          Stay up to date with job approvals, incoming applications, and billing updates.
        </p>
      </div>
      <Button
        v-if="unreadCount > 0"
        variant="outline"
        size="sm"
        class="gap-1.5 rounded-lg border-outline-variant text-xs"
        :disabled="markAllReadMutation.isPending.value"
        @click="markAllReadMutation.mutate()"
      >
        <CheckCheck class="h-4 w-4" />
        Mark all read
      </Button>
    </div>

    <!-- Loading -->
    <div v-if="isPending" class="space-y-3">
      <div v-for="i in 4" :key="i" class="animate-pulse rounded-xl border border-outline-variant bg-surface-container-lowest p-5">
        <div class="mb-2 h-4 w-1/3 rounded bg-surface-container-low" />
        <div class="h-3 w-2/3 rounded bg-surface-container-low" />
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="isError" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-8 text-center">
      <p class="text-sm text-on-surface-variant">Failed to load notifications. Please try again.</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="!notifications.length" class="rounded-xl border border-outline-variant bg-surface-container-lowest p-12 text-center shadow-soft">
      <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
        <Bell class="h-6 w-6" />
      </div>
      <h3 class="mb-1 text-lg font-semibold text-on-surface">All caught up!</h3>
      <p class="text-sm text-on-surface-variant">You have no new notifications.</p>
    </div>

    <!-- Notifications List -->
    <div v-else class="space-y-2">
      <article
        v-for="n in notifications"
        :key="n.id"
        class="group relative flex items-start gap-4 rounded-xl border p-4 transition-all duration-200"
        :class="n.read_at
          ? 'border-outline-variant bg-surface-container-lowest opacity-75'
          : 'border-primary/20 bg-primary/5 shadow-sm'"
      >
        <!-- Unread badge -->
        <div
          v-if="!n.read_at"
          class="absolute left-1.5 top-1/2 h-2.5 w-2.5 -translate-y-1/2 rounded-full bg-primary"
          title="Unread"
        />

        <!-- Icon -->
        <div
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm"
          :class="n.type === 'JobStatusChanged'
            ? 'bg-blue-50 text-blue-600'
            : n.type === 'UserStatusChanged'
              ? 'bg-amber-50 text-amber-600'
              : 'bg-primary/10 text-primary'"
        >
          <Briefcase v-if="n.type === 'JobStatusChanged'" class="h-4.5 w-4.5" />
          <AlertCircle v-else-if="n.type === 'UserStatusChanged'" class="h-4.5 w-4.5" />
          <Bell v-else class="h-4.5 w-4.5" />
        </div>

        <!-- Content -->
        <div class="min-w-0 flex-1">
          <p class="text-sm leading-relaxed text-on-surface">
            {{ getNotificationText(n) }}
          </p>
          <div class="mt-1 flex items-center gap-1.5 text-xs text-on-surface-variant">
            <Clock class="h-3 w-3" />
            <span>{{ formatTime(n.created_at) }}</span>
          </div>
        </div>

        <!-- Mark as Read Button -->
        <button
          v-if="!n.read_at"
          class="flex h-7 w-7 items-center justify-center rounded-lg border border-outline-variant bg-surface-container-lowest text-on-surface-variant transition-colors hover:border-primary/30 hover:bg-primary/5 hover:text-primary"
          title="Mark as read"
          :disabled="markReadMutation.isPending.value"
          @click="markReadMutation.mutate(n.id)"
        >
          <Check class="h-4 w-4" />
        </button>
      </article>
    </div>

    <!-- Pagination -->
    <div v-if="meta && meta.last_page > 1" class="mt-xl flex items-center justify-center gap-2">
      <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--">
        <ChevronLeft class="h-4 w-4" />
        Previous
      </Button>
      <span class="px-3 text-sm text-on-surface-variant">
        Page {{ meta.current_page }} of {{ meta.last_page }}
      </span>
      <Button variant="outline" size="sm" :disabled="page >= meta.last_page" @click="page++">
        Next
        <ChevronRight class="h-4 w-4" />
      </Button>
    </div>
  </main>
</template>
