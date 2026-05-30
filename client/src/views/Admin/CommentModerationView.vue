<script setup lang="ts">
import { computed, ref } from "vue";
import { useMutation, useQuery, useQueryClient } from "@tanstack/vue-query";
import { EyeOff, Eye, Trash2, Filter, AlertTriangle } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import {
  getAdminCommentsApi,
  hideCommentApi,
  deleteCommentApi,
  type Comment,
} from "@/api/admin";

const queryClient = useQueryClient();
const page = ref(1);
const showOnlyReported = ref(false);

const { data, isPending, isError } = useQuery({
  queryKey: ["admin", "comments", page, showOnlyReported],
  queryFn: () => getAdminCommentsApi(page.value, showOnlyReported.value),
  keepPreviousData: true,
});

const comments = computed<Comment[]>(() => data.value?.data?.data ?? []);

const hideMutation = useMutation({
  mutationFn: (commentId: number) => hideCommentApi(commentId),
  onSuccess: () => {
    toast.success("Comment visibility updated.");
    queryClient.invalidateQueries({ queryKey: ["admin", "comments"] });
  },
  onError: () => {
    toast.error("Failed to update visibility.");
  }
});

const deleteMutation = useMutation({
  mutationFn: (commentId: number) => deleteCommentApi(commentId),
  onSuccess: () => {
    toast.success("Comment deleted permanently.");
    queryClient.invalidateQueries({ queryKey: ["admin", "comments"] });
  },
  onError: () => {
    toast.error("Failed to delete comment.");
  }
});

const confirmDelete = (comment: Comment) => {
  if (window.confirm("Are you sure you want to delete this comment? This cannot be undone.")) {
    deleteMutation.mutate(comment.id);
  }
};
</script>

<template>
  <div>
    <div class="mb-md flex flex-wrap items-center justify-end gap-sm">
      <Button
        variant="outline"
        @click="showOnlyReported = !showOnlyReported"
        :class="showOnlyReported ? 'bg-primary/10 text-primary border-primary' : ''"
      >
        <Filter class="h-4 w-4 mr-2" aria-hidden="true" />
        {{ showOnlyReported ? 'Showing Reported' : 'Filter Reported' }}
      </Button>
    </div>

    <section class="rounded-2xl border border-outline-variant bg-card shadow-soft">
      <header class="flex items-center justify-between border-b border-outline-variant px-lg py-md">
        <div>
          <h2 class="text-lg font-semibold text-on-surface">Comments</h2>
          <p class="text-sm text-on-surface-variant">Manage discussion across all job listings.</p>
        </div>
        <div class="text-sm text-on-surface-variant">
          {{ data?.data?.meta?.total ?? comments.length }} results
        </div>
      </header>

      <div v-if="isPending" class="px-lg py-lg text-sm text-on-surface-variant">
        Loading comments...
      </div>
      <div v-else-if="isError" class="px-lg py-lg text-sm text-destructive">
        Failed to load comments. Please refresh.
      </div>
      <div v-else-if="!comments.length" class="px-lg py-lg text-sm text-on-surface-variant">
        No comments found.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-outline-variant text-sm">
          <thead class="bg-surface-container-lowest text-left text-xs uppercase tracking-wide text-on-surface-variant">
            <tr>
              <th class="px-lg py-sm font-medium">Author / Content</th>
              <th class="px-lg py-sm font-medium">Job Listing</th>
              <th class="px-lg py-sm font-medium">Status</th>
              <th class="px-lg py-sm text-right font-medium">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-outline-variant">
            <tr v-for="comment in comments" :key="comment.id" class="hover:bg-surface-container-low transition-colors duration-200">
              <td class="px-lg py-md max-w-md">
                <div class="font-semibold text-on-surface mb-1 flex items-center gap-2">
                  {{ comment.user?.name || 'Unknown User' }}
                  <AlertTriangle v-if="comment.is_reported" class="h-4 w-4 text-destructive" />
                </div>
                <div class="text-on-surface-variant line-clamp-2">{{ comment.content }}</div>
                <div class="text-xs text-on-surface-variant mt-1">
                  {{ comment.created_at ? new Date(comment.created_at).toLocaleString() : "" }}
                </div>
              </td>
              <td class="px-lg py-md text-on-surface-variant">
                <span class="font-medium text-primary cursor-pointer hover:underline">
                  {{ comment.job_listing?.title || 'Unknown Job' }}
                </span>
              </td>
              <td class="px-lg py-md">
                <div class="flex flex-col gap-1 items-start">
                  <span
                    v-if="comment.is_reported"
                    class="rounded-full bg-destructive/10 text-destructive px-sm py-[2px] text-xs font-medium"
                  >
                    Reported
                  </span>
                  <span
                    v-if="comment.is_hidden"
                    class="rounded-full bg-warning/10 text-warning px-sm py-[2px] text-xs font-medium"
                  >
                    Hidden
                  </span>
                  <span
                    v-if="!comment.is_reported && !comment.is_hidden"
                    class="rounded-full bg-secondary/10 text-secondary px-sm py-[2px] text-xs font-medium"
                  >
                    Visible
                  </span>
                </div>
              </td>
              <td class="px-lg py-md">
                <div class="flex items-center justify-end gap-xs">
                  <Button
                    size="sm"
                    variant="outline"
                    :disabled="hideMutation.isPending"
                    @click="hideMutation.mutate(comment.id)"
                  >
                    <Eye v-if="comment.is_hidden" class="h-4 w-4" aria-hidden="true" />
                    <EyeOff v-else class="h-4 w-4" aria-hidden="true" />
                    {{ comment.is_hidden ? 'Unhide' : 'Hide' }}
                  </Button>
                  <Button
                    size="sm"
                    variant="destructive"
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
        
        <!-- Basic Pagination Controls -->
        <div class="flex items-center justify-between border-t border-outline-variant px-lg py-sm" v-if="data?.data?.meta && data.data.meta.last_page > 1">
          <Button
            variant="outline"
            size="sm"
            :disabled="page === 1"
            @click="page--"
          >
            Previous
          </Button>
          <span class="text-sm text-on-surface-variant">
            Page {{ page }} of {{ data.data.meta.last_page }}
          </span>
          <Button
            variant="outline"
            size="sm"
            :disabled="page >= data.data.meta.last_page"
            @click="page++"
          >
            Next
          </Button>
        </div>
      </div>
    </section>
  </div>
</template>
