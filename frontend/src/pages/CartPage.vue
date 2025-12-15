<template>
  <div class="bg-white">
    <div class="mx-auto max-w-2xl px-4 pt-16 pb-24 sm:px-6 lg:max-w-7xl lg:px-8">
      <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Shopping Cart</h1>

      <form class="mt-12 lg:grid lg:grid-cols-12 lg:items-start lg:gap-x-12 xl:gap-x-16">
        <section aria-labelledby="cart-heading" class="lg:col-span-7">
          <h2 id="cart-heading" class="sr-only">Items in your shopping cart</h2>

          <ul role="list" class="divide-y divide-gray-200 border-t border-b border-gray-200">
            <li
              v-for="item in cart.items.value"
              :key="item.productId"
              class="flex py-6 sm:py-10"
            >
              <div class="shrink-0">
                <img
                  :src="item.image"
                  :alt="item.name"
                  class="size-24 rounded-md object-cover sm:size-48"
                />
              </div>

              <div class="ml-4 flex flex-1 flex-col justify-between sm:ml-6">
                <div class="relative pr-9 sm:grid sm:grid-cols-2 sm:gap-x-6 sm:pr-0">
                  <div>
                    <div class="flex justify-between">
                      <h3 class="text-sm font-medium text-gray-700">
                        {{ item.name }}
                      </h3>
                    </div>

                    <p class="mt-1 text-sm font-medium text-gray-900">
                      ${{ item.price.toFixed(2) }}
                    </p>
                  </div>

                  <div class="mt-4 sm:mt-0 sm:pr-9">
                    <div class="grid w-full max-w-16 grid-cols-1">
                      <select
                        :value="item.quantity"
                        @change="update(item.productId, Number(($event.target as HTMLSelectElement).value))"
                        class="col-start-1 row-start-1 appearance-none rounded-md bg-white py-1.5 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                      >
                        <option v-for="n in 8" :key="n" :value="n">{{ n }}</option>
                      </select>
                    </div>

                    <div class="absolute top-0 right-0">
                      <button
                        type="button"
                        @click="cart.removeFromCart(item.productId)"
                        class="-m-2 inline-flex p-2 text-gray-400 hover:text-gray-500"
                      >
                        <svg viewBox="0 0 20 20" fill="currentColor" class="size-5">
                          <path
                            d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"
                          />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>

                <p class="mt-4 flex space-x-2 text-sm text-gray-700">
                  <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 text-green-500">
                    <path
                      d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                    />
                  </svg>
                  <span>In stock</span>
                </p>
              </div>
            </li>
          </ul>
        </section>

        <section
          aria-labelledby="summary-heading"
          class="mt-16 rounded-lg bg-gray-50 px-4 py-6 sm:p-6 lg:col-span-5 lg:mt-0 lg:p-8"
        >
          <h2 id="summary-heading" class="text-lg font-medium text-gray-900">Order summary</h2>

          <dl class="mt-6 space-y-4">
            <div class="flex items-center justify-between">
              <dt class="text-sm text-gray-600">Subtotal</dt>
              <dd class="text-sm font-medium text-gray-900">
                ${{ cart.subtotal.value.toFixed(2) }}
              </dd>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
              <dt class="text-base font-medium text-gray-900">Order total</dt>
              <dd class="text-base font-medium text-gray-900">
                ${{ cart.subtotal.value.toFixed(2) }}
              </dd>
            </div>
          </dl>

            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700">Email</label>
              <input
                v-model="email"
                type="email"
                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"
                placeholder="you@example.com"
              />
              <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>

              <button
                type="button"
                :disabled="submitting"
                @click="placeOrder"
                class="mt-4 w-full rounded-md border border-transparent bg-indigo-600 px-4 py-3 text-base font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
              >
                {{ submitting ? 'Placing order...' : 'Checkout' }}
              </button>
            </div>

        </section>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCart } from '../stores/cart'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const cart = useCart()
const router = useRouter()

const email = ref('')
const submitting = ref(false)
const error = ref('')

async function placeOrder() {
    error.value = ''
    if (!email.value) {
        error.value = 'Email is required.'
        return
    }
    if (cart.items.value.length === 0) {
        error.value = 'Cart is empty.'
        return
    }

    submitting.value = true
    try {
        const payload = {
            email: email.value,
            items: cart.items.value.map(i => ({
                product_id: i.productId,
                variant_id: i.variantId,
                quantity: i.quantity,
            })),
        }

        const res = await fetch(`${import.meta.env.VITE_API_BASE}/orders`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })

        if (!res.ok) {
            const data = await res.json().catch(() => null)
            throw new Error(data?.message || 'Checkout failed')
        }

        cart.clear()
        router.push('/')
    } catch (e: any) {
        error.value = e?.message || 'Checkout failed'
    } finally {
        submitting.value = false
    }
}


function update(id: number, qty: number) {
  cart.updateQuantity(id, Number(qty))
}
</script>
