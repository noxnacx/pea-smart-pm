<template>
  <nav class="bg-white border-b border-gray-200 px-6 py-3 flex justify-end items-center sticky top-0 z-30 h-16 shadow-sm">
    <div class="flex items-center gap-4">
      <div class="text-right hidden md:block">
        <div class="text-sm font-bold text-gray-700">{{ user.name }}</div>
        <div class="text-xs text-gray-500">{{ user.email }}</div>
      </div>

      <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold border border-purple-200">
        {{ user.name ? user.name.charAt(0) : 'U' }}
      </div>

      <div class="h-6 w-px bg-gray-300 mx-2"></div>

      <button
        @click="handleLogout"
        class="text-gray-500 hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition-colors"
        title="ออกจากระบบ"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const user = ref({ name: 'Loading...', email: '' });

onMounted(async () => {
  try {
    const response = await axios.get('/api/user');
    user.value = response.data;
  } catch (error) {
    localStorage.removeItem('isLoggedIn');
    router.push('/login');
  }
});

const handleLogout = async () => {
  if (!confirm('ต้องการออกจากระบบใช่หรือไม่?')) return;
  try {
    await axios.post('/logout');
    localStorage.removeItem('isLoggedIn');
    router.push('/login');
  } catch (error) { console.error(error); }
};
</script>
