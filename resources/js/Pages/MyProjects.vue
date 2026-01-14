<script setup>
import AppLayout from '../Components/AppLayout.vue';
import ProjectModal from '../Components/ProjectModal.vue';
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(true);
const projects = ref({ data: [], current_page: 1, last_page: 1 });
const search = ref('');
const statusFilter = ref('all');
const currentUser = ref(null);
const showModal = ref(false);
const projectToEdit = ref(null);

const fetchMyProjects = async (page = 1) => {
    loading.value = true;
    try {
        if(!currentUser.value) { const userRes = await axios.get('/api/user'); currentUser.value = userRes.data; }
        const response = await axios.get('/api/projects', {
            params: { page, scope: 'my_projects', search: search.value, status: statusFilter.value }
        });
        projects.value = response.data;
    } catch (error) { console.error("Error:", error); }
    loading.value = false;
};

// Pagination Logic
const displayedPages = computed(() => {
  const current = projects.value.current_page;
  const last = projects.value.last_page;
  const delta = 2; const range = [];
  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) range.push(i);
  if (current - delta > 2) range.unshift('...'); if (current + delta < last - 1) range.push('...');
  range.unshift(1); if (last !== 1) range.push(last);
  return range;
});
const changePage = (page) => { if (page !== '...' && page !== projects.value.current_page) fetchMyProjects(page); };

const goToDetail = (id) => router.push(`/project/${id}`);
const openEditModal = (project, event) => { event.stopPropagation(); projectToEdit.value = project; showModal.value = true; };
const handleSave = async (formData) => {
    try { await axios.put(`/api/projects/${formData.id}`, formData); showModal.value = false; fetchMyProjects(); alert('บันทึกสำเร็จ'); }
    catch (e) { alert('บันทึกไม่สำเร็จ'); }
};

const statusLabels = { 'ongoing': 'กำลังดำเนินการ', 'late': 'ล่าช้า', 'completed': 'เสร็จสิ้น', 'draft': 'ร่าง' };
let timeout = null;
watch([search, statusFilter], () => { clearTimeout(timeout); timeout = setTimeout(() => fetchMyProjects(1), 500); });
onMounted(fetchMyProjects);
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">👤 โครงการของฉัน (My Projects)</h1>
            <p class="text-sm text-gray-500">โครงการที่คุณเป็นผู้รับผิดชอบ (PM) หรือเป็นสมาชิกในทีม</p>
        </div>
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
                <th class="px-6 py-4 text-center">บทบาท</th>
                <th class="px-6 py-4 text-center">สถานะ</th>
                <th class="px-6 py-4 text-right">ความคืบหน้า</th>
                <th class="px-6 py-4 text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="p in projects.data" :key="p.id" @click="goToDetail(p.id)" class="hover:bg-purple-50 cursor-pointer transition-colors group">
                <td class="px-6 py-4 font-bold text-purple-700">{{ p.code }}</td>
                <td class="px-6 py-4">
                    <div class="font-medium text-gray-800">{{ p.name }}</div>
                    <div class="text-xs text-gray-500 mt-0.5" v-if="p.manager">PM: {{ p.manager.name }}</div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span v-if="currentUser && p.manager_id === currentUser.id" class="bg-purple-100 text-purple-700 text-[10px] px-2 py-0.5 rounded border border-purple-200 font-bold">Project Manager</span>
                    <span v-else class="bg-gray-100 text-gray-600 text-[10px] px-2 py-0.5 rounded border">Member</span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span :class="{'bg-green-100 text-green-800': p.status === 'ongoing', 'bg-red-100 text-red-800': p.status === 'late', 'bg-blue-100 text-blue-800': p.status === 'completed', 'bg-gray-100 text-gray-800': p.status === 'draft'}" class="px-2 py-1 text-xs rounded-full font-bold border">
                    {{ statusLabels[p.status] || p.status }}
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="font-bold text-gray-700">{{ p.progress_actual || 0 }}%</div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1"><div class="bg-blue-600 h-1.5 rounded-full" :style="{ width: (p.progress_actual||0) + '%' }"></div></div>
                </td>
                <td class="px-6 py-4 text-right">
                    <button v-if="currentUser && (p.manager_id === currentUser.id || currentUser.role === 'admin')" @click="(e) => openEditModal(p, e)" class="p-1.5 text-yellow-500 hover:bg-yellow-50 rounded transition-colors group-hover:opacity-100 opacity-60" title="แก้ไข">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                    </button>
                    <span v-else class="text-xs text-gray-300">View Only</span>
                </td>
                </tr>
                <tr v-if="projects.data.length === 0"><td colspan="6" class="text-center py-10 text-gray-400">คุณยังไม่มีโครงการที่รับผิดชอบ</td></tr>
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
