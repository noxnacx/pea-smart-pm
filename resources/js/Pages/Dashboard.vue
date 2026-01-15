<script setup>
import AppLayout from '../Components/AppLayout.vue';
import LineChart from '../Components/LineChart.vue';
import DonutChart from '../Components/DonutChart.vue';
import BarChart from '../Components/BarChart.vue';
import ProjectModal from '../Components/ProjectModal.vue';
import { ref, onMounted, nextTick, watch } from 'vue'; // ✅ เพิ่ม watch
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(true);

const defaultStats = {
    total_projects: 0, total_budget: 0, total_paid: 0, budget_usage: 0,
    ongoing: 0, late: 0, completed: 0, my_pending_tasks: 0
};

const stats = ref({ ...defaultStats });
const recentProjects = ref([]);
const criticalProjects = ref([]);
const sCurveData = ref([]);
const sCurveTitle = ref('');
const donutData = ref({ labels: [], series: [] });
const barData = ref({ categories: [], series: [] });

const userRole = ref('user');
const showCreateModal = ref(false);

const fetchDashboardData = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/dashboard');
    const data = response.data || {};

    stats.value = data.stats || { ...defaultStats };
    recentProjects.value = data.recent_projects || [];
    criticalProjects.value = data.critical_projects || [];
    sCurveData.value = data.chart_scurve || [];
    sCurveTitle.value = data.chart_title || '';

    donutData.value = data.chart_donut || { labels: [], series: [] };
    barData.value = data.chart_bar || { categories: [], series: [] };

    userRole.value = data.role || 'user';

  } catch (error) {
    console.error("Dashboard Error:", error);
    stats.value = { ...defaultStats };
  } finally {
    loading.value = false;
  }
};

// ✅ ใช้ Watcher ดักจับเมื่อ loading เปลี่ยนเป็น false (โหลดเสร็จ)
// ไม่ว่าจะใช้เวลา 1 วิ หรือ 10 วิ คำสั่งนี้จะทำงานเมื่อข้อมูลพร้อมเสมอ
watch(loading, (isLoading) => {
    if (!isLoading) {
        nextTick(() => {
            // สั่ง trigger resize event เพื่อให้กราฟคำนวณความกว้างใหม่ให้พอดีกับกรอบ
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 300);
        });
    }
});

const formatCurrency = (val) => new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB', maximumFractionDigits: 0 }).format(val || 0);
const goToProject = (id) => router.push(`/project/${id}`);

const handleCreateProject = async (formData) => {
    try {
        await axios.post('/api/projects', formData);
        showCreateModal.value = false;
        fetchDashboardData();
        alert('สร้างโครงการสำเร็จ!');
    } catch (e) { alert('เกิดข้อผิดพลาดในการสร้างโครงการ'); }
};

onMounted(() => fetchDashboardData());
</script>

<template>
  <AppLayout>
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">
          {{ (userRole === 'admin') ? '🌍 ภาพรวมองค์กร (Executive View)' : '👋 สวัสดี! มาดูงานของคุณกัน' }}
        </h1>
        <p class="text-sm text-gray-500">อัปเดตสถานะล่าสุดเมื่อ: {{ new Date().toLocaleDateString('th-TH') }}</p>
      </div>
      <button @click="showCreateModal = true" class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-5 py-2.5 rounded-xl shadow-md flex items-center gap-2 transition-all transform hover:scale-105">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
        สร้างโครงการ
      </button>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-20 text-gray-500 animate-pulse">
        <div class="w-10 h-10 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mb-4"></div>
        กำลังประมวลผลข้อมูล...
    </div>

    <div v-else class="space-y-6">

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div @click="router.push('/projects')" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between group hover:border-purple-200 transition-all cursor-pointer hover:shadow-md">
           <div>
              <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">โครงการทั้งหมด</p>
              <h3 class="text-3xl font-extrabold text-gray-800 mt-1">{{ stats?.total_projects ?? 0 }}</h3>
              <p class="text-xs text-green-600 mt-1 bg-green-50 inline-block px-1.5 py-0.5 rounded">Completed: {{ stats?.completed ?? 0 }}</p>
           </div>
           <div class="p-3 bg-purple-50 rounded-xl text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
           </div>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-between hover:border-blue-200 transition-all">
           <div class="flex justify-between items-start">
              <div>
                 <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">เบิกจ่ายสะสม / งบรวม</p>
                 <h3 class="text-xl font-extrabold text-gray-800 mt-1">{{ formatCurrency(stats?.total_paid) }}</h3>
                 <p class="text-xs text-gray-400">จาก {{ formatCurrency(stats?.total_budget) }}</p>
              </div>
              <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
           </div>
           <div class="w-full bg-gray-100 rounded-full h-1.5 mt-3">
              <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-1000" :style="{ width: (stats?.budget_usage || 0) + '%' }"></div>
           </div>
        </div>
        <div @click="router.push('/my-tasks')" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between cursor-pointer hover:shadow-md hover:border-orange-200 transition-all">
           <div>
              <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">งานค้างของคุณ</p>
              <h3 class="text-3xl font-extrabold text-gray-800 mt-1">{{ stats?.my_pending_tasks ?? 0 }}</h3>
              <p class="text-xs text-orange-500 mt-1 font-medium">Click เพื่อจัดการ</p>
           </div>
           <div class="p-3 bg-orange-50 rounded-xl text-orange-500">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
           </div>
        </div>
        <div @click="router.push({ path: '/projects', query: { status: 'late' } })" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between hover:border-red-200 transition-all cursor-pointer hover:shadow-md">
           <div>
              <p class="text-gray-500 text-xs font-bold uppercase tracking-wider">โครงการล่าช้า</p>
              <h3 class="text-3xl font-extrabold text-gray-800 mt-1">{{ stats?.late ?? 0 }}</h3>
              <p class="text-xs text-red-500 mt-1 font-bold">ต้องเร่งติดตาม!</p>
           </div>
           <div class="p-3 bg-red-50 rounded-xl text-red-500">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
           </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 min-w-0 overflow-hidden">
              <div class="flex justify-between items-center mb-6">
                  <div>
                      <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                          กราฟ S-Curve (โครงการหลัก)
                      </h3>
                      <p class="text-xs text-gray-500 mt-1" v-if="sCurveTitle">โครงการ: {{ sCurveTitle }}</p>
                  </div>
              </div>
              <div v-if="sCurveData && sCurveData.length > 0" class="w-full">
                  <LineChart :key="sCurveData.length" :data="sCurveData" class="w-full" />
              </div>
              <div v-else class="h-64 flex flex-col items-center justify-center bg-gray-50 rounded-xl border border-dashed border-gray-300 text-gray-400">
                  <p>ยังไม่มีข้อมูลความก้าวหน้าโครงการ</p>
              </div>
          </div>

          <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col min-w-0 overflow-hidden">
              <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></svg>
                  สัดส่วนสถานะโครงการ
              </h3>
              <div class="flex-1 flex items-center justify-center w-full">
                  <DonutChart :key="donutData.series.length" :labels="donutData.labels" :series="donutData.series" />
              </div>
          </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 min-w-0 overflow-hidden">
            <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                งบประมาณแยกตามแผนงาน (Top 5)
            </h3>
            <div v-if="barData.series.length > 0" class="w-full">
                <BarChart :key="barData.series.length" :categories="barData.categories" :series="barData.series" class="w-full" />
            </div>
            <div v-else class="h-40 flex items-center justify-center text-gray-400 border border-dashed rounded-lg">
                ไม่มีข้อมูลแผนงาน
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 min-w-0">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                โครงการที่ต้องจับตา (Late)
            </h3>
            <div v-if="criticalProjects.length > 0" class="space-y-3">
                <div v-for="cp in criticalProjects" :key="cp.id" @click="goToProject(cp.id)" class="p-3 rounded-xl border border-red-100 bg-red-50/30 hover:bg-red-50 cursor-pointer transition-colors">
                    <div class="text-sm font-bold text-gray-700 truncate">{{ cp.name }}</div>
                    <div class="flex justify-between items-center mt-2">
                        <div class="w-full bg-gray-200 rounded-full h-1.5 mr-3">
                            <div class="bg-red-500 h-1.5 rounded-full" :style="{ width: cp.progress + '%' }"></div>
                        </div>
                        <span class="text-xs font-bold text-red-600">{{ cp.progress }}%</span>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-6 text-gray-400 text-sm bg-gray-50 rounded-xl border border-dashed">
                ไม่มีโครงการล่าช้า 👍
            </div>
        </div>
      </div>
    </div>

    <ProjectModal :isOpen="showCreateModal" :project="null" @close="showCreateModal = false" @saved="handleCreateProject" />
  </AppLayout>
</template>
