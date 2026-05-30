import { computed, ref } from "vue";
import { getProfileApi } from "@/api/profile";

interface User {
  id?: string | number;
  name?: string;
  email?: string;
  role?: "admin" | "employer" | "candidate";
  [key: string]: any;
}

const user = ref<User | null>(null);
const isLoading = ref(false);
let profileFetchPromise: Promise<void> | null = null;

export function useAuth() {
  const isAuthenticated = computed(() => {
    return !!localStorage.getItem("token") && !!user.value;
  });

  const userRole = computed(() => {
    return user.value?.role || null;
  });

  const fetchUserProfile = async () => {
    // Prevent multiple simultaneous requests
    if (profileFetchPromise) {
      return profileFetchPromise;
    }

    const token = localStorage.getItem("token");
    if (!token) {
      user.value = null;
      return;
    }

    profileFetchPromise = (async () => {
      isLoading.value = true;
      try {
        const profileData = await getProfileApi();
        user.value = profileData;
      } catch (error) {
        console.error("Failed to fetch user profile:", error);
        user.value = null;
        localStorage.removeItem("token");
        throw error;
      } finally {
        isLoading.value = false;
        profileFetchPromise = null;
      }
    })();

    return profileFetchPromise;
  };

  const logout = () => {
    localStorage.removeItem("token");
    user.value = null;
  };

  const setUser = (userData: User | null) => {
    user.value = userData;
  };

  return {
    user: computed(() => user.value),
    isAuthenticated,
    userRole,
    isLoading: computed(() => isLoading.value),
    fetchUserProfile,
    logout,
    setUser,
  };
}
