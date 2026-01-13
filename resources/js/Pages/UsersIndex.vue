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
    form.value = { ...user, password: '' };
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
  } catch (e) { alert('บันทึกไม่สำเร็จ'); }
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
      <h1 class="text-2xl font-bold text-gray-800">จัดการผู้ใช้งาน (Users)</h1>
      <button @click="openModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition-transform active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
        เพิ่มผู้ใช้งาน
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
          <tr>
            <th class="px-6 py-3">ชื่อ</th>
            <th class="px-6 py-3">อีเมล</th>
            <th class="px-6 py-3 text-center">สิทธิ์ (Role)</th>
            <th class="px-6 py-3 text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="u in users" :key="u.id" class="hover:bg-purple-50 transition-colors">
            <td class="px-6 py-4 font-bold text-gray-800 flex items-center gap-3">
               <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-bold text-sm">
                  {{ u.name.charAt(0).toUpperCase() }}
               </div>
               {{ u.name }}
            </td>
            <td class="px-6 py-4 text-gray-600">{{ u.email }}</td>
            <td class="px-6 py-4 text-center">
              <span v-if="u.role==='admin'" class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-bold">Admin</span>
              <span v-else-if="u.role==='program_manager'" class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-bold">Manager</span>
              <span v-else class="bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-bold">User</span>
            </td>
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <button @click="openModal(u)" class="p-1 text-yellow-500 hover:bg-yellow-50 rounded transition-colors" title="แก้ไข">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                </button>
                <button @click="remove(u.id)" class="p-1 text-red-500 hover:bg-red-50 rounded transition-colors" title="ลบ">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white p-6 rounded-xl w-96 shadow-2xl">
        <h3 class="font-bold text-xl mb-4 text-gray-800">{{ form.id ? '✏️ แก้ไข' : '➕ เพิ่ม' }} ผู้ใช้งาน</h3>
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">ชื่อ-สกุล</label>
            <input v-model="form.name" class="w-full border rounded-lg px-3 py-2 outline-none">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">อีเมล</label>
            <input v-model="form.email" type="email" class="w-full border rounded-lg px-3 py-2 outline-none">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">รหัสผ่าน {{ form.id ? '(เว้นว่างถ้าไม่เปลี่ยน)' : '*' }}</label>
            <input v-model="form.password" type="password" class="w-full border rounded-lg px-3 py-2 outline-none">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">สิทธิ์ (Role)</label>
            <select v-model="form.role" class="w-full border rounded-lg px-3 py-2 outline-none">
              <option value="user">User (ทั่วไป)</option>
              <option value="program_manager">Program Manager</option>
              <option value="admin">Admin (ดูแลระบบ)</option>
            </select>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
          <button @click="showModal=false" class="px-4 py-2 text-gray-600 hover:text-gray-800">ยกเลิก</button>
          <button @click="save" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow">บันทึก</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
