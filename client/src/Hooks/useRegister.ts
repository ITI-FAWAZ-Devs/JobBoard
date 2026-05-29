import { registerApi } from "@/api/auth";
import { useMutation } from "@tanstack/vue-query";

export function useRegister() {
  return useMutation({
    mutationFn: registerApi,

    onSuccess: (res) => {
      const token = res?.data?.token;
      if (token) {
        localStorage.setItem("token", token);
      }
    },

    onError: (err) => {
      console.error(err);
    },
  });
}
