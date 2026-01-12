<template>
  <div class="flex h-screen bg-gray-100 overflow-hidden font-sans">
    <Sidebar :isCollapsed="isCollapsed" @toggle="toggleSidebar" />

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <Navbar />

      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
        <slot></slot>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from './Sidebar.vue';
import Navbar from './Navbar.vue';

const isCollapsed = ref(false);

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value;
};
</script>
