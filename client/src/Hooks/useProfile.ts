import { useQuery } from "@tanstack/vue-query";
import { getProfileApi } from "@/api/profile";

export function useProfile(enabled = true) {
	return useQuery({
		queryKey: ["profile", "me"],
		queryFn: getProfileApi,
		enabled,
		staleTime: 1000 * 60 * 5,
		retry: false,
	});
}
