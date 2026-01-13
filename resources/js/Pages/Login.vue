<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({ email: '', password: '' });
const errorMessage = ref('');
const loading = ref(false);

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const response = await axios.post('/api/login', form.value);
    const token = response.data.token;
    localStorage.setItem('auth_token', token);
    localStorage.setItem('user_info', JSON.stringify(response.data.user));
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    router.push('/dashboard');
  } catch (error) {
    if (error.response && error.response.status === 401) {
      errorMessage.value = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
    } else {
      errorMessage.value = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md border border-gray-200">

      <div class="text-center mb-8">
        <img src="/images/logo.png" alt="PEA Logo" class="h-24 mx-auto mb-4 object-contain">
        <h2 class="text-2xl font-bold text-gray-800">เข้าสู่ระบบ</h2>
        <p class="text-gray-500 text-sm mt-1">ระบบบริหารจัดการโครงการ (Smart PM)</p>
      </div>

      <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 text-red-600 text-sm rounded-lg flex items-center gap-2 border border-red-100">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
          <input v-model="form.email" type="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none" placeholder="admin@pea.co.th">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
          <input v-model="form.password" type="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none" placeholder="••••••••">
        </div>
        <button type="submit" :disabled="loading" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 rounded-lg shadow transition-colors flex justify-center items-center gap-2 disabled:opacity-70">
          <span v-if="loading">กำลังตรวจสอบ...</span>
          <span v-else>เข้าสู่ระบบ</span>
        </button>
      </form>
    </div>
  </div>
</template>
