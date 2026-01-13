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
        <span v-if="!isCollapsed">ภาพรวม (Dashboard)</span>
      </router-link>

      <div v-if="canManageProjects" class="mt-2 pt-2 border-t border-purple-800">
          <div v-if="!isCollapsed" class="px-6 mb-2 text-xs font-bold text-purple-300 uppercase tracking-wider">
             ส่วนบริหารงาน
          </div>

          <router-link to="/programs" class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700 transition-colors border-l-4 border-transparent hover:border-white group" active-class="bg-purple-800 border-l-4 border-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            <span v-if="!isCollapsed">จัดการแผนงานหลัก</span>
          </router-link>

          <router-link to="/projects" class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700 transition-colors border-l-4 border-transparent hover:border-white group" active-class="bg-purple-800 border-l-4 border-white">
             <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
             <span v-if="!isCollapsed">ทะเบียนโครงการทั้งหมด</span>
          </router-link>
      </div>

      <div class="mt-2 pt-2 border-t border-purple-800">
          <div v-if="!isCollapsed" class="px-6 mb-2 text-xs font-bold text-purple-300 uppercase tracking-wider">
             ส่วนปฏิบัติการ
          </div>

          <router-link to="/my-projects" class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700 transition-colors border-l-4 border-transparent hover:border-white group" active-class="bg-purple-800 border-l-4 border-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span v-if="!isCollapsed">โครงการของฉัน</span>
          </router-link>
      </div>

      <div v-if="isSystemAdmin" class="mt-auto">
        <div class="border-t border-purple-800 mt-2 pt-2">
            <router-link to="/users" class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700 transition-colors border-l-4 border-transparent hover:border-white group" active-class="bg-purple-800 border-l-4 border-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                <span v-if="!isCollapsed">จัดการผู้ใช้งาน</span>
            </router-link>
        </div>
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

// แยกตัวแปรเช็คสิทธิ์เพื่อให้ละเอียดขึ้น
const canManageProjects = ref(false); // สำหรับ Admin และ Program Manager
const isSystemAdmin = ref(false);     // สำหรับ Admin เท่านั้น

onMounted(() => {
    // ดึงข้อมูล User จาก LocalStorage
    const user = JSON.parse(localStorage.getItem('user_info') || '{}');

    // 1. สิทธิ์เห็นเมนู "ส่วนบริหารงาน" (Programs, All Projects)
    // เงื่อนไข: เป็น admin หรือ program_manager
    canManageProjects.value = ['admin', 'program_manager'].includes(user.role);

    // 2. สิทธิ์เห็นเมนู "จัดการผู้ใช้งาน" (Users)
    // เงื่อนไข: ต้องเป็น admin เท่านั้น
    isSystemAdmin.value = user.role === 'admin';
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
</style>
