<template>
  <div class="max-w-7xl mx-auto p-6">
    <h1 class="text-2xl font-semibold mb-4">Products</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="product in products" :key="product.id" class="border rounded-lg p-5 shadow-sm hover:shadow-md transition-shadow bg-white flex flex-col">
        <div class="flex items-center justify-between mb-2">
          <router-link :to="`/product/${product.id}`" class="text-lg font-semibold hover:underline block mb-1 truncate">
            {{ product.title }}
          </router-link>
          <span v-if="product.status === 'active'" class="inline-block px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">Active</span>
          <span v-else-if="product.status === 'draft'" class="inline-block px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 rounded">Draft</span>
          <span v-else-if="product.status === 'archived'" class="inline-block px-2 py-1 text-xs font-medium bg-gray-200 text-gray-600 rounded">Archived</span>
        </div>
        <div class="text-sm text-gray-600 line-clamp-2 mt-1 mb-3 min-h-[2.5em]">{{ product.description }}</div>
        <div class="mt-auto">
          <span class="text-lg font-semibold text-gray-900">{{ getPrice(product) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Variant {
  price: number | string
}

interface Product {
  id: number | string
  title: string
  slug: string
  description: string
  status: string
  variants: Variant[]
}

const products = ref<Product[]>([])

function getPrice(product: Product): string {
  if (Array.isArray(product.variants) && product.variants.length > 0 && product.variants[0]?.price !== undefined) {
    return `$${product.variants[0].price}`
  }
  return 'N/A'
}

onMounted(async () => {
  try {
    const res = await fetch(`${import.meta.env.VITE_CATALOG_URL}/products`)
    if (!res.ok) throw new Error('Failed to fetch products')
    const json = await res.json()
    products.value = json.data
  } catch (e) {
    products.value = []
  }
})
</script>
