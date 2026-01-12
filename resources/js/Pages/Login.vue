<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">PEA Smart PM</h1>
        <p class="text-gray-500 text-sm">ระบบบริหารจัดการโครงการ</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">อีเมล</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full border rounded px-3 py-2 focus:outline-blue-500 focus:border-blue-500"
            placeholder="admin@pea.co.th"
            required
          >
        </div>

        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-bold mb-2">รหัสผ่าน</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full border rounded px-3 py-2 focus:outline-blue-500 focus:border-blue-500"
            placeholder="********"
            required
          >
        </div>

        <button
          type="submit"
          class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors"
          :disabled="loading"
        >
          {{ loading ? 'กำลังตรวจสอบ...' : 'เข้าสู่ระบบ' }}
        </button>

        <p v-if="errorMessage" class="mt-4 text-red-500 text-sm text-center">
          {{ errorMessage }}
        </p>
      </form>

      <div class="mt-6 text-center text-xs text-gray-400">
        Demo Account: admin@pea.co.th / password
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const errorMessage = ref('');

const form = reactive({
  email: '',
  password: ''
});

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    // 1. ยิง API Login
    // ก่อน login เราต้องขอ cookie CSRF ก่อน (เป็นกฎของ Laravel Sanctum)
    await axios.get('/sanctum/csrf-cookie');

    await axios.post('/login', form);

    // 2. ถ้าผ่าน ให้เก็บสถานะว่าล็อกอินแล้ว (ใน LocalStorage ง่ายๆ ก่อน)
    localStorage.setItem('isLoggedIn', 'true');

    // 3. ดีดไปหน้า Dashboard
    router.push('/');

  } catch (error) {
    console.error(error);
    errorMessage.value = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
  } finally {
    loading.value = false;
  }
};
</script>
