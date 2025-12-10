<template>
  <div class="min-h-screen flex flex-col bg-bg">
    <AppHeader />

    <!-- Route outlet MUST be here -->
    <main class="flex-1 max-w-screen-xl mx-auto p-6">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import AppHeader from "../components/AppHeader.vue";
import { RouterView } from "vue-router";
</script>
