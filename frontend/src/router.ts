import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import ProductList from './pages/ProductList.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: ProductList
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
