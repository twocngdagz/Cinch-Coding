import { createRouter, createWebHistory } from "vue-router";
import ProductList from "./pages/ProductList.vue";

const routes = [{ path: "/", name: "products.index", component: ProductList }];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});
