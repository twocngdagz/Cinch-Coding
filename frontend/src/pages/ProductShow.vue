<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCart } from '../stores/cart'

const route = useRoute()
const router = useRouter()
const product = ref<any>(null)
const loading = ref(true)
const error = ref('')

const selectedSize = ref('')
const selectedColor = ref('')

const sizes = ref<string[]>([])
const colors = ref<string[]>([])
const filteredVariants = ref<any[]>([])
const selectedVariant = ref<any>(null)

const cart = useCart()

function updateOptions() {
  if (!product.value?.data?.variants) return
  const allSizes = new Set<string>()
  const allColors = new Set<string>()
  for (const v of product.value.data.variants) {
    if (v.options?.size) allSizes.add(v.options.size)
    if (v.options?.color) allColors.add(v.options.color)
  }
  sizes.value = Array.from(allSizes)
  colors.value = Array.from(allColors)
}

function updateFilteredVariants() {
  if (!product.value?.data?.variants) return
  filteredVariants.value = product.value.data.variants.filter((v: any) => {
    return (!selectedSize.value || v.options?.size === selectedSize.value) &&
           (!selectedColor.value || v.options?.color === selectedColor.value)
  })
  selectedVariant.value = filteredVariants.value[0] || null
}

function onSelectSize(size: string) {
  selectedSize.value = size
  updateFilteredVariants()
}

function onSelectColor(color: string) {
  selectedColor.value = color
  updateFilteredVariants()
}

function add() {
  let price = selectedVariant.value?.price ?? product.value.data.variants?.[0]?.price ?? 0;
  price = typeof price === 'string' ? parseFloat(price) : price;
  if (isNaN(price)) price = 0;
  cart.addToCart({
    id: product.value.data.id,
    name: product.value.data.title,
    price,
    quantity: 1,
    image: product.value.data.images?.[0] ?? '',
    color: selectedColor.value || undefined,
    size: selectedSize.value || undefined
  })
  router.push('/cart')
}

onMounted(async () => {
  try {
    const res = await fetch(`${import.meta.env.VITE_CATALOG_URL}/products/${route.params.id}`)
    if (!res.ok) throw new Error('Product not found')
    product.value = await res.json()
    updateOptions()
    updateFilteredVariants()
  } catch (e: any) {
    error.value = e.message || 'Error loading product'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="max-w-7xl mx-auto p-6">
    <nav class="mb-6 flex items-center gap-2 text-sm text-fg/80">
      <a href="/" class="hover:text-fg">Home</a>
      <span class="mx-2 text-fg/60">/</span>
      <a href="/products" class="hover:text-fg">Products</a>
      <span class="mx-2 text-fg/60">/</span>
      <span class="text-fg-emphasis">{{ product?.data?.title || 'Product' }}</span>
    </nav>
    <div v-if="loading" class="flex justify-center items-center h-96 text-base text-fg/80">Loading...</div>
    <div v-else-if="error" class="flex justify-center items-center h-96 text-base text-fg-emphasis">{{ error }}</div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="flex flex-col gap-4">
        <div v-if="product?.data?.images?.length" class="aspect-square bg-bg rounded-l shadow-m overflow-hidden">
          <img :src="product.data.images[0]" alt="Product image" class="w-full h-full object-cover rounded-l" />
        </div>
        <div v-else class="aspect-square bg-bg/50 rounded-l shadow-m flex items-center justify-center">
          <span class="text-fg/60 text-lg">No image</span>
        </div>
      </div>
      <div class="flex flex-col gap-6">
        <h1 class="text-xl font-semibold text-fg-emphasis">{{ product.data.title }}</h1>
        <div class="text-xl font-semibold text-fg">
          ${{ selectedVariant?.price ?? product.data.variants?.[0]?.price ?? 'N/A' }}
        </div>
        <div class="text-base text-fg/80">{{ product.data.description }}</div>
        <div v-if="sizes.length" class="flex flex-col gap-2">
          <span class="text-sm text-fg-emphasis">Size</span>
          <div class="flex gap-2">
            <button
              v-for="size in sizes"
              :key="size"
              type="button"
              @click="onSelectSize(size)"
              :class="[
                'px-4 py-2 rounded-s border border-border text-base text-fg bg-bg hover:bg-bg/80 focus-visible:outline focus-visible:outline-border',
                selectedSize === size ? 'bg-bg-emphasis border-border font-semibold' : ''
              ]"
            >
              {{ size }}
            </button>
          </div>
        </div>
        <div v-if="colors.length" class="flex flex-col gap-2">
          <span class="text-sm text-fg-emphasis">Color</span>
          <div class="flex gap-2">
            <button
              v-for="color in colors"
              :key="color"
              type="button"
              @click="onSelectColor(color)"
              :class="[
                'px-4 py-2 rounded-full border border-border text-base text-fg bg-bg hover:bg-bg/80 focus-visible:outline focus-visible:outline-border',
                selectedColor === color ? 'bg-bg-emphasis border-border font-semibold' : ''
              ]"
            >
              {{ color }}
            </button>
          </div>
        </div>
        <button
          @click="add"
          class="mt-8 flex w-full items-center justify-center rounded-md bg-indigo-600 px-8 py-3 text-base font-medium text-white hover:bg-indigo-700">
          Add to cart
        </button>
        <div class="mt-6 border-t border-border/50 pt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-bg-muted rounded-s p-4 border border-border/50 text-sm text-fg/80">
            Free shipping on orders over $50
          </div>
          <div class="bg-bg-muted rounded-s p-4 border border-border/50 text-sm text-fg/80">
            30-day return policy
          </div>
          <div class="bg-bg-muted rounded-s p-4 border border-border/50 text-sm text-fg/80">
            Secure checkout
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
