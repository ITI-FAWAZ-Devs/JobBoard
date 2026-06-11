import { createApp } from "vue";
import { VueQueryPlugin } from "@tanstack/vue-query";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import "./styles/globals.css";

createApp(App).use(router).use(VueQueryPlugin).use(createPinia()).mount("#app");