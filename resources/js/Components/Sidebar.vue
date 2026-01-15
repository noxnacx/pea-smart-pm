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

    <nav class="flex-1 py-6 space-y-6 overflow-y-auto custom-scrollbar">
        <div v-for="(group, index) in filteredMenuGroups" :key="index">
            <div v-if="!isCollapsed && group.title" class="px-6 mb-2 text-xs font-bold text-purple-300 uppercase tracking-wider">
                {{ group.title }}
            </div>

            <div class="space-y-1">
                <template v-for="item in group.items" :key="item.name">

                    <router-link
                        v-if="!item.isMock"
                        :to="item.path"
                        class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700 transition-colors border-l-4 border-transparent hover:border-white group"
                        :class="{ 'bg-purple-800 border-white': item.active }"
                        :title="isCollapsed ? item.name : ''"
                    >
                        <span class="transition-transform group-hover:scale-110">
                            <svg v-if="item.icon === 'DashboardIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                            <svg v-else-if="item.icon === 'CalendarIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <svg v-else-if="item.icon === 'BellIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                            <svg v-else-if="item.icon === 'FolderOpenIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg v-else-if="item.icon === 'ClipboardCheckIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                            <svg v-else-if="item.icon === 'CollectionIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            <svg v-else-if="item.icon === 'ArchiveIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>
                            <svg v-else-if="item.icon === 'UsersIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </span>
                        <span v-if="!isCollapsed">{{ item.name }}</span>
                    </router-link>

                    <div
                        v-else
                        @click="showAlert(item.name)"
                        class="flex items-center gap-4 px-6 py-3 hover:bg-purple-700/50 transition-colors border-l-4 border-transparent hover:border-purple-300 group cursor-pointer text-purple-200"
                        :title="isCollapsed ? item.name + ' (เร็วๆ นี้)' : ''"
                    >
                        <span class="transition-transform group-hover:scale-110 opacity-70">
                            <svg v-if="item.icon === 'DocumentReportIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <svg v-else-if="item.icon === 'CogIcon'" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 min-w-[24px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        </span>
                        <div v-if="!isCollapsed" class="flex-1 flex justify-between items-center">
                            <span>{{ item.name }}</span>
                            <span class="text-[10px] bg-purple-900/50 border border-purple-500/30 px-1.5 py-0.5 rounded text-purple-300">Soon</span>
                        </div>
                    </div>

                </template>
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
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

defineProps(['isCollapsed']);
defineEmits(['toggle']);

const userRole = ref('');

onMounted(() => {
    const user = JSON.parse(localStorage.getItem('user_info') || '{}');
    userRole.value = user.role || 'user';
});

const showAlert = (name) => {
    alert(`เมนู "${name}" อยู่ในระหว่างการพัฒนา (Coming Soon)`);
};

// โครงสร้างเมนูทั้งหมด
const filteredMenuGroups = computed(() => {
    const isAdminOrPM = ['admin', 'program_manager'].includes(userRole.value);
    const isAdminOnly = userRole.value === 'admin';

    return [
        {
            title: 'ภาพรวม (General)',
            items: [
                { name: 'ภาพรวม (Dashboard)', path: '/dashboard', icon: 'DashboardIcon', active: route.path === '/dashboard' },
                { name: 'ปฏิทินงาน', path: '/calendar', icon: 'CalendarIcon', active: route.path === '/calendar' },
                // ✅ เพิ่มเมนูการแจ้งเตือนตรงนี้ครับ
                { name: 'การแจ้งเตือน', path: '/notifications', icon: 'BellIcon', active: route.path === '/notifications' }
            ]
        },
        {
            title: 'งานของฉัน (My Workspace)',
            items: [
                { name: 'โครงการของฉัน', path: '/my-projects', icon: 'FolderOpenIcon', active: route.path === '/my-projects' },
                { name: 'งานที่ต้องทำ', path: '/my-tasks', icon: 'ClipboardCheckIcon', active: route.path === '/my-tasks' }
            ]
        },
        {
            title: 'การบริหาร (Management)',
            items: isAdminOrPM ? [
                { name: 'จัดการแผนงานหลัก', path: '/programs', icon: 'CollectionIcon', active: route.path.startsWith('/programs') },
                { name: 'ทะเบียนโครงการ', path: '/projects', icon: 'ArchiveIcon', active: route.path === '/projects' },
                { name: 'รายงานสรุป', path: '/reports', icon: 'DocumentReportIcon', isMock: true }
            ] : []
        },
        {
            title: 'ตั้งค่าระบบ (System)',
            items: isAdminOnly ? [
                { name: 'จัดการผู้ใช้งาน', path: '/users', icon: 'UsersIcon', active: route.path === '/users' },
                { name: 'ตั้งค่าส่วนตัว', path: '/settings', icon: 'CogIcon', isMock: true }
            ] : []
        }
    ].filter(group => group.items.length > 0);
});
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }

/* Custom Scrollbar ให้เข้ากับธีมสีม่วง */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #6d28d9; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
</style>
