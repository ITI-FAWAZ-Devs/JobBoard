<script setup lang="ts">
import { computed } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { Activity, ArrowLeft, Home, SearchX } from "lucide-vue-next";
import { Button } from "@/components/ui/button";

const route = useRoute();
const router = useRouter();

const requestedPath = computed(() => {
	const path = typeof route.fullPath === "string" ? route.fullPath : "/";
	return path || "/";
});

const handleBack = () => {
	if (window.history.length > 1) {
		router.back();
		return;
	}

	router.push("/");
};
</script>

<template>
	<div class="relative h-screen overflow-hidden bg-gradient-subtle">

		<main class="relative mx-auto flex max-w-7xl items-center px-6 py-12">
			<section class="w-full">

				<div class="mx-auto max-w-2xl rounded-3xl border bg-background/80 p-8 shadow-soft backdrop-blur-xl md:p-10">
					<div class="flex flex-col items-center text-center">
						<div class="mb-5 inline-flex items-center gap-2 rounded-full border bg-muted/40 px-4 py-2 text-xs font-medium text-muted-foreground">
							<SearchX class="h-4 w-4" />
							لم يتم العثور على الصفحة
						</div>

						<div class="text-7xl font-black tracking-tight text-transparent bg-clip-text bg-gradient-primary md:text-8xl">
							404
						</div>

						<h1 class="mt-3 text-balance text-2xl font-bold md:text-3xl">الصفحة غير موجودة</h1>
						<p class="mt-2 max-w-prose text-sm leading-relaxed text-muted-foreground">
							يبدو أن الرابط غير صحيح أو أن الصفحة تم نقلها. يمكنك العودة للرئيسية أو الرجوع للصفحة السابقة.
						</p>

						<div class="mt-6 w-full rounded-2xl border bg-muted/20 p-4 text-right">
							<div class="text-xs font-medium text-muted-foreground">الرابط المطلوب</div>
							<div class="mt-1 break-all rounded-lg bg-background px-3 py-2 text-sm font-semibold">
								{{ requestedPath }}
							</div>
						</div>

						<div class="mt-8 flex w-full flex-col gap-3 sm:flex-row sm:justify-center">
							<RouterLink to="/" class="w-full sm:w-auto">
								<Button size="lg" class="w-full gap-2 shadow-elegant cursor-pointer">
									العودة للرئيسية
									<Home class="h-4 w-4" />
								</Button>
							</RouterLink>

							<Button
								type="button"
								variant="outline"
								size="lg"
								class="w-full gap-2 sm:w-auto cursor-pointer"
								@click="handleBack"
							>
								رجوع
								<ArrowLeft class="h-4 w-4" />
							</Button>
						</div>
					</div>
				</div>
			</section>
		</main>
	</div>
</template>
