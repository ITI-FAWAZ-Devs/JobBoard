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

      const user = res?.data?.user;
      if (user) {
        localStorage.setItem("user", JSON.stringify(user));
      }
    },

    onError: (err) => {
      console.error(err);
    },
  });
}
