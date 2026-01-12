<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({ email: '', password: '' });
const errorMsg = ref('');

const handleLogin = async () => {
  try {
    // 1. ยิง API Login
    const response = await axios.post('/api/login', form.value);

    // 2. *** จุดสำคัญ: เก็บ Token และสถานะลงเครื่อง ***
    localStorage.setItem('token', response.data.token);
    localStorage.setItem('isLoggedIn', 'true'); // ใช้สำหรับ Router Guard
    localStorage.setItem('user', JSON.stringify(response.data.user)); // เก็บชื่อคนล็อกอินไว้โชว์

    // 3. ตั้งค่า Header ให้ Axios ทันที (ไม่ต้องรอ Refresh หน้า)
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

    // 4. ไปหน้า Dashboard
    router.push('/');

  } catch (error) {
    console.error(error);
    errorMsg.value = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
  }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-purple-900">
    <div class="bg-white p-8 rounded-lg shadow-2xl w-96 transform hover:scale-105 transition-transform duration-300">
      <div class="text-center mb-6">
        <h1 class="text-3xl font-bold text-purple-800">PEA Smart PM</h1>
        <p class="text-gray-500 text-sm mt-1">ระบบบริหารโครงการ</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Email</label>
          <input v-model="form.email" type="email" required class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-purple-500 outline-none" placeholder="admin@pea.co.th">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Password</label>
          <input v-model="form.password" type="password" required class="w-full mt-1 p-2 border rounded-lg focus:ring-2 focus:ring-purple-500 outline-none" placeholder="••••••••">
        </div>

        <div v-if="errorMsg" class="text-red-500 text-sm text-center bg-red-50 p-2 rounded">
          {{ errorMsg }}
        </div>

        <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 font-bold shadow-md transition-colors">
          เข้าสู่ระบบ
        </button>
      </form>
    </div>
  </div>
</template>
