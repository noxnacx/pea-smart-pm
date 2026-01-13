<script setup>
import AppLayout from '../Components/AppLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const programs = ref([]);
const loading = ref(false);
const showModal = ref(false);
const form = ref({ id: null, name: '', total_budget: 0 });

const fetchPrograms = async () => {
  loading.value = true;
  const res = await axios.get('/api/programs');
  programs.value = res.data;
  loading.value = false;
};

const openModal = (program = null) => {
  if (program) {
    form.value = { ...program };
  } else {
    form.value = { id: null, name: '', total_budget: 0 };
  }
  showModal.value = true;
};

const save = async () => {
  try {
    if (form.value.id) await axios.put(`/api/programs/${form.value.id}`, form.value);
    else await axios.post('/api/programs', form.value);
    showModal.value = false;
    fetchPrograms();
    alert('บันทึกสำเร็จ');
  } catch (e) { alert('เกิดข้อผิดพลาด'); }
};

const remove = async (id) => {
  if (!confirm('ยืนยันลบแผนงานนี้?')) return;
  try {
    await axios.delete(`/api/programs/${id}`);
    fetchPrograms();
  } catch (e) { alert(e.response?.data?.message || 'ลบไม่สำเร็จ'); }
};

const formatCurrency = (val) => new Intl.NumberFormat('th-TH').format(val);
onMounted(fetchPrograms);
</script>

<template>
  <AppLayout>
    <div class="flex justify-between mb-6">
      <h1 class="text-2xl font-bold">จัดการแผนงาน (Programs)</h1>
      <button @click="openModal()" class="bg-purple-600 text-white px-4 py-2 rounded shadow">เพิ่มแผนงาน</button>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3">ชื่อแผนงาน</th>
            <th class="px-6 py-3 text-right">งบประมาณรวม</th>
            <th class="px-6 py-3 text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="pg in programs" :key="pg.id" class="border-t hover:bg-gray-50">
            <td class="px-6 py-4">{{ pg.name }}</td>
            <td class="px-6 py-4 text-right">{{ formatCurrency(pg.total_budget) }}</td>
            <td class="px-6 py-4 text-center space-x-2">
              <button @click="openModal(pg)" class="text-yellow-600 hover:underline">แก้ไข</button>
              <button @click="remove(pg.id)" class="text-red-600 hover:underline">ลบ</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded w-96 shadow-xl">
        <h3 class="font-bold text-lg mb-4">{{ form.id ? 'แก้ไข' : 'เพิ่ม' }} แผนงาน</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm mb-1">ชื่อแผนงาน</label>
            <input v-model="form.name" class="w-full border rounded px-3 py-2" placeholder="เช่น แผนพัฒนาระบบระยะที่ 1">
          </div>
          <div>
            <label class="block text-sm mb-1">งบประมาณรวม</label>
            <input v-model="form.total_budget" type="number" class="w-full border rounded px-3 py-2">
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
