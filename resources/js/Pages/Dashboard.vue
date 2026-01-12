<script setup>
import LineChart from '../Components/LineChart.vue';
import ProjectModal from '../Components/ProjectModal.vue'; // <--- 1. Import Modal
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(true);
const stats = ref({});
const recentProjects = ref([]);
const sCurveData = ref([]);
const showCreateModal = ref(false); // <--- 2. ตัวแปรเปิดปิด Modal

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

const goToProject = (id) => {
  // แก้ไขให้รองรับกรณี ID มาจาก Database จริง
  router.push(`/project/${id}`);
};

// 3. ฟังก์ชันสร้างโครงการใหม่
const handleCreateProject = async (formData) => {
  try {
    await axios.post('/api/projects', formData);
    showCreateModal.value = false;
    fetchDashboardData(); // โหลดข้อมูลใหม่ หน้าจอจะอัปเดตทันที
    alert('สร้างโครงการสำเร็จ!');
  } catch (error) {
    alert('เกิดข้อผิดพลาดในการสร้างโครงการ');
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>

<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Dashboard ภาพรวมโครงการ</h1>

      <button @click="showCreateModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        สร้างโครงการใหม่
      </button>
    </div>

    <div v-if="loading" class="text-center py-10">กำลังโหลดข้อมูล...</div>

    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
          <div class="text-gray-500 text-sm">โครงการทั้งหมด</div>
          <div class="text-3xl font-bold text-blue-600">{{ stats.total_projects }}</div>
          <div class="text-xs text-gray-400 mt-1">โครงการ</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-purple-500">
          <div class="text-gray-500 text-sm">งบประมาณได้รับจัดสรร</div>
          <div class="text-3xl font-bold text-purple-600">{{ formatCurrency(stats.total_budget) }}</div>
          <div class="text-xs text-gray-400 mt-1">บาท</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
          <div class="text-gray-500 text-sm">กำลังดำเนินการ</div>
          <div class="text-3xl font-bold text-green-600">{{ stats.ongoing }}</div>
          <div class="text-xs text-gray-400 mt-1">โครงการ</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow border-l-4 border-red-500">
          <div class="text-gray-500 text-sm">โครงการล่าช้า</div>
          <div class="text-3xl font-bold text-red-600">{{ stats.late }}</div>
          <div class="text-xs text-gray-400 mt-1">ต้องเร่งติดตาม!</div>
        </div>
      </div>

      <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h3 class="font-bold text-gray-700 mb-4">แนวโน้มความก้าวหน้า (S-Curve)</h3>
        <div v-if="sCurveData && sCurveData.length > 0">
           <LineChart :data="sCurveData" />
        </div>
        <div v-else class="text-center text-gray-400 py-10">
           ไม่มีข้อมูลกราฟ
        </div>
      </div>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
          <h3 class="font-bold text-gray-700">โครงการล่าสุด</h3>
        </div>
        <table class="w-full text-left">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
            <tr>
              <th class="px-6 py-3">ชื่อโครงการ</th>
              <th class="px-6 py-3">ผู้รับผิดชอบ</th>
              <th class="px-6 py-3">สถานะ</th>
              <th class="px-6 py-3 text-right">ความคืบหน้า</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(project, index) in recentProjects" :key="index"
                @click="goToProject(project.id)"
                class="hover:bg-blue-50 cursor-pointer transition-colors">
              <td class="px-6 py-4 font-medium">{{ project.name }}</td>
              <td class="px-6 py-4">{{ project.manager }}</td>
              <td class="px-6 py-4">
                <span :class="{
                  'bg-green-100 text-green-800': project.status === 'ongoing',
                  'bg-red-100 text-red-800': project.status === 'late',
                  'bg-blue-100 text-blue-800': project.status === 'completed',
                  'bg-gray-100 text-gray-800': project.status === 'draft'
                }" class="px-2 py-1 text-xs rounded-full">
                  {{ project.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end">
                  <span class="mr-2">{{ project.progress }}%</span>
                  <div class="w-24 bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: project.progress + '%' }"></div>
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
  </div>
</template>
