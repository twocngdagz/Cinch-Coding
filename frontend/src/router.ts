import { createRouter, createWebHistory } from "vue-router";
import ProductList from "./pages/ProductList.vue";
import ProductShow from "./pages/ProductShow.vue";

const routes = [
  { path: "/", name: "products.index", component: ProductList },
  { path: "/products/:id", name: "products.show", component: ProductShow },
  {
    path: '/cart',
    name: 'cart',
    component: () => import('./pages/CartPage.vue')
  }
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});
