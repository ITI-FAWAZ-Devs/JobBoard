import { loginApi } from "@/api/auth";
import { useMutation } from "@tanstack/vue-query";

export function useLogin() {
  return useMutation({
    mutationFn: loginApi,

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
