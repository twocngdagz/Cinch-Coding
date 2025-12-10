<script setup lang="ts">
import { ref } from "vue";

defineProps<{ msg: string }>();

const count = ref(0);
</script>

<template>
  <h1>{{ msg }}</h1>
  <div class="rounded-m shadow bg-bg p-4">
    <button
      type="button"
      @click="count++"
      class="text-base font-medium text-fg p-2 rounded-s bg-bg border border-border hover:bg-bg/80"
    >
      count is {{ count }}
    </button>
    <p class="text-sm text-fg/80 mt-2">
      Edit
      <code>components/HelloWorld.vue</code> to test HMR
    </p>
  </div>
  <p class="text-sm text-fg mt-4">
    Check out
    <a
      href="https://vuejs.org/guide/quick-start.html#local"
      target="_blank"
      class="text-fg hover:text-fg/80"
      >create-vue</a
    >
    , the official Vue + Vite starter
  </p>
  <p class="text-sm text-fg mt-2">
    Learn more about IDE Support for Vue in the
    <a
      href="https://vuejs.org/guide/scaling-up/tooling.html#ide-support"
      target="_blank"
      class="text-fg hover:text-fg/80"
      >Vue Docs Scaling up Guide</a
    >
    .
  </p>
  <p class="text-xs text-fg/80 mt-2">Click on the Vite and Vue logos to learn more</p>
</template>

<style scoped>
.read-the-docs {
  color: #888;
}
</style>
