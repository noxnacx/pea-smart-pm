<script setup>
import AppLayout from '../Components/AppLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const programs = ref([]);
const loading = ref(false);
const showModal = ref(false);

// Form Default Value
const form = ref({
  id: null,
  name: '',
  fiscal_year: new Date().getFullYear() + 543, // ค่าเริ่มต้นเป็นปี พ.ศ. ปัจจุบัน
  total_budget: 0,
  start_date: '',
  end_date: '',
  description: '',
  status: 'active'
});

const fetchPrograms = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/programs');
    programs.value = res.data;
  } catch (e) {
    console.error(e);
  }
  loading.value = false;
};

const openModal = (program = null) => {
  if (program) {
    // โหมดแก้ไข: copy ข้อมูลมาใส่ฟอร์ม
    form.value = { ...program };
  } else {
    // โหมดสร้างใหม่: reset ค่า
    form.value = {
      id: null,
      name: '',
      fiscal_year: new Date().getFullYear() + 543,
      total_budget: 0,
      start_date: '',
      end_date: '',
      description: '',
      status: 'active'
    };
  }
  showModal.value = true;
};

const save = async () => {
  try {
    // สร้าง payload แยกออกมาเพื่อปรับแต่งข้อมูลก่อนส่ง (ไม่กระทบหน้าจอ)
    const payload = { ...form.value };

    // 1. แปลงปีเป็น String เพื่อความชัวร์ (Backend validate size:4 ต้องการ string)
    payload.fiscal_year = String(payload.fiscal_year);

    // 2. ✅ แก้บั๊ก: แปลงวันที่ว่าง "" ให้เป็น null (Backend จะได้ไม่มองว่าเป็น invalid date)
    if (payload.start_date === '') payload.start_date = null;
    if (payload.end_date === '') payload.end_date = null;

    // ส่งข้อมูล
    if (payload.id) {
        await axios.put(`/api/programs/${payload.id}`, payload);
    } else {
        await axios.post('/api/programs', payload);
    }

    showModal.value = false;
    fetchPrograms();
    alert('บันทึกสำเร็จ');
  } catch (e) {
    console.error(e);
    // แสดง Error ที่ Backend ส่งกลับมาให้ชัดเจน
    alert(e.response?.data?.message || 'เกิดข้อผิดพลาด กรุณาตรวจสอบข้อมูล');
  }
};

const remove = async (id) => {
  if (!confirm('ยืนยันลบแผนงานนี้? (ต้องไม่มีโครงการภายใน)')) return;
  try {
    await axios.delete(`/api/programs/${id}`);
    fetchPrograms();
  } catch (e) {
    alert(e.response?.data?.message || 'ลบไม่สำเร็จ');
  }
};

// ฟังก์ชันไปหน้าดูรายละเอียด (Drill-down)
const goToDetail = (id) => {
    router.push(`/programs/${id}`);
};

const formatCurrency = (val) => new Intl.NumberFormat('th-TH').format(val || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('th-TH') : '-';

onMounted(fetchPrograms);
</script>

<template>
  <AppLayout>
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">จัดการแผนงาน (Programs)</h1>
        <p class="text-sm text-gray-500">บริหารจัดการแผนงานหลักและงบประมาณภาพรวม</p>
      </div>
      <button @click="openModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition-transform active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
        เพิ่มแผนงานใหม่
      </button>
    </div>

    <div v-if="loading" class="text-center py-20 text-gray-500">กำลังโหลดข้อมูล...</div>

    <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
          <tr>
            <th class="px-6 py-3">ปีงบฯ</th>
            <th class="px-6 py-3">ชื่อแผนงาน</th>
            <th class="px-6 py-3 text-center">ระยะเวลา</th>
            <th class="px-6 py-3 text-center">สถานะ</th>
            <th class="px-6 py-3 text-right">งบประมาณรวม</th>
            <th class="px-6 py-3 text-center">จัดการ</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="pg in programs" :key="pg.id" class="hover:bg-purple-50 transition-colors group">
            <td class="px-6 py-4 font-bold text-purple-700">{{ pg.fiscal_year }}</td>
            <td class="px-6 py-4 cursor-pointer" @click="goToDetail(pg.id)">
              <div class="font-medium text-gray-800 group-hover:text-purple-700 group-hover:underline transition-colors">{{ pg.name }}</div>
              <div class="text-xs text-gray-500 truncate w-64">{{ pg.description }}</div>
            </td>
            <td class="px-6 py-4 text-center text-sm text-gray-500">
              {{ formatDate(pg.start_date) }} - {{ formatDate(pg.end_date) }}
            </td>
            <td class="px-6 py-4 text-center">
               <span :class="pg.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="px-2 py-1 rounded text-xs font-bold">
                 {{ pg.status === 'active' ? 'ใช้งาน' : 'ปิดแล้ว' }}
               </span>
            </td>
            <td class="px-6 py-4 text-right font-medium font-mono">{{ formatCurrency(pg.total_budget) }}</td>
            <td class="px-6 py-4 text-center">
              <div class="flex items-center justify-center gap-2">
                <button @click="goToDetail(pg.id)" class="p-1 text-blue-500 hover:bg-blue-100 rounded transition-colors" title="ดูโครงการภายใน">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" /></svg>
                </button>
                <button @click="openModal(pg)" class="p-1 text-yellow-500 hover:bg-yellow-50 rounded transition-colors" title="แก้ไข">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                </button>
                <button @click="remove(pg.id)" class="p-1 text-red-500 hover:bg-red-50 rounded transition-colors" title="ลบ">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
          <h3 class="font-bold text-xl text-gray-800">{{ form.id ? '✏️ แก้ไขแผนงาน' : '➕ เพิ่มแผนงานใหม่' }}</h3>
          <button @click="showModal=false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
          <div class="grid grid-cols-2 gap-4">
             <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">ปีงบประมาณ (พ.ศ.) *</label>
                <input v-model="form.fiscal_year" type="text" maxlength="4" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" placeholder="2569">
             </div>
             <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">สถานะ</label>
                <select v-model="form.status" class="w-full border rounded-lg px-3 py-2 outline-none">
                   <option value="active">🟢 ใช้งานอยู่</option>
                   <option value="closed">🔴 ปิดแล้ว</option>
                </select>
             </div>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">ชื่อแผนงาน *</label>
            <input v-model="form.name" class="w-full border rounded-lg px-3 py-2 outline-none" placeholder="ระบุชื่อแผนงาน...">
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">งบประมาณรวม (บาท) *</label>
            <input v-model="form.total_budget" type="number" class="w-full border rounded-lg px-3 py-2 outline-none">
          </div>

          <div class="grid grid-cols-2 gap-4">
             <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">วันเริ่มแผน</label>
                <input v-model="form.start_date" type="date" class="w-full border rounded-lg px-3 py-2 outline-none">
             </div>
             <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">วันสิ้นสุดแผน</label>
                <input v-model="form.end_date" type="date" class="w-full border rounded-lg px-3 py-2 outline-none">
             </div>
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">รายละเอียดเพิ่มเติม</label>
            <textarea v-model="form.description" rows="3" class="w-full border rounded-lg px-3 py-2 outline-none" placeholder="ขอบเขตงานโดยย่อ..."></textarea>
          </div>
        </div>

        <div class="p-6 border-t bg-gray-50 flex justify-end gap-2">
          <button @click="showModal=false" class="px-4 py-2 text-gray-600 hover:text-gray-800">ยกเลิก</button>
          <button @click="save" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-bold shadow transition-colors">บันทึก</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
