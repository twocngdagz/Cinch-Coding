import { ref, computed } from 'vue'

export type CartItem = {
    productId: number
    variantId: number
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
        const existing = items.value.find(i => i.variantId === product.variantId)
        if (existing) {
            existing.quantity += product.quantity
            return
        }
        items.value.push(product)
    }

    function removeFromCart(variantId: number) {
        items.value = items.value.filter(i => i.variantId !== variantId)
    }

    function updateQuantity(variantId: number, qty: number) {
        const item = items.value.find(i => i.variantId === variantId)
        if (item) item.quantity = qty
    }

    function clear() {
        items.value = []
    }

    const subtotal = computed(() =>
        items.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
    )

    return { items, addToCart, removeFromCart, updateQuantity, clear, subtotal }
}
