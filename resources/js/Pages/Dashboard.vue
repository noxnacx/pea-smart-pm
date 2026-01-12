<script setup>
import AppLayout from '../Components/AppLayout.vue';
import LineChart from '../Components/LineChart.vue';
import ProjectModal from '../Components/ProjectModal.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(true);
const stats = ref({});
const recentProjects = ref([]);
const sCurveData = ref([]);
const showCreateModal = ref(false);

const fetchDashboardData = async () => {
  try {
    const response = await axios.get('/api/dashboard');
    stats.value = response.data.stats;
    recentProjects.value = response.data.recent_projects;
    sCurveData.value = response.data.chart_scurve;
    loading.value = false;
  } catch (error) {
    console.error("Error loading dashboard:", error);
    loading.value = false;
  }
};

const formatCurrency = (value) => new Intl.NumberFormat('th-TH').format(value);
const goToProject = (id) => router.push(`/project/${id}`);

const handleCreateProject = async (formData) => {
  try {
    await axios.post('/api/projects', formData);
    showCreateModal.value = false;
    fetchDashboardData();
    alert('สร้างโครงการสำเร็จ!');
  } catch (error) { alert('เกิดข้อผิดพลาดในการสร้างโครงการ'); }
};

onMounted(() => { fetchDashboardData(); });
</script>

<template>
  <AppLayout>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Dashboard ภาพรวมโครงการ</h1>
      <button @click="showCreateModal = true" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition-transform active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
        สร้างโครงการใหม่
      </button>
    </div>

    <div v-if="loading" class="text-center py-20 text-gray-500">กำลังโหลดข้อมูล...</div>

    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-shadow">
          <div class="absolute right-0 top-0 h-full w-1 bg-blue-500"></div>
          <div class="text-gray-500 text-sm font-medium">โครงการทั้งหมด</div>
          <div class="text-3xl font-bold text-gray-800 mt-2">{{ stats.total_projects }}</div>
          <div class="text-xs text-blue-500 mt-2 font-medium flex items-center"><span class="bg-blue-50 px-2 py-1 rounded">Update ล่าสุด</span></div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-shadow">
          <div class="absolute right-0 top-0 h-full w-1 bg-purple-500"></div>
          <div class="text-gray-500 text-sm font-medium">งบประมาณรวม</div>
          <div class="text-3xl font-bold text-gray-800 mt-2">{{ formatCurrency(stats.total_budget) }}</div>
          <div class="text-xs text-purple-500 mt-2 font-medium">บาท</div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-shadow">
          <div class="absolute right-0 top-0 h-full w-1 bg-green-500"></div>
          <div class="text-gray-500 text-sm font-medium">กำลังดำเนินการ</div>
          <div class="text-3xl font-bold text-gray-800 mt-2">{{ stats.ongoing }}</div>
          <div class="text-xs text-green-500 mt-2 font-medium">โครงการ</div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition-shadow">
          <div class="absolute right-0 top-0 h-full w-1 bg-red-500"></div>
          <div class="text-gray-500 text-sm font-medium">ล่าช้ากว่าแผน</div>
          <div class="text-3xl font-bold text-gray-800 mt-2">{{ stats.late }}</div>
          <div class="text-xs text-red-500 mt-2 font-medium">ต้องเร่งติดตาม!</div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
        <h3 class="font-bold text-gray-700 mb-6 flex items-center gap-2">
           <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
           แนวโน้มความก้าวหน้า (S-Curve)
        </h3>
        <div v-if="sCurveData && sCurveData.length > 0" class="h-80">
          <LineChart :data="sCurveData" />
        </div>
        <div v-else class="text-center text-gray-400 py-10 bg-gray-50 rounded-lg border border-dashed border-gray-300">
           ไม่มีข้อมูลกราฟ
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
          <h3 class="font-bold text-gray-700">โครงการล่าสุด</h3>
          <span class="text-xs text-purple-600 cursor-pointer hover:underline">ดูทั้งหมด ></span>
        </div>
        <table class="w-full text-left">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-6 py-3 font-semibold">ชื่อโครงการ</th>
              <th class="px-6 py-3 font-semibold">ผู้รับผิดชอบ</th>
              <th class="px-6 py-3 font-semibold">สถานะ</th>
              <th class="px-6 py-3 font-semibold text-right">ความคืบหน้า</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(project, index) in recentProjects" :key="index"
                @click="goToProject(project.id)"
                class="hover:bg-purple-50 cursor-pointer transition-colors">
              <td class="px-6 py-4 font-medium text-gray-800">{{ project.name }}</td>
              <td class="px-6 py-4 text-gray-600">{{ project.manager }}</td>
              <td class="px-6 py-4">
                <span :class="{
                  'bg-green-100 text-green-700 ring-1 ring-green-600/20': project.status === 'ongoing',
                  'bg-red-100 text-red-700 ring-1 ring-red-600/20': project.status === 'late',
                  'bg-blue-100 text-blue-700 ring-1 ring-blue-600/20': project.status === 'completed',
                  'bg-gray-100 text-gray-700 ring-1 ring-gray-600/20': project.status === 'draft'
                }" class="px-2.5 py-1 text-xs rounded-full font-medium inline-block min-w-[80px] text-center">
                  {{ project.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-3">
                  <span class="text-sm font-bold text-gray-700 w-8">{{ project.progress }}%</span>
                  <div class="w-24 bg-gray-200 rounded-full h-2">
                    <div class="bg-purple-600 h-2 rounded-full shadow-sm" :style="{ width: project.progress + '%' }"></div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <ProjectModal
        :isOpen="showCreateModal"
        :project="null"
        @close="showCreateModal = false"
        @saved="handleCreateProject"
      />
    </div>
  </AppLayout>
</template>
