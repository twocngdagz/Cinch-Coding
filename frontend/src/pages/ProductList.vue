<template>
  <div class="bg-bg">
    <div class="mx-auto max-w-screen-xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
      <h2 class="text-2xl font-semibold tracking-tight text-fg mb-6">
        Customers also purchased
      </h2>
      <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-[4] xl:gap-x-8">
        <div v-for="product in products" :key="product.id" class="group relative">
          <img
            :src="`https://picsum.photos/seed/${product.id}/600/900`"
            alt="Product image"
            class="aspect-square w-full rounded-m bg-bg/50 object-cover group-hover:opacity-75 lg:aspect-auto lg:h-80"
          />
          <div class="mt-4 flex justify-between">
            <div>
              <h3 class="text-sm text-fg">
                <router-link :to="`/products/${product.id}`">
                  <span aria-hidden="true" class="absolute inset-0"></span>
                  {{ product.title }}
                </router-link>
              </h3>
              <p class="mt-1 text-sm text-fg/80 line-clamp-2">
                {{ product.description }}
              </p>
            </div>
            <p class="text-sm font-medium text-fg">
              {{ getPrice(product) }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";

interface Variant {
  price: number | string;
}

interface Product {
  id: number | string;
  title: string;
  slug: string;
  description: string;
  status: string;
  variants: Variant[];
}

const products = ref<Product[]>([]);

function getPrice(product: Product): string {
  if (
    Array.isArray(product.variants) &&
    product.variants.length > 0 &&
    product.variants[0]?.price !== undefined
  ) {
    return `$${product.variants[0].price}`;
  }
  return "N/A";
}

onMounted(async () => {
  try {
    const res = await fetch(`${import.meta.env.VITE_CATALOG_URL}/products`);
    if (!res.ok) throw new Error("Failed to fetch products");
    const json = await res.json();
    products.value = json.data;
  } catch (e) {
    products.value = [];
  }
});
</script>
