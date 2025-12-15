import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [vue(), tailwindcss()],
    server: {
        proxy: {
            // Catalog
            "/api/v1/products": {
                target: "http://localhost:8001",
                changeOrigin: true,
            },
            // Checkout
            "/api/v1/orders": {
                target: "http://localhost:8002",
                changeOrigin: true,
            },
        },
    },
})
