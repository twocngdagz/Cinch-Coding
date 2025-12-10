<template>
  <header class="bg-bg shadow border-border border-b sticky top-0 z-10">
    <div class="max-w-screen-xl mx-auto p-6 flex items-center justify-between">
      <router-link
        to="/"
        class="text-xl font-semibold tracking-tight hover:text-fg/80"
      >
        Cinch Store
      </router-link>
      <nav class="flex items-center">
        <router-link to="/" class="text-base font-medium hover:text-fg/80 ml-6"
          >Products</router-link>
        <router-link to="/cart" class="text-base font-medium hover:text-fg/80 ml-6"
          >Cart</router-link>
      </nav>
    </div>
  </header>
</template>

<script setup lang="ts">
// No script logic needed
</script>
