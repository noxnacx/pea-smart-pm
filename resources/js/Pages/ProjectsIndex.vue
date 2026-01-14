<script setup>
import AppLayout from '../Components/AppLayout.vue';
import ProjectModal from '../Components/ProjectModal.vue';
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { useRouter, useRoute } from 'vue-router'; // ✅ เพิ่ม useRoute

const router = useRouter();
const route = useRoute(); // ✅ เรียกใช้ useRoute
const loading = ref(true);
const projects = ref({ data: [], current_page: 1, last_page: 1 });
const search = ref('');
const statusFilter = ref('all');
const showModal = ref(false);
const projectToEdit = ref(null);

const fetchProjects = async (page = 1) => {
  loading.value = true;
  try {
    const response = await axios.get('/api/projects', {
      params: { page, search: search.value, status: statusFilter.value }
    });
    projects.value = response.data;
  } catch (error) { console.error("Error:", error); }
  loading.value = false;
};

// --- Pagination Logic ---
const displayedPages = computed(() => {
  const current = projects.value.current_page;
  const last = projects.value.last_page;
  const delta = 2;
  const range = [];
  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i);
  }
  if (current - delta > 2) range.unshift('...');
  if (current + delta < last - 1) range.push('...');
  range.unshift(1);
  if (last !== 1) range.push(last);
  return range;
});

const changePage = (page) => {
    if (page === '...' || page === projects.value.current_page) return;
    fetchProjects(page);
};

// --- Actions ---
const goToDetail = (id) => router.push(`/project/${id}`);
const openCreateModal = () => { projectToEdit.value = null; showModal.value = true; };
const openEditModal = (project, event) => { event.stopPropagation(); projectToEdit.value = project; showModal.value = true; };
const handleDelete = async (id, event) => {
  event.stopPropagation();
  if (!confirm('ยืนยันที่จะลบโครงการนี้?')) return;
  try { await axios.delete(`/api/projects/${id}`); fetchProjects(); alert('ลบโครงการเรียบร้อย'); }
  catch (error) { alert('ลบไม่สำเร็จ'); }
};
const handleSave = async (formData) => {
  try {
    formData.id ? await axios.put(`/api/projects/${formData.id}`, formData) : await axios.post('/api/projects', formData);
    showModal.value = false; fetchProjects();
  } catch (error) { alert('บันทึกไม่สำเร็จ'); }
};

const statusLabels = { 'ongoing': 'กำลังดำเนินการ', 'late': 'ล่าช้า', 'completed': 'เสร็จสิ้น', 'draft': 'ร่าง' };
const formatCurrency = (val) => new Intl.NumberFormat('th-TH').format(val || 0);

let timeout = null;
watch([search, statusFilter], () => { clearTimeout(timeout); timeout = setTimeout(() => fetchProjects(1), 500); });

// --- onMounted (แก้ไขใหม่) ---
onMounted(() => {
    // ✅ เช็คว่ามีค่า ?status=... ส่งมาจาก Dashboard หรือไม่
    if (route.query.status) {
        statusFilter.value = route.query.status;
    }
    fetchProjects();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
           <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">📂 ทะเบียนโครงการ (All Projects)</h1>
           <p class="text-sm text-gray-500">จัดการข้อมูลโครงการทั้งหมดในระบบ</p>
        </div>
        <button @click="openCreateModal" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition-all active:scale-95">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
          สร้างโครงการใหม่
        </button>
      </div>

      <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          <input v-model="search" type="text" placeholder="ค้นหาชื่อโครงการ หรือ รหัส..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        <div class="w-full md:w-48">
          <select v-model="statusFilter" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
            <option value="all">สถานะ: ทั้งหมด</option>
            <option value="ongoing">กำลังดำเนินการ</option>
            <option value="late">ล่าช้า</option>
            <option value="completed">เสร็จสิ้น</option>
            <option value="draft">ร่าง (Draft)</option>
          </select>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
        <div v-if="loading" class="text-center py-12 text-gray-500 animate-pulse">กำลังโหลดข้อมูล...</div>
        <div v-else>
            <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                <th class="px-6 py-4">รหัส</th>
                <th class="px-6 py-4">ชื่อโครงการ</th>
                <th class="px-6 py-4 text-center">สถานะ</th>
                <th class="px-6 py-4 text-right">งบประมาณ</th>
                <th class="px-6 py-4 text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="project in projects.data" :key="project.id" @click="goToDetail(project.id)" class="hover:bg-purple-50 cursor-pointer transition-colors group">
                <td class="px-6 py-4 font-bold text-purple-700">{{ project.code }}</td>
                <td class="px-6 py-4">
                    <div class="font-medium text-gray-800">{{ project.name }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">PM: {{ project.manager?.name || '-' }}</div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span :class="{'bg-green-100 text-green-800': project.status === 'ongoing', 'bg-red-100 text-red-800': project.status === 'late', 'bg-blue-100 text-blue-800': project.status === 'completed', 'bg-gray-100 text-gray-800': project.status === 'draft'}" class="px-2 py-1 text-xs rounded-full font-bold border">
                    {{ statusLabels[project.status] || project.status }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right text-gray-700 font-mono">{{ formatCurrency(project.contract_amount) }}</td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                    <button @click="(e) => openEditModal(project, e)" class="p-1.5 text-yellow-600 hover:bg-yellow-100 rounded" title="แก้ไข"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg></button>
                    <button @click="(e) => handleDelete(project.id, e)" class="p-1.5 text-red-600 hover:bg-red-100 rounded" title="ลบ"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
                    </div>
                </td>
                </tr>
                <tr v-if="projects.data.length === 0"><td colspan="5" class="text-center py-10 text-gray-400">ไม่พบข้อมูลโครงการ</td></tr>
            </tbody>
            </table>

            <div v-if="projects.last_page > 1" class="px-6 py-4 border-t border-gray-200 flex justify-between items-center bg-gray-50">
                <span class="text-sm text-gray-500">แสดงหน้า {{ projects.current_page }} จาก {{ projects.last_page }}</span>
                <div class="flex gap-1">
                    <button @click="changePage(projects.current_page - 1)" :disabled="projects.current_page === 1" class="px-3 py-1 rounded border bg-white hover:bg-gray-100 disabled:opacity-50 text-sm">ก่อนหน้า</button>
                    <button v-for="page in displayedPages" :key="page" @click="changePage(page)" :class="{'bg-purple-600 text-white border-purple-600': page === projects.current_page, 'bg-white text-gray-700 hover:bg-gray-100': page !== projects.current_page}" class="px-3 py-1 rounded border text-sm min-w-[32px]">
                        {{ page }}
                    </button>
                    <button @click="changePage(projects.current_page + 1)" :disabled="projects.current_page === projects.last_page" class="px-3 py-1 rounded border bg-white hover:bg-gray-100 disabled:opacity-50 text-sm">ถัดไป</button>
                </div>
            </div>
        </div>
      </div>

      <ProjectModal :isOpen="showModal" :project="projectToEdit" @close="showModal = false" @saved="handleSave" />
    </div>
  </AppLayout>
</template>
