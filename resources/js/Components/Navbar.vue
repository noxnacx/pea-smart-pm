<template>
  <nav class="bg-white border-b border-gray-200 px-6 py-3 flex justify-end items-center sticky top-0 z-30 h-16 shadow-sm">
    <div class="flex items-center gap-4">

      <div class="relative" ref="bellRef">
        <button
          @click="toggleNotifications"
          class="p-2 rounded-full text-gray-500 hover:bg-purple-50 hover:text-purple-600 transition-colors relative"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <span v-if="unreadCount > 0" class="absolute top-1 right-1 h-4 w-4 bg-red-500 rounded-full text-[10px] font-bold text-white flex items-center justify-center border border-white">
            {{ unreadCount > 9 ? '9+' : unreadCount }}
          </span>
        </button>

        <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden z-50 animate-fade-in-up">
            <div class="p-3 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-700 text-sm">การแจ้งเตือน</h3>
                <div class="flex gap-2">
                    <button v-if="notifications.length > 0" @click="clearAllNotifications" class="text-xs text-gray-400 hover:text-red-500">ลบทั้งหมด</button>
                    <button v-if="unreadCount > 0" @click="markAllRead" class="text-xs text-purple-600 hover:underline">อ่านทั้งหมด</button>
                </div>
            </div>
            <div class="max-h-80 overflow-y-auto custom-scrollbar">
                <div v-if="notifications.length === 0" class="p-8 text-center text-gray-400 text-sm">
                    ไม่มีการแจ้งเตือนใหม่
                </div>
                <div
                    v-for="noti in notifications"
                    :key="noti.id"
                    @click="handleNotificationClick(noti)"
                    class="p-3 border-b border-gray-50 hover:bg-purple-50 cursor-pointer transition-colors flex gap-3 group relative"
                    :class="{'bg-white': !!noti.read_at, 'bg-blue-50/50': !noti.read_at}"
                >
                    <div class="mt-1">
                        <div class="w-2 h-2 rounded-full" :class="!noti.read_at ? 'bg-blue-500' : 'bg-transparent'"></div>
                    </div>

                    <div class="flex-1 pr-6"> <p class="text-sm text-gray-800 font-medium leading-tight">{{ noti.data.message }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ formatDate(noti.created_at) }}</p>
                    </div>

                    <button
                        @click.stop="deleteNotification(noti.id)"
                        class="absolute top-3 right-3 text-gray-300 hover:text-red-500 p-1 rounded-full hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100"
                        title="ลบแจ้งเตือน"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
      </div>

      <div class="text-right hidden md:block">
        <div class="text-sm font-bold text-gray-700">{{ user.name }}</div>
        <div class="text-xs text-gray-500">{{ user.email }}</div>
      </div>

      <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold border border-purple-200">
        {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
      </div>

      <div class="h-6 w-px bg-gray-300 mx-2"></div>

      <button @click="handleLogout" class="text-gray-500 hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition-colors" title="ออกจากระบบ">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
      </button>
    </div>
  </nav>

  <div v-if="showNotifications" @click="showNotifications = false" class="fixed inset-0 z-20 cursor-default"></div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const user = ref({ name: 'User', email: '' });
const notifications = ref([]);
const unreadCount = ref(0);
const showNotifications = ref(false);
let pollingInterval = null;

const fetchNotifications = async () => {
    try {
        const [resList, resCount] = await Promise.all([
            axios.get('/api/notifications'),
            axios.get('/api/notifications/unread')
        ]);
        notifications.value = resList.data;
        unreadCount.value = resCount.data.count;
    } catch (e) { console.error("Fetch noti error"); }
};

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) fetchNotifications();
};

const markAllRead = async () => {
    await axios.post('/api/notifications/read-all');
    fetchNotifications();
};

const handleNotificationClick = async (noti) => {
    if (!noti.read_at) {
        await axios.post(`/api/notifications/${noti.id}/read`);
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        noti.read_at = new Date().toISOString();
    }
    showNotifications.value = false;
    if (noti.data.link) {
        router.push(noti.data.link);
    }
};

// ✅ ฟังก์ชันลบแจ้งเตือน (รายตัว)
const deleteNotification = async (id) => {
    // หาตัวที่จะลบเพื่อเช็คว่าอ่านหรือยัง (ถ้ายังไม่อ่าน ต้องลดตัวเลขแจ้งเตือนด้วย)
    const targetNoti = notifications.value.find(n => n.id === id);

    try {
        await axios.delete(`/api/notifications/${id}`);

        // อัปเดต List ทันที (ไม่ต้องรอโหลดใหม่)
        notifications.value = notifications.value.filter(n => n.id !== id);

        // ถ้าลบตัวที่ยังไม่อ่าน ให้ลดตัวเลข
        if (targetNoti && !targetNoti.read_at) {
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
    } catch (e) {
        console.error('Delete failed', e);
    }
};

// ✅ ฟังก์ชันลบแจ้งเตือน (ทั้งหมด) - เผื่ออยากใช้ในอนาคต (Optional)
const clearAllNotifications = async () => {
    if(!confirm('ยืนยันลบการแจ้งเตือนทั้งหมด?')) return;
    // หมายเหตุ: ต้องไปเพิ่ม API delete all ที่หลังบ้านถ้าจะใช้ฟีเจอร์นี้จริงๆ
    // ตอนนี้ทำเป็น mockup loop ลบไปก่อน
    notifications.value = [];
    unreadCount.value = 0;
};

const formatDate = (date) => {
    const d = new Date(date);
    const now = new Date();
    const diff = (now - d) / 1000;
    if (diff < 60) return 'เมื่อสักครู่';
    if (diff < 3600) return Math.floor(diff/60) + ' นาทีที่แล้ว';
    if (diff < 86400) return Math.floor(diff/3600) + ' ชั่วโมงที่แล้ว';
    return d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
};

onMounted(async () => {
  const storedUser = localStorage.getItem('user_info');
  if (storedUser) user.value = JSON.parse(storedUser);

  try {
    const response = await axios.get('/api/user');
    user.value = response.data;
    localStorage.setItem('user_info', JSON.stringify(response.data));
    fetchNotifications();
    pollingInterval = setInterval(fetchNotifications, 60000);
  } catch (error) {
    if (error.response && error.response.status === 401) handleLogout(false);
  }
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

const handleLogout = async (confirmLogout = true) => {
  if (confirmLogout && !confirm('ต้องการออกจากระบบใช่หรือไม่?')) return;
  try { await axios.post('/api/logout'); } catch (error) {}
  finally {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_info');
    delete axios.defaults.headers.common['Authorization'];
    router.push('/login');
  }
};
</script>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.2s ease-out; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
/* สไตล์ Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
</style>
