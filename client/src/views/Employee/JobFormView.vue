<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/vue-query";
import { ArrowLeft, Save } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { toast } from "vue-sonner";
import { getCategoriesApi } from "@/api/jobs";
import api from "@/api/api";

const route = useRoute();
const router = useRouter();
const queryClient = useQueryClient();

const isEdit = computed(() => Boolean(route.params.id));
const jobId = computed(() => (isEdit.value ? Number(route.params.id) : null));

const form = ref({
  title: "",
  description: "",
  requirements: "",
  benefits: "",
  location: "",
  salary_min: null as number | null,
  salary_max: null as number | null,
  work_type: "full-time",
  deadline: "",
  category_id: null as number | null,
});

const workTypes = [
  { label: "Full-time", value: "full-time" },
  { label: "Part-time", value: "part-time" },
  { label: "Remote", value: "remote" },
  { label: "Contract", value: "contract" },
  { label: "Freelance", value: "freelance" },
];

const { data: categoriesData } = useQuery({
  queryKey: ["categories"],
  queryFn: getCategoriesApi,
});
const categories = computed(() => categoriesData.value?.data ?? []);

// Load existing job data for editing
const { data: existingJob } = useQuery({
  queryKey: ["employer-job", jobId],
  queryFn: async () => {
    if (!jobId.value) return null;
    const res = await api.get(`/employer/jobs/${jobId.value}`);
    return res.data?.data ?? res.data;
  },
  enabled: isEdit,
});

watch(existingJob, (job) => {
  if (job) {
    form.value = {
      title: job.title || "",
      description: job.description || "",
      requirements: job.requirements || "",
      benefits: job.benefits || "",
      location: job.location || "",
      salary_min: job.salary_min ?? null,
      salary_max: job.salary_max ?? null,
      work_type: job.work_type || "full-time",
      deadline: job.deadline || "",
      category_id: job.category?.id ?? null,
    };
  }
});

const saveMutation = useMutation({
  mutationFn: async () => {
    const payload = { ...form.value };
    if (isEdit.value && jobId.value) {
      const res = await api.put(`/employer/jobs/${jobId.value}`, payload);
      return res.data;
    }
    const res = await api.post("/employer/jobs", payload);
    return res.data;
  },
  onSuccess: () => {
    toast.success(isEdit.value ? "Job updated successfully!" : "Job created successfully!");
    queryClient.invalidateQueries({ queryKey: ["employer", "jobs"] });
    router.push("/employer/dashboard");
  },
  onError: (err: any) => {
    const msg = err?.response?.data?.message || "Failed to save job.";
    toast.error(msg);
  },
});

function handleSubmit() {
  if (!form.value.title.trim()) {
    toast.error("Job title is required.");
    return;
  }
  if (!form.value.description.trim()) {
    toast.error("Job description is required.");
    return;
  }
  saveMutation.mutate();
}
</script>

<template>
  <div class="min-h-screen bg-background text-on-background">
    <main class="mx-auto w-full max-w-container-max flex-1 p-md md:p-lg">
      <button
        class="mb-md flex items-center gap-1.5 text-sm text-on-surface-variant transition-colors hover:text-on-surface"
        @click="router.back()"
      >
        <ArrowLeft class="h-4 w-4" />
        Back
      </button>

      <div class="mb-xl">
        <h1 class="font-headline-lg text-headline-lg text-on-background">
          {{ isEdit ? "Edit Job Listing" : "Post a New Job" }}
        </h1>
        <p class="mt-1 font-body-md text-body-md leading-relaxed text-on-surface-variant">
          {{ isEdit ? "Update your job listing details." : "Fill in the details to create a new job posting." }}
        </p>
      </div>

      <form class="space-y-lg" @submit.prevent="handleSubmit">
        <!-- Basic Info -->
        <section class="rounded-xl border border-outline-variant bg-surface-container-lowest p-md shadow-sm">
          <h2 class="mb-md font-headline-md text-headline-md text-on-background">Basic Information</h2>
          <div class="space-y-md">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-title">Job Title *</label>
              <input
                id="job-title"
                v-model="form.title"
                type="text"
                required
                placeholder="e.g. Senior Frontend Developer"
                class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>

            <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-category">Category</label>
                <select
                  id="job-category"
                  v-model.number="form.category_id"
                  class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option :value="null">Select a category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-work-type">Work Type *</label>
                <select
                  id="job-work-type"
                  v-model="form.work_type"
                  required
                  class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option v-for="wt in workTypes" :key="wt.value" :value="wt.value">{{ wt.label }}</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-location">Location</label>
                <input
                  id="job-location"
                  v-model="form.location"
                  type="text"
                  placeholder="e.g. New York, NY or Remote"
                  class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-deadline">Application Deadline</label>
                <input
                  id="job-deadline"
                  v-model="form.deadline"
                  type="date"
                  class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 gap-md sm:grid-cols-2">
              <div>
                <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-salary-min">Minimum Salary</label>
                <input
                  id="job-salary-min"
                  v-model.number="form.salary_min"
                  type="number"
                  min="0"
                  placeholder="e.g. 50000"
                  class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                />
              </div>
              <div>
                <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-salary-max">Maximum Salary</label>
                <input
                  id="job-salary-max"
                  v-model.number="form.salary_max"
                  type="number"
                  min="0"
                  placeholder="e.g. 80000"
                  class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                />
              </div>
            </div>
          </div>
        </section>

        <!-- Description & Details -->
        <section class="rounded-xl border border-outline-variant bg-surface-container-lowest p-md shadow-sm">
          <h2 class="mb-md font-headline-md text-headline-md text-on-background">Job Details</h2>
          <div class="space-y-md">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-description">Description *</label>
              <textarea
                id="job-description"
                v-model="form.description"
                rows="6"
                required
                placeholder="Describe the role, responsibilities, and what makes it exciting..."
                class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-requirements">Requirements</label>
              <textarea
                id="job-requirements"
                v-model="form.requirements"
                rows="4"
                placeholder="List the skills, experience, and qualifications needed..."
                class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-sm font-medium text-on-surface" for="job-benefits">Benefits</label>
              <textarea
                id="job-benefits"
                v-model="form.benefits"
                rows="4"
                placeholder="Health insurance, flexible hours, stock options..."
                class="w-full rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
          </div>
        </section>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
          <Button variant="outline" class="rounded-lg" type="button" @click="router.back()">Cancel</Button>
          <Button
            class="gap-2 rounded-lg"
            type="submit"
            :disabled="saveMutation.isPending.value"
          >
            <Save class="h-4 w-4" />
            {{ saveMutation.isPending.value ? "Saving..." : isEdit ? "Update Job" : "Post Job" }}
          </Button>
        </div>
      </form>
    </main>
  </div>
</template>
