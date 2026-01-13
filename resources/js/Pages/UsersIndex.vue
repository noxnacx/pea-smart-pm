<script setup>
import AppLayout from '../Components/AppLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const users = ref([]);
const showModal = ref(false);
const form = ref({ id: null, name: '', email: '', password: '', role: 'user' });

const fetchUsers = async () => {
  const res = await axios.get('/api/users');
  users.value = res.data;
};

const openModal = (user = null) => {
  if (user) {
    form.value = { ...user, password: '' }; // ไม่ต้องโหลด password เดิมมา
  } else {
    form.value = { id: null, name: '', email: '', password: '', role: 'user' };
  }
  showModal.value = true;
};

const save = async () => {
  try {
    if (form.value.id) await axios.put(`/api/users/${form.value.id}`, form.value);
    else await axios.post('/api/users', form.value);
    showModal.value = false;
    fetchUsers();
    alert('บันทึกสำเร็จ');
  } catch (e) { alert('บันทึกไม่สำเร็จ (อีเมลซ้ำหรือข้อมูลไม่ครบ)'); }
};

const remove = async (id) => {
  if (!confirm('ลบผู้ใช้งานนี้?')) return;
  try { await axios.delete(`/api/users/${id}`); fetchUsers(); }
  catch (e) { alert(e.response?.data?.message); }
};

onMounted(fetchUsers);
</script>

<template>
  <AppLayout>
    <div class="flex justify-between mb-6">
      <h1 class="text-2xl font-bold">จัดการผู้ใช้งาน (Users)</h1>
      <button @click="openModal()" class="bg-purple-600 text-white px-4 py-2 rounded shadow">เพิ่มผู้ใช้งาน</button>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3">ชื่อ</th>
            <th class="px-6 py-3">อีเมล</th>
            <th class="px-6 py-3">สิทธิ์ (Role)</th>
            <th class="px-6 py-3 text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id" class="border-t hover:bg-gray-50">
            <td class="px-6 py-4 font-bold">{{ u.name }}</td>
            <td class="px-6 py-4">{{ u.email }}</td>
            <td class="px-6 py-4">
              <span v-if="u.role==='admin'" class="bg-red-100 text-red-800 px-2 rounded text-xs">Admin</span>
              <span v-else-if="u.role==='program_manager'" class="bg-blue-100 text-blue-800 px-2 rounded text-xs">Program Mgr</span>
              <span v-else class="bg-gray-100 text-gray-800 px-2 rounded text-xs">User</span>
            </td>
            <td class="px-6 py-4 text-center space-x-2">
              <button @click="openModal(u)" class="text-yellow-600 hover:underline">แก้ไข</button>
              <button @click="remove(u.id)" class="text-red-600 hover:underline">ลบ</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded w-96 shadow-xl">
        <h3 class="font-bold text-lg mb-4">{{ form.id ? 'แก้ไข' : 'เพิ่ม' }} ผู้ใช้งาน</h3>
        <div class="space-y-3">
          <div>
            <label class="block text-sm mb-1">ชื่อ-สกุล</label>
            <input v-model="form.name" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm mb-1">อีเมล</label>
            <input v-model="form.email" type="email" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm mb-1">รหัสผ่าน {{ form.id ? '(เว้นว่างถ้าไม่เปลี่ยน)' : '*' }}</label>
            <input v-model="form.password" type="password" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm mb-1">สิทธิ์ (Role)</label>
            <select v-model="form.role" class="w-full border rounded px-3 py-2">
              <option value="user">User (ทั่วไป)</option>
              <option value="program_manager">Program Manager</option>
              <option value="admin">Admin (ดูแลระบบ)</option>
            </select>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
          <button @click="showModal=false" class="text-gray-500 px-3">ยกเลิก</button>
          <button @click="save" class="bg-purple-600 text-white px-4 py-2 rounded">บันทึก</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
