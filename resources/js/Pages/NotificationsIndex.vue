<script setup>
import AppLayout from '../Components/AppLayout.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const notifications = ref([]);
const loading = ref(true);

// --- Pagination Config ---
const currentPage = ref(1);
const itemsPerPage = 10;

const fetchNotifications = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/notifications');
        notifications.value = res.data;
    } catch (e) { console.error(e); }
    loading.value = false;
};

// --- Pagination Logic (Frontend Side) ---
const paginatedNotifications = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return notifications.value.slice(start, start + itemsPerPage);
});

const totalPages = computed(() => Math.ceil(notifications.value.length / itemsPerPage));

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const displayedPages = computed(() => {
    const current = currentPage.value;
    const last = totalPages.value;
    const delta = 2;
    const range = [];
    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
    }
    if (current - delta > 2) range.unshift('...');
    if (current + delta < last - 1) range.push('...');
    range.unshift(1);
    if (last !== 1) range.push(last);
    return range;
});

// --- Actions ---
const markAsRead = async (noti) => {
    if (!noti.read_at) {
        await axios.post(`/api/notifications/${noti.id}/read`);
        // อัปเดตค่าใน Array หลัก (UI จะเปลี่ยนเอง)
        const target = notifications.value.find(n => n.id === noti.id);
        if (target) target.read_at = new Date().toISOString();
    }
};

const handleClick = async (noti) => {
    await markAsRead(noti);
    if (noti.data.link) {
        router.push(noti.data.link);
    }
};

const markAllRead = async () => {
    if(!confirm('ทำเครื่องหมายว่าอ่านแล้วทั้งหมด?')) return;
    try {
        await axios.post('/api/notifications/read-all');
        notifications.value.forEach(n => n.read_at = new Date().toISOString());
    } catch(e) { alert('เกิดข้อผิดพลาด'); }
};

const deleteNotification = async (id, event) => {
    event.stopPropagation();
    if(!confirm('ลบการแจ้งเตือนนี้?')) return;

    try {
        await axios.delete(`/api/notifications/${id}`);
        notifications.value = notifications.value.filter(n => n.id !== id);
        // ถ้าหน้าปัจจุบันไม่มีข้อมูลแล้ว ให้ถอยกลับ 1 หน้า
        if (paginatedNotifications.value.length === 0 && currentPage.value > 1) {
            currentPage.value--;
        }
    } catch(e) { alert('ลบไม่สำเร็จ'); }
};

const formatDate = (date) => new Date(date).toLocaleString('th-TH', {
    day: 'numeric', month: 'short', year: '2-digit', hour: '2-digit', minute:'2-digit'
});

onMounted(fetchNotifications);
</script>

<template>
  <AppLayout>
    <div class="space-y-6 h-full flex flex-col">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    🔔 การแจ้งเตือน (Notifications)
                </h1>
                <p class="text-sm text-gray-500">รวมประวัติการแจ้งเตือนทั้งหมดของคุณ ({{ notifications.length }})</p>
            </div>
            <div class="flex gap-3">
                <button @click="fetchNotifications" class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors" title="รีโหลด">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
                <button
                    v-if="notifications.some(n => !n.read_at)"
                    @click="markAllRead"
                    class="bg-white border border-purple-200 text-purple-700 hover:bg-purple-50 px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-colors"
                >
                    อ่านทั้งหมด
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col min-h-[calc(100vh-200px)] overflow-hidden">

            <div v-if="loading" class="flex-1 flex flex-col items-center justify-center text-gray-500 animate-pulse">
                <div class="w-10 h-10 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mb-4"></div>
                กำลังโหลดข้อมูล...
            </div>

            <div v-else-if="notifications.length === 0" class="flex-1 flex flex-col items-center justify-center text-center py-20">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                </div>
                <p class="text-gray-500 font-medium">ไม่มีการแจ้งเตือน</p>
                <p class="text-xs text-gray-400 mt-1">คุณจัดการงานเรียบร้อยหมดแล้ว!</p>
            </div>

            <div v-else class="flex-1 flex flex-col">
                <div class="flex-1 divide-y divide-gray-100">
                    <div
                        v-for="noti in paginatedNotifications"
                        :key="noti.id"
                        @click="handleClick(noti)"
                        class="p-5 hover:bg-purple-50 cursor-pointer transition-colors flex gap-4 group relative items-start"
                        :class="{'bg-white': !!noti.read_at, 'bg-blue-50/40': !noti.read_at}"
                    >
                        <div class="shrink-0 mt-1">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center transition-colors"
                                 :class="!noti.read_at ? 'bg-blue-100 text-blue-600 shadow-sm' : 'bg-gray-100 text-gray-400'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                                </svg>
                            </div>
                        </div>

                        <div class="flex-1 pr-12">
                            <div class="flex flex-col md:flex-row md:justify-between md:items-baseline mb-1">
                                <span class="font-bold text-gray-800 text-base" :class="{'text-purple-700': !noti.read_at}">
                                    {{ noti.data.message }}
                                    <span v-if="!noti.read_at" class="inline-block w-2 h-2 bg-red-500 rounded-full ml-2 align-middle"></span>
                                </span>
                                <span class="text-xs text-gray-400 whitespace-nowrap mt-1 md:mt-0">{{ formatDate(noti.created_at) }}</span>
                            </div>
                            <p class="text-sm text-gray-600 line-clamp-1">
                                {{ noti.data.project_name ? '📁 โครงการ: ' + noti.data.project_name : 'คลิกเพื่อดูรายละเอียดเพิ่มเติม...' }}
                            </p>
                        </div>

                        <button
                            @click="(e) => deleteNotification(noti.id, e)"
                            class="absolute right-4 top-4 p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-full transition-all opacity-0 group-hover:opacity-100"
                            title="ลบรายการนี้"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>

                <div v-if="totalPages > 1" class="border-t border-gray-200 p-4 bg-gray-50 flex justify-between items-center mt-auto">
                    <div class="text-sm text-gray-500 hidden md:block">
                        แสดงหน้า {{ currentPage }} จาก {{ totalPages }} (ทั้งหมด {{ notifications.length }} รายการ)
                    </div>
                    <div class="flex gap-1 justify-center w-full md:w-auto">
                        <button
                            @click="changePage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-3 py-1.5 rounded-lg border bg-white text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium transition-colors"
                        >
                            ก่อนหน้า
                        </button>

                        <div class="flex gap-1 overflow-x-auto max-w-[200px] scrollbar-hide">
                            <button
                                v-for="page in displayedPages"
                                :key="page"
                                @click="changePage(page)"
                                :class="page === currentPage ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-gray-700 hover:bg-gray-100 border-gray-200'"
                                class="w-8 h-8 rounded-lg border text-sm flex items-center justify-center font-medium transition-colors shrink-0"
                            >
                                {{ page }}
                            </button>
                        </div>

                        <button
                            @click="changePage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1.5 rounded-lg border bg-white text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium transition-colors"
                        >
                            ถัดไป
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </AppLayout>
</template>
