<script setup>
import AppLayout from '../Components/AppLayout.vue';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const allPrograms = ref([]);
const loading = ref(false);
const showModal = ref(false);
const search = ref('');
const yearFilter = ref('');

// Pagination State
const currentPage = ref(1);
const itemsPerPage = 10;

const form = ref({ id: null, name: '', fiscal_year: new Date().getFullYear() + 543, total_budget: 0, start_date: '', end_date: '', description: '', status: 'active' });

const fetchPrograms = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/programs');
    allPrograms.value = res.data;
  } catch (e) { console.error(e); }
  loading.value = false;
};

// --- Computed Filters & Pagination ---
const availableYears = computed(() => [...new Set(allPrograms.value.map(p => p.fiscal_year))].sort().reverse());

const filteredPrograms = computed(() => {
    return allPrograms.value.filter(p => {
        const matchesSearch = p.name.toLowerCase().includes(search.value.toLowerCase());
        const matchesYear = yearFilter.value ? String(p.fiscal_year) === String(yearFilter.value) : true;
        return matchesSearch && matchesYear;
    });
});

const paginatedPrograms = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredPrograms.value.slice(start, start + itemsPerPage);
});

const totalPages = computed(() => Math.ceil(filteredPrograms.value.length / itemsPerPage));

const changePage = (page) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };
watch([search, yearFilter], () => { currentPage.value = 1; }); // Reset หน้าเมื่อกรอง

// --- Actions ---
const openModal = (program = null) => {
  form.value = program ? { ...program } : { id: null, name: '', fiscal_year: new Date().getFullYear() + 543, total_budget: 0, start_date: '', end_date: '', description: '', status: 'active' };
  showModal.value = true;
};
const save = async () => {
  try {
    const payload = { ...form.value, fiscal_year: String(form.value.fiscal_year) };
    if (!payload.start_date) payload.start_date = null; if (!payload.end_date) payload.end_date = null;
    payload.id ? await axios.put(`/api/programs/${payload.id}`, payload) : await axios.post('/api/programs', payload);
    showModal.value = false; fetchPrograms(); alert('บันทึกสำเร็จ');
  } catch (e) { alert('เกิดข้อผิดพลาด'); }
};
const remove = async (id) => { if (confirm('ยืนยันลบแผนงานนี้?')) { await axios.delete(`/api/programs/${id}`); fetchPrograms(); } };
const goToDetail = (id) => router.push(`/programs/${id}`);
const formatCurrency = (val) => new Intl.NumberFormat('th-TH').format(val || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('th-TH') : '-';

onMounted(fetchPrograms);
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
           <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">📂 จัดการแผนงาน (Programs)</h1>
           <p class="text-sm text-gray-500">บริหารจัดการแผนงานหลักและงบประมาณภาพรวม</p>
        </div>
        <button @click="openModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition-transform active:scale-95">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
          เพิ่มแผนงานใหม่
        </button>
      </div>

      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input v-model="search" type="text" placeholder="ค้นหาชื่อแผนงาน..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        <div class="w-full md:w-48">
            <select v-model="yearFilter" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
                <option value="">ทุกปีงบประมาณ</option>
                <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div v-if="loading" class="text-center py-12 text-gray-500 animate-pulse">กำลังโหลดข้อมูล...</div>
        <div v-else>
            <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                <th class="px-6 py-4">ปีงบฯ</th>
                <th class="px-6 py-4">ชื่อแผนงาน</th>
                <th class="px-6 py-4 text-center">ระยะเวลา</th>
                <th class="px-6 py-4 text-center">สถานะ</th>
                <th class="px-6 py-4 text-right">งบประมาณรวม</th>
                <th class="px-6 py-4 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="pg in paginatedPrograms" :key="pg.id" class="hover:bg-purple-50 transition-colors group">
                <td class="px-6 py-4 font-bold text-purple-700">{{ pg.fiscal_year }}</td>
                <td class="px-6 py-4 cursor-pointer" @click="goToDetail(pg.id)">
                    <div class="font-medium text-gray-800 group-hover:text-purple-700 group-hover:underline transition-colors">{{ pg.name }}</div>
                    <div class="text-xs text-gray-500 truncate w-64">{{ pg.description }}</div>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ formatDate(pg.start_date) }} - {{ formatDate(pg.end_date) }}</td>
                <td class="px-6 py-4 text-center">
                    <span :class="pg.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="px-2 py-1 rounded text-xs font-bold border">
                        {{ pg.status === 'active' ? 'ใช้งาน' : 'ปิดแล้ว' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right font-medium font-mono">{{ formatCurrency(pg.total_budget) }}</td>
                <td class="px-6 py-4 text-center">
                    <div class="flex items-center justify-center gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                    <button @click="openModal(pg)" class="p-1.5 text-yellow-600 hover:bg-yellow-100 rounded" title="แก้ไข"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg></button>
                    <button @click="remove(pg.id)" class="p-1.5 text-red-600 hover:bg-red-100 rounded" title="ลบ"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
                    </div>
                </td>
                </tr>
                <tr v-if="paginatedPrograms.length === 0"><td colspan="6" class="text-center py-10 text-gray-400">ไม่พบข้อมูลแผนงาน</td></tr>
            </tbody>
            </table>

            <div v-if="totalPages > 1" class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
                <span class="text-sm text-gray-500">แสดงหน้า {{ currentPage }} จาก {{ totalPages }}</span>
                <div class="flex gap-1">
                    <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1" class="px-3 py-1 rounded border bg-white hover:bg-gray-100 disabled:opacity-50 text-sm">ก่อนหน้า</button>
                    <button v-for="page in totalPages" :key="page" @click="changePage(page)" :class="{'bg-purple-600 text-white': page === currentPage, 'bg-white text-gray-700 hover:bg-gray-100': page !== currentPage}" class="px-3 py-1 rounded border text-sm min-w-[32px]">
                        {{ page }}
                    </button>
                    <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages" class="px-3 py-1 rounded border bg-white hover:bg-gray-100 disabled:opacity-50 text-sm">ถัดไป</button>
                </div>
            </div>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="p-5 border-b bg-gray-50 flex justify-between items-center"><h3 class="font-bold text-gray-800">{{ form.id ? '✏️ แก้ไขแผนงาน' : '➕ เพิ่มแผนงานใหม่' }}</h3><button @click="showModal=false" class="text-gray-400 hover:text-gray-600">✕</button></div>
        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
          <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-bold text-gray-700 mb-1">ปีงบฯ</label><input v-model="form.fiscal_year" class="w-full border rounded-lg px-3 py-2"></div><div><label class="block text-sm font-bold text-gray-700 mb-1">สถานะ</label><select v-model="form.status" class="w-full border rounded-lg px-3 py-2"><option value="active">🟢 ใช้งาน</option><option value="closed">🔴 ปิด</option></select></div></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">ชื่อแผนงาน</label><input v-model="form.name" class="w-full border rounded-lg px-3 py-2"></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">งบประมาณ</label><input v-model="form.total_budget" type="number" class="w-full border rounded-lg px-3 py-2"></div>
          <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-bold text-gray-700 mb-1">เริ่ม</label><input v-model="form.start_date" type="date" class="w-full border rounded-lg px-3 py-2"></div><div><label class="block text-sm font-bold text-gray-700 mb-1">สิ้นสุด</label><input v-model="form.end_date" type="date" class="w-full border rounded-lg px-3 py-2"></div></div>
          <div><label class="block text-sm font-bold text-gray-700 mb-1">รายละเอียด</label><textarea v-model="form.description" class="w-full border rounded-lg px-3 py-2"></textarea></div>
        </div>
        <div class="p-5 border-t bg-gray-50 flex justify-end gap-2"><button @click="showModal=false" class="px-4 py-2 text-gray-600">ยกเลิก</button><button @click="save" class="bg-purple-600 text-white px-6 py-2 rounded-lg font-bold">บันทึก</button></div>
      </div>
    </div>
  </AppLayout>
</template>
