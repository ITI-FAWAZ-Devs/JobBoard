import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { toast } from "vue-sonner";
import {
  getPendingJobsApiV2,
  approveJobApi,
  rejectJobApi,
  type JobListing,
  type Paginated,
} from "@/api/admin";

export const useAdminJobsStore = defineStore("adminJobs", () => {
  const jobs = ref<JobListing[]>([]);
  const meta = ref<Paginated<JobListing>["meta"] | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const actionLoading = ref<Record<number, boolean>>({});

  const totalPending = computed(() => meta.value?.total ?? jobs.value.length);

  async function fetchJobs(page = 1) {
    loading.value = true;
    error.value = null;
    try {
      const res = await getPendingJobsApiV2(page);
      jobs.value = res.data.data;
      meta.value = res.data.meta ?? null;
    } catch (e: any) {
      error.value = e?.response?.data?.message || "Failed to load pending jobs";
      toast.error(error.value!);
    } finally {
      loading.value = false;
    }
  }

  async function approveJob(jobId: number) {
    actionLoading.value[jobId] = true;
    try {
      await approveJobApi(jobId);
      jobs.value = jobs.value.filter((j) => j.id !== jobId);
      toast.success("Job approved successfully");
    } catch (e: any) {
      toast.error(e?.response?.data?.message || "Failed to approve job");
    } finally {
      actionLoading.value[jobId] = false;
    }
  }

  async function rejectJob(jobId: number, reason: string) {
    actionLoading.value[jobId] = true;
    try {
      await rejectJobApi(jobId, reason);
      jobs.value = jobs.value.filter((j) => j.id !== jobId);
      toast.success("Job rejected");
    } catch (e: any) {
      toast.error(e?.response?.data?.message || "Failed to reject job");
    } finally {
      actionLoading.value[jobId] = false;
    }
  }

  return {
    jobs,
    meta,
    loading,
    error,
    actionLoading,
    totalPending,
    fetchJobs,
    approveJob,
    rejectJob,
  };
});
