import { ref, computed } from 'vue'

export type CartItem = {
  id: number
  name: string
  price: number
  quantity: number
  image: string
  color?: string
  size?: string
}

const items = ref<CartItem[]>([])

export function useCart() {
  function addToCart(product: CartItem) {
    const existing = items.value.find(i => i.id === product.id)
    if (existing) {
      existing.quantity += product.quantity
      console.log('Updated quantity:', items.value)
      return
    }
    items.value.push(product)
    console.log('Added to cart:', items.value)
  }

  function removeFromCart(id: number) {
    items.value = items.value.filter(i => i.id !== id)
  }

  function updateQuantity(id: number, qty: number) {
    const item = items.value.find(i => i.id === id)
    if (item) item.quantity = qty
  }

  const subtotal = computed(() =>
    items.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
  )

  return { items, addToCart, removeFromCart, updateQuantity, subtotal }
}
