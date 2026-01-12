<script setup>
import GanttChart from '../Components/GanttChart.vue';
import TaskModal from '../Components/TaskModal.vue';
import ProjectModal from '../Components/ProjectModal.vue'; // <--- 1. Import ProjectModal
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const project = ref({});
const budgetSummary = ref({});

// Control Modals
const showTaskModal = ref(false);
const showProjectModal = ref(false); // <--- 2. ตัวแปรเปิดปิด Project Modal
const taskToEdit = ref(null);

const fetchProjectDetail = async () => {
  try {
    const id = route.params.id;
    const response = await axios.get(`/api/projects/${id}`);
    project.value = response.data.project;
    budgetSummary.value = response.data.budget_summary;
    loading.value = false;
  } catch (error) {
    console.error("Error fetching project:", error);
    loading.value = false;
  }
};

// --- Task Functions ---
const openCreateTask = () => { taskToEdit.value = null; showTaskModal.value = true; };
const openEditTask = (task) => { taskToEdit.value = task; showTaskModal.value = true; };

const handleSaveTask = async (taskData) => {
  try {
    if (taskData.id) await axios.put(`/api/tasks/${taskData.id}`, taskData);
    else await axios.post('/api/tasks', taskData);
    showTaskModal.value = false;
    fetchProjectDetail();
    alert('บันทึกงานสำเร็จ!');
  } catch (error) { alert('บันทึกงานไม่สำเร็จ'); }
};

const handleDeleteTask = async (id) => {
  if (!confirm('ยืนยันลบงานนี้?')) return;
  try { await axios.delete(`/api/tasks/${id}`); fetchProjectDetail(); }
  catch (error) { alert('ลบงานไม่สำเร็จ'); }
};

// --- 3. Project Functions (เพิ่มใหม่) ---
const handleUpdateProject = async (formData) => {
  try {
    await axios.put(`/api/projects/${project.value.id}`, formData);
    showProjectModal.value = false;
    fetchProjectDetail(); // โหลดใหม่เพื่ออัปเดต Header
    alert('แก้ไขโครงการสำเร็จ!');
  } catch (error) {
    alert('แก้ไขไม่สำเร็จ');
  }
};

const formatCurrency = (val) => new Intl.NumberFormat('th-TH').format(val || 0);
const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
};

onMounted(() => { fetchProjectDetail(); });
</script>

<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6">
      <router-link to="/" class="flex items-center text-gray-500 hover:text-blue-600 font-medium w-fit">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
        กลับหน้า Dashboard
      </router-link>
    </div>

    <div v-if="loading" class="text-center py-20 text-gray-500">กำลังโหลดข้อมูลโครงการ...</div>

    <div v-else class="space-y-6">
      <div class="bg-white p-6 rounded-lg shadow flex justify-between items-start">
        <div class="flex-1">
          <div class="flex items-center gap-3 mb-2">
            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-bold">{{ project.code }}</span>
            <span :class="{'text-green-600 bg-green-50': project.status === 'ongoing', 'text-red-600 bg-red-50': project.status === 'late', 'text-blue-600 bg-blue-50': project.status === 'completed'}" class="px-2 py-1 text-xs rounded-full border">{{ project.status }}</span>
          </div>

          <div class="flex items-center gap-2">
            <h1 class="text-3xl font-bold text-gray-800">{{ project.name }}</h1>
            <button @click="showProjectModal = true" class="text-gray-400 hover:text-blue-600 p-1" title="แก้ไขโครงการ">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
            </button>
          </div>

          <p class="text-gray-500 mt-2">ผู้จัดการโครงการ: <span class="font-medium text-gray-700">{{ project.manager?.name || '-' }}</span></p>
          <p class="text-sm text-gray-500 mt-1">ความก้าวหน้าโครงการ: <span class="font-bold text-blue-600">{{ project.progress_actual }}%</span></p>
        </div>

        <div class="text-right bg-gray-50 p-4 rounded border border-gray-100 min-w-[200px]">
          <p class="text-sm text-gray-500">มูลค่าสัญญา</p>
          <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(project.contract_amount) }} บาท</p>
          <div class="mt-2 text-xs text-gray-400">เบิกจ่ายแล้ว: {{ formatCurrency(project.paid_amount) }}</div>
        </div>
      </div>

      <div v-if="project.tasks && project.tasks.length > 0">
         <GanttChart :tasks="project.tasks" />
      </div>

      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4 border-b pb-2">
          <h3 class="font-bold text-xl text-gray-700">📅 แผนการดำเนินงาน (Tasks)</h3>
          <button @click="openCreateTask" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2 text-sm shadow">
             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg> เพิ่มงาน
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
              <tr>
                <th class="px-4 py-3 rounded-tl-lg">ชื่องาน</th>
                <th class="px-4 py-3 text-center">น้ำหนัก</th>
                <th class="px-4 py-3 text-center">ความคืบหน้า</th>
                <th class="px-4 py-3">เริ่ม-สิ้นสุด</th>
                <th class="px-4 py-3 rounded-tr-lg text-right">จัดการ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="task in project.tasks" :key="task.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ task.name }}</td>
                <td class="px-4 py-3 text-center text-gray-500">{{ task.weight }}%</td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-1 text-xs rounded-full" :class="task.progress==100?'bg-green-100 text-green-800':'bg-gray-100'">{{ task.progress==100?'เสร็จสิ้น':task.progress+'%' }}</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(task.start_date) }} - {{ formatDate(task.end_date) }}</td>
                <td class="px-4 py-3 text-right space-x-2">
                  <button @click="openEditTask(task)" class="text-yellow-500 hover:text-yellow-600 p-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg></button>
                  <button @click="handleDeleteTask(task.id)" class="text-red-500 hover:text-red-600 p-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
                </td>
              </tr>
              <tr v-if="!project.tasks?.length"><td colspan="5" class="text-center py-8 text-gray-400">ไม่มีข้อมูล</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <TaskModal :isOpen="showTaskModal" :projectId="project.id" :editData="taskToEdit" @close="showTaskModal=false" @saved="handleSaveTask" />
      <ProjectModal :isOpen="showProjectModal" :project="project" @close="showProjectModal=false" @saved="handleUpdateProject" /> </div>
  </div>
</template>
