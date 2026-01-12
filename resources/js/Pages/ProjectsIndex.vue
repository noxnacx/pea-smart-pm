<script setup>
import AppLayout from '../Components/AppLayout.vue';
import ProjectModal from '../Components/ProjectModal.vue';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(true);
const projects = ref({ data: [] });
const search = ref('');
const statusFilter = ref('all');
const showModal = ref(false);
const projectToEdit = ref(null);

// ดึงข้อมูลโครงการ
const fetchProjects = async (page = 1) => {
  loading.value = true;
  try {
    const response = await axios.get('/api/projects', {
      params: {
        page: page,
        search: search.value,
        status: statusFilter.value
      }
    });
    projects.value = response.data;
    loading.value = false;
  } catch (error) {
    console.error("Error:", error);
    loading.value = false;
  }
};

const goToDetail = (id) => {
  router.push(`/project/${id}`);
};

const openCreateModal = () => {
  projectToEdit.value = null;
  showModal.value = true;
};

const openEditModal = (project, event) => {
  event.stopPropagation();
  projectToEdit.value = project;
  showModal.value = true;
};

const handleDelete = async (id, event) => {
  event.stopPropagation();
  if (!confirm('ยืนยันที่จะลบโครงการนี้? (ข้อมูลทั้งหมดจะหายไป)')) return;

  try {
    await axios.delete(`/api/projects/${id}`);
    fetchProjects();
    alert('ลบโครงการเรียบร้อย');
  } catch (error) {
    alert('ลบไม่สำเร็จ (อาจยังไม่ได้เพิ่ม Route Delete ใน api.php)');
  }
};

const handleSave = async (formData) => {
  try {
    if (formData.id) {
        await axios.put(`/api/projects/${formData.id}`, formData); // แก้ตรงนี้ให้ใช้ formData.id ตรงๆ
    } else {
        await axios.post('/api/projects', formData);
    }
    showModal.value = false;
    fetchProjects();
  } catch (error) {
    alert('บันทึกไม่สำเร็จ');
  }
};

const formatCurrency = (val) => new Intl.NumberFormat('th-TH').format(val || 0);

// Watch Search (Debounce แบบ Manual ไม่ต้องใช้ Lodash)
let timeout = null;
watch([search, statusFilter], () => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    fetchProjects(1);
  }, 500);
});

onMounted(() => {
  fetchProjects();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-800">จัดการโครงการทั้งหมด</h1>
        <button @click="openCreateModal" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition-all active:scale-95">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
          สร้างโครงการใหม่
        </button>
      </div>

      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4">
        <div class="flex-1 relative">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          <input
            v-model="search"
            type="text"
            placeholder="ค้นหาชื่อโครงการ หรือ รหัส..."
            class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 border-gray-300"
          >
        </div>
        <div class="w-full md:w-48">
          <select v-model="statusFilter" class="w-full py-2 px-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 border-gray-300 bg-white">
            <option value="all">สถานะ: ทั้งหมด</option>
            <option value="ongoing">กำลังดำเนินการ</option>
            <option value="late">ล่าช้า</option>
            <option value="completed">เสร็จสิ้น</option>
            <option value="draft">ร่าง (Draft)</option>
          </select>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div v-if="loading" class="text-center py-12 text-gray-500">กำลังโหลดข้อมูล...</div>

        <table v-else class="w-full text-left">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-6 py-3">รหัส</th>
              <th class="px-6 py-3">ชื่อโครงการ</th>
              <th class="px-6 py-3 text-center">สถานะ</th>
              <th class="px-6 py-3 text-right">งบประมาณ</th>
              <th class="px-6 py-3 text-right">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="project in projects.data" :key="project.id"
                @click="goToDetail(project.id)"
                class="hover:bg-purple-50 cursor-pointer transition-colors group">
              <td class="px-6 py-4 font-medium text-purple-700">{{ project.code }}</td>
              <td class="px-6 py-4 font-medium text-gray-800">
                {{ project.name }}
                <div class="text-xs text-gray-500 font-normal mt-0.5">PM: {{ project.manager?.name || '-' }}</div>
              </td>
              <td class="px-6 py-4 text-center">
                <span :class="{
                  'bg-green-100 text-green-800': project.status === 'ongoing',
                  'bg-red-100 text-red-800': project.status === 'late',
                  'bg-blue-100 text-blue-800': project.status === 'completed',
                  'bg-gray-100 text-gray-800': project.status === 'draft'
                }" class="px-2 py-1 text-xs rounded-full font-bold">
                  {{ project.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right text-gray-600">{{ formatCurrency(project.contract_amount) }}</td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="(e) => openEditModal(project, e)" class="p-1 text-yellow-500 hover:bg-yellow-50 rounded" title="แก้ไข">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                  </button>
                  <button @click="(e) => handleDelete(project.id, e)" class="p-1 text-red-500 hover:bg-red-50 rounded" title="ลบ">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!projects.data || projects.data.length === 0">
               <td colspan="5" class="text-center py-8 text-gray-400">ไม่พบข้อมูลโครงการ</td>
            </tr>
          </tbody>
        </table>

        <div v-if="projects.last_page > 1" class="bg-gray-50 px-6 py-3 flex justify-between items-center border-t">
          <button
            @click="fetchProjects(projects.current_page - 1)"
            :disabled="projects.current_page === 1"
            class="px-3 py-1 rounded border bg-white disabled:opacity-50 text-sm hover:bg-gray-100"
          >
            ก่อนหน้า
          </button>
          <span class="text-sm text-gray-600">หน้า {{ projects.current_page }} จาก {{ projects.last_page }}</span>
          <button
            @click="fetchProjects(projects.current_page + 1)"
            :disabled="projects.current_page === projects.last_page"
            class="px-3 py-1 rounded border bg-white disabled:opacity-50 text-sm hover:bg-gray-100"
          >
            ถัดไป
          </button>
        </div>
      </div>

      <ProjectModal
        :isOpen="showModal"
        :project="projectToEdit"
        @close="showModal = false"
        @saved="handleSave"
      />
    </div>
  </AppLayout>
</template>
