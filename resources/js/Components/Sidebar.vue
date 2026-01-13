<template>
  <aside
    class="bg-[#4c1d95] text-white transition-all duration-300 flex flex-col shadow-xl z-50"
    :class="isCollapsed ? 'w-20' : 'w-64'"
  >
    <div class="h-20 flex items-center justify-center border-b border-purple-800 bg-[#2e1065] shadow-sm relative overflow-hidden">
      <div class="absolute top-0 left-0 w-full h-full bg-white opacity-5 pointer-events-none"></div>
      <div v-if="!isCollapsed" class="flex items-center gap-3 px-4 animate-fade-in transition-all z-10">
        <div class="bg-white p-1.5 rounded-lg shadow-lg flex items-center justify-center">
            <img src="/images/logo.png" alt="PEA PM" class="h-9 w-auto object-contain" />
        </div>
        <div class="flex flex-col">
            <span class="font-bold text-lg leading-tight tracking-wide text-white drop-shadow-sm">PEA Smart</span>
            <span class="text-[10px] text-purple-200 tracking-wider uppercase font-medium">Project Management</span>
        </div>
      </div>
      <div v-else class="z-10">
         <div class="bg-white p-1.5 rounded-lg shadow-lg">
            <img src="/images/logo.png" alt="PEA" class="h-8 w-8 object-contain" />
         </div>
      </div>
    </div>

    <nav class="flex-1 py-6 space-y-1 overflow-y-auto">
      <router-link to="/dashboard" class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700 transition-colors border-l-4 border-transparent hover:border-white group" active-class="bg-purple-800 border-l-4 border-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
        <span v-if="!isCollapsed">ภาพรวมโครงการ</span>
      </router-link>

      <router-link to="/projects" class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700 transition-colors border-l-4 border-transparent hover:border-white group" active-class="bg-purple-800 border-l-4 border-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
        <span v-if="!isCollapsed">จัดการโครงการ</span>
      </router-link>

      <div v-if="isAdmin" class="pt-4 mt-4 border-t border-purple-800">
        <div v-if="!isCollapsed" class="px-6 mb-2 text-xs font-bold text-purple-300 uppercase tracking-wider">
            ผู้ดูแลระบบ
        </div>

        <router-link to="/programs" class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700 transition-colors border-l-4 border-transparent hover:border-white group" active-class="bg-purple-800 border-l-4 border-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
            <span v-if="!isCollapsed">จัดการแผนงาน (Programs)</span>
        </router-link>

        <router-link to="/users" class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700 transition-colors border-l-4 border-transparent hover:border-white group" active-class="bg-purple-800 border-l-4 border-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            <span v-if="!isCollapsed">จัดการผู้ใช้งาน (Users)</span>
        </router-link>
      </div>
    </nav>

    <div class="p-4 border-t border-purple-800 bg-[#4c1d95]">
      <button @click="$emit('toggle')" class="w-full flex items-center justify-center p-2 rounded hover:bg-purple-800 transition-colors text-purple-200 hover:text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transition-transform duration-300" :class="isCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted } from 'vue';

defineProps(['isCollapsed']);
defineEmits(['toggle']);

const isAdmin = ref(false);

onMounted(() => {
    // เช็คสิทธิ์จาก LocalStorage (ง่ายและเร็ว)
    const user = JSON.parse(localStorage.getItem('user_info') || '{}');
    isAdmin.value = user.role === 'admin';
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
</style>
