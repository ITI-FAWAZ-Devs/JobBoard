<script setup lang="ts">
import { computed, ref } from "vue";
import { useMutation, useQuery, useQueryClient } from "@tanstack/vue-query";
import { Flag, Trash2, Search, XCircle, AlertTriangle } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import {
  getAdminCommentsApi,
  deleteCommentApi,
  unflagCommentApi,
  type Comment,
} from "@/api/admin";

const queryClient = useQueryClient();
const page = ref(1);
const search = ref("");

const { data, isPending, isError } = useQuery({
  queryKey: ["admin", "comments", "flagged", page],
  queryFn: () => getAdminCommentsApi(page.value, true),
  keepPreviousData: true,
});

const comments = computed<Comment[]>(() => data.value?.data?.data ?? []);

const filteredComments = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) return comments.value;
  return comments.value.filter(
    (c) =>
      (c.user?.name || "").toLowerCase().includes(term) ||
      c.content.toLowerCase().includes(term),
  );
});

const deleteMutation = useMutation({
  mutationFn: (commentId: number) => deleteCommentApi(commentId),
  onSuccess: () => {
    toast.success("Comment deleted permanently.");
    queryClient.invalidateQueries({ queryKey: ["admin", "comments"] });
  },
  onError: () => toast.error("Failed to delete comment."),
});

const unflagMutation = useMutation({
  mutationFn: (commentId: number) => unflagCommentApi(commentId),
  onSuccess: () => {
    toast.success("Flag dismissed.");
    queryClient.invalidateQueries({ queryKey: ["admin", "comments"] });
  },
  onError: () => toast.error("Failed to dismiss flag."),
});

const confirmDelete = (comment: Comment) => {
  if (window.confirm("Delete this comment permanently? This cannot be undone.")) {
    deleteMutation.mutate(comment.id);
  }
};
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <div class="mb-xl flex flex-col justify-between gap-sm md:flex-row md:items-center">
        <div>
          <h1 class="font-headline-lg text-headline-lg text-on-background">Comment Moderation</h1>
          <p class="mt-1 font-body-md text-body-md text-on-surface-variant">
            Review and moderate flagged comments.
          </p>
        </div>
        <div class="font-label-md text-label-md text-on-surface-variant">
          {{ filteredComments.length }} result{{ filteredComments.length !== 1 ? 's' : '' }}
        </div>
      </div>

      <div class="mb-md flex flex-wrap items-center justify-end gap-sm">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-on-surface-variant" aria-hidden="true" />
          <input
            v-model="search"
            class="w-72 rounded-lg border border-outline-variant bg-surface-container-lowest py-2 pl-10 pr-4 font-body-sm text-body-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            placeholder="Search by author or comment text..."
            type="text"
          />
        </div>
      </div>

      <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-[0px_4px_12px_rgba(0,0,0,0.05)]">
        <div class="border-b border-outline-variant p-md">
          <h2 class="font-headline-md text-headline-md text-on-background">Flagged Comments</h2>
        </div>

        <div v-if="isPending" class="p-md font-body-sm text-body-sm text-on-surface-variant">Loading flagged comments...</div>
        <div v-else-if="isError" class="flex items-center gap-sm p-md font-body-sm text-body-sm text-destructive">
          <AlertTriangle class="h-4 w-4" />
          Failed to load comments. Please refresh.
        </div>
        <div v-else-if="!filteredComments.length" class="flex flex-col items-center justify-center p-xl text-center">
          <Flag class="mb-sm h-10 w-10 text-secondary" />
          <p class="font-label-md text-label-md text-on-background">No flagged comments</p>
          <p class="font-body-sm text-body-sm text-on-surface-variant">{{ search ? 'No results match your search.' : 'No comments have been flagged for review.' }}</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full border-collapse text-left">
            <thead>
              <tr class="border-b border-outline-variant bg-surface">
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Author</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Comment</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Job</th>
                <th class="p-md font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Flagged At</th>
                <th class="p-md text-right font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
              <tr v-for="comment in filteredComments" :key="comment.id" class="group transition-colors hover:bg-surface-container-low">
                <td class="p-md font-label-md text-label-md text-on-background">{{ comment.user?.name || 'Unknown' }}</td>
                <td class="max-w-xs p-md">
                  <p class="line-clamp-2 font-body-sm text-body-sm text-on-surface-variant">{{ comment.content }}</p>
                </td>
                <td class="p-md font-body-sm text-body-sm text-on-surface-variant">
                  {{ comment.job_listing?.title || '—' }}
                </td>
                <td class="p-md font-body-sm text-body-sm text-on-surface-variant">
                  {{ comment.created_at ? new Date(comment.created_at).toLocaleDateString() : '—' }}
                </td>
                <td class="p-md text-right">
                  <div class="flex items-center justify-end gap-xs">
                    <Button
                      size="sm"
                      variant="outline"
                      :disabled="unflagMutation.isPending"
                      @click="unflagMutation.mutate(comment.id)"
                    >
                      <XCircle class="h-4 w-4" aria-hidden="true" />
                      Dismiss Flag
                    </Button>
                    <Button
                      size="sm"
                      variant="outline"
                      class="border-destructive text-destructive hover:bg-destructive/10"
                      :disabled="deleteMutation.isPending"
                      @click="confirmDelete(comment)"
                    >
                      <Trash2 class="h-4 w-4" aria-hidden="true" />
                      Delete
                    </Button>
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
