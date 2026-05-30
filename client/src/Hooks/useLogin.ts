import { loginApi } from "@/api/auth";
import { useMutation } from "@tanstack/vue-query";
import { useAuth } from "@/composables/useAuth";

export function useLogin() {
  const { fetchUserProfile } = useAuth();

  return useMutation({
    mutationFn: loginApi,

    onSuccess: async (res) => {
      const token = res?.data?.token;
      if (token) {
        localStorage.setItem("token", token);
        // Fetch user profile after successful login
        try {
          await fetchUserProfile();
        } catch (error) {
          console.error("Failed to fetch profile after login:", error);
        }
      }
    },

    onError: (err) => {
      console.error(err);
    },
  });
}
