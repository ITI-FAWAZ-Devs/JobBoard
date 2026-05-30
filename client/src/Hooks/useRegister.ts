import { registerApi } from "@/api/auth";
import { useMutation } from "@tanstack/vue-query";
import { useAuth } from "@/composables/useAuth";

export function useRegister() {
  const { fetchUserProfile } = useAuth();

  return useMutation({
    mutationFn: registerApi,

    onSuccess: async (res) => {
      const token = res?.data?.token;
      if (token) {
        localStorage.setItem("token", token);
        // Fetch user profile after successful registration
        try {
          await fetchUserProfile();
        } catch (error) {
          console.error("Failed to fetch profile after registration:", error);
        }
      }
    },

    onError: (err) => {
      console.error(err);
    },
  });
}
