<script setup>
import AppLayout from '../Components/AppLayout.vue';
import GanttChart from '../Components/GanttChart.vue';
import TaskModal from '../Components/TaskModal.vue';
import ProjectModal from '../Components/ProjectModal.vue';
import PaymentManager from '../Components/PaymentManager.vue';
import BudgetManager from '../Components/BudgetManager.vue';
import ProgressModal from '../Components/ProgressModal.vue';
import TaskHistoryModal from '../Components/TaskHistoryModal.vue';
import FileModal from '../Components/FileModal.vue';
import CommentSection from '../Components/CommentSection.vue';
import WorkloadView from '../Components/WorkloadView.vue';
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const project = ref({});
const budgetSummary = ref({});
const lastUpdateInfo = ref(null);
const riskAnalysis = ref(null);
const currentUser = ref(null);

// ตัวแปรจัดการ Tab และ Filter
const currentTab = ref('planning');
const taskFilter = ref('all');

// Permission Flags
const canManageProject = ref(false);
const canUpdateWork = ref(false);

const statusLabels = {
  'ongoing': 'กำลังดำเนินการ',
  'late': 'ล่าช้า',
  'completed': 'เสร็จสิ้น',
  'draft': 'ร่าง (Draft)'
};

const showTaskModal = ref(false);
const showProjectModal = ref(false);
const showProgressModal = ref(false);
const showHistoryModal = ref(false);
const showDocsModal = ref(false);
const taskToEdit = ref(null);
const selectedTaskHistory = ref(null);

// ตัวแปรสำหรับระบบสมาชิก (Search Users)
const userSearchQuery = ref('');
const userSearchResults = ref([]);
const isSearchingUser = ref(false);

const fetchCurrentUser = async () => {
    try {
        const res = await axios.get('/api/user');
        currentUser.value = res.data;
    } catch (e) { console.error("Auth error"); }
};

const fetchProjectDetail = async () => {
  try {
    const id = route.params.id;
    const response = await axios.get(`/api/projects/${id}`);

    project.value = response.data.project;
    budgetSummary.value = response.data.budget_summary;
    lastUpdateInfo.value = response.data.last_update;
    riskAnalysis.value = response.data.risk_analysis;

    if (currentUser.value) {
        const isAdmin = currentUser.value.role === 'admin';
        const isPM = project.value.manager_id === currentUser.value.id;
        const isMember = project.value.members?.some(m => m.id === currentUser.value.id);

        canManageProject.value = isAdmin || isPM;
        canUpdateWork.value = isAdmin || isPM || isMember;
    }
    loading.value = false;
  } catch (error) {
    console.error("Error fetching project:", error);
    loading.value = false;
  }
};

// --- Task Stats & Filter ---
const taskStats = computed(() => {
    const tasks = project.value.tasks || [];
    const today = new Date().setHours(0,0,0,0);
    let late = 0, ongoing = 0, completed = 0;
    tasks.forEach(t => {
        if (t.progress == 100) completed++;
        else {
            const endDate = new Date(t.end_date).getTime();
            if (endDate < today) late++;
            else ongoing++;
        }
    });
    return { all: tasks.length, late, ongoing, completed };
});

const filteredTasks = computed(() => {
    const tasks = project.value.tasks || [];
    if (taskFilter.value === 'all') return tasks;
    const today = new Date().setHours(0,0,0,0);
    return tasks.filter(t => {
        if (taskFilter.value === 'completed') return t.progress == 100;
        if (t.progress < 100) {
            const endDate = new Date(t.end_date).getTime();
            if (taskFilter.value === 'late') return endDate < today;
            if (taskFilter.value === 'ongoing') return endDate >= today;
        }
        return false;
    });
});

const getTaskStatus = (task) => {
    if (task.progress == 100) return { label: 'เสร็จสิ้น', class: 'bg-green-100 text-green-700' };
    const today = new Date().setHours(0,0,0,0);
    const end = new Date(task.end_date).getTime();
    if (end < today) return { label: 'ล่าช้า', class: 'bg-red-100 text-red-700' };
    return { label: 'กำลังทำ', class: 'bg-blue-100 text-blue-700' };
};

// --- Member Functions (New) ---
const handleSearchUsers = async () => {
    if (userSearchQuery.value.length < 2) return;
    isSearchingUser.value = true;
    try {
        // เรียก API ค้นหา User (สมมติว่า backend รองรับ ?search=...)
        const res = await axios.get(`/api/users/search?search=${userSearchQuery.value}`);

        // กรองคนที่เป็นสมาชิกอยู่แล้วออก
        const currentMemberIds = project.value.members.map(m => m.id);
        userSearchResults.value = res.data.filter(user => !currentMemberIds.includes(user.id));
    } catch (e) {
        console.error(e);
    }
    isSearchingUser.value = false;
};

const handleAddMember = async (user) => {
    try {
        // ✅ แก้ไข: เพิ่ม role: 'member' เข้าไปใน payload
        await axios.post(`/api/projects/${project.value.id}/members`, {
            user_id: user.id,
            role: 'member' // กำหนด role เริ่มต้นเป็น member
        });

        // รีโหลดข้อมูลโปรเจกต์ใหม่เพื่ออัปเดตรายชื่อ
        await fetchProjectDetail();
        // ลบออกจากผลการค้นหา
        userSearchResults.value = userSearchResults.value.filter(u => u.id !== user.id);
        alert(`เพิ่ม ${user.name} เข้าทีมเรียบร้อย`);
    } catch (e) {
        console.error(e);
        alert('เพิ่มสมาชิกไม่สำเร็จ: ' + (e.response?.data?.message || 'โปรดลองอีกครั้ง'));
    }
};

const handleRemoveMember = async (userId) => {
  if(!confirm('ต้องการลบสมาชิกคนนี้ออกจากทีม?')) return;
  try {
      await axios.delete(`/api/projects/${project.value.id}/members/${userId}`);
      fetchProjectDetail();
  } catch(e) { alert('ลบไม่สำเร็จ'); }
};

// --- Other Actions ---
const openCreateTask = () => { taskToEdit.value = null; showTaskModal.value = true; };
const openEditTask = (task) => { taskToEdit.value = task; showTaskModal.value = true; };
const openTaskHistory = (task) => { selectedTaskHistory.value = task; showHistoryModal.value = true; };

const handleSaveTask = async (taskData) => {
  try {
    if (taskData.id) await axios.put(`/api/tasks/${taskData.id}`, taskData);
    else await axios.post('/api/tasks', taskData);
    showTaskModal.value = false;
    fetchProjectDetail();
    alert('บันทึกงานสำเร็จ!');
  } catch (error) { alert(error.response?.data?.message || 'บันทึกงานไม่สำเร็จ'); }
};

const handleDeleteTask = async (id) => {
  if (!confirm('ยืนยันลบงานนี้?')) return;
  try { await axios.delete(`/api/tasks/${id}`); fetchProjectDetail(); }
  catch (error) { alert('ลบงานไม่สำเร็จ'); }
};

const handleUpdateProject = async (formData) => {
  try {
    await axios.put(`/api/projects/${project.value.id}`, formData);
    showProjectModal.value = false;
    fetchProjectDetail();
    alert('แก้ไขโครงการสำเร็จ!');
  } catch (error) { alert('แก้ไขไม่สำเร็จ'); }
};

const handleUpdateProgress = async (data) => {
  try {
    await axios.post(`/api/projects/${data.projectId}/progress`, data);
    showProgressModal.value = false;
    fetchProjectDetail();
    alert('อัปเดตความก้าวหน้าเรียบร้อย!');
  } catch (e) { alert('เกิดข้อผิดพลาดในการอัปเดต'); }
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatDateTime = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleString('th-TH', { year: '2-digit', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

onMounted(async () => {
    await fetchCurrentUser();
    fetchProjectDetail();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">

      <div class="mb-4">
        <router-link to="/projects" class="flex items-center text-gray-500 hover:text-purple-600 font-medium w-fit transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
          กลับหน้ารายการโครงการ
        </router-link>
      </div>

      <div v-if="loading" class="text-center py-20 text-gray-500">กำลังโหลดข้อมูลโครงการ...</div>

      <div v-else class="space-y-6">

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="flex justify-between items-start">
              <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                      <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full font-bold">{{ project.code }}</span>
                      <span :class="{'text-green-600 bg-green-50': project.status === 'ongoing', 'text-red-600 bg-red-50': project.status === 'late', 'text-blue-600 bg-blue-50': project.status === 'completed'}" class="px-2 py-1 text-xs rounded-full border">{{ statusLabels[project.status] || project.status }}</span>
                  </div>
                  <div class="flex items-center gap-2 flex-wrap">
                      <h1 class="text-3xl font-bold text-gray-800">{{ project.name }}</h1>
                      <button v-if="canManageProject" @click="showProjectModal = true" class="text-gray-400 hover:text-purple-600 p-1" title="แก้ไข">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                      </button>
                      <button @click="showDocsModal = true" class="ml-2 flex items-center gap-1 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg text-sm shadow-sm transition-all hover:text-purple-700 hover:border-purple-300">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                          เอกสารสัญญา
                      </button>
                  </div>
                  <div class="mt-4 flex flex-col md:flex-row md:items-center gap-4">
                      <div>
                          <p class="text-sm text-gray-500 mb-1">ความก้าวหน้าสะสม:</p>
                          <div class="flex items-center gap-3">
                              <span class="font-bold text-blue-600 text-2xl">{{ project.progress_actual }}%</span>
                              <button v-if="canUpdateWork" @click="showProgressModal = true" class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded-md transition-colors font-medium">อัปเดต</button>
                          </div>
                      </div>
                      <div v-if="riskAnalysis" class="flex-1 max-w-md p-3 rounded-lg border flex items-center gap-3" :class="riskAnalysis.level === 'high' ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'">
                          <div class="flex-1">
                              <p class="text-xs font-bold text-gray-700 uppercase">สถานะความเสี่ยง: {{ riskAnalysis.label }}</p>
                              <p class="text-[10px] text-gray-500">Gap: {{ riskAnalysis.gap }}%</p>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
        </div>

        <div class="border-b border-gray-200 mt-6 sticky top-0 bg-gray-50 z-10 pt-2 -mx-4 px-4 md:static md:bg-transparent md:p-0">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="currentTab = 'planning'" :class="[currentTab === 'planning' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors']">📊 แผนงาน</button>
                <button @click="currentTab = 'finance'" :class="[currentTab === 'finance' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors']">💰 การเงิน</button>

                <button @click="currentTab = 'members'" :class="[currentTab === 'members' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors']">👥 สมาชิก</button>

                <button @click="currentTab = 'discussion'" :class="[currentTab === 'discussion' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors']">💬 พูดคุย</button>
            </nav>
        </div>

        <div v-if="currentTab === 'planning'" class="space-y-6 animate-fade-in">
            <div class="flex flex-wrap gap-3">
                <button @click="taskFilter = 'all'" :class="taskFilter === 'all' ? 'bg-gray-800 text-white' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'" class="px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                    📋 ทั้งหมด <span class="bg-gray-200 text-gray-800 text-xs px-2 py-0.5 rounded-full ml-1">{{ taskStats.all }}</span>
                </button>
                <button @click="taskFilter = 'late'" :class="taskFilter === 'late' ? 'bg-red-600 text-white' : 'bg-white text-red-600 border border-red-200 hover:bg-red-50'" class="px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                    🔥 ล่าช้า <span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full ml-1">{{ taskStats.late }}</span>
                </button>
                <button @click="taskFilter = 'ongoing'" :class="taskFilter === 'ongoing' ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-blue-200 hover:bg-blue-50'" class="px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                    ⏱️ กำลังทำ <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full ml-1">{{ taskStats.ongoing }}</span>
                </button>
                <button @click="taskFilter = 'completed'" :class="taskFilter === 'completed' ? 'bg-green-600 text-white' : 'bg-white text-green-600 border border-green-200 hover:bg-green-50'" class="px-4 py-2 rounded-lg text-sm font-bold shadow-sm transition-all flex items-center gap-2">
                    ✅ เสร็จสิ้น <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full ml-1">{{ taskStats.completed }}</span>
                </button>
            </div>

            <WorkloadView :members="project.members" :tasks="filteredTasks" />

            <div v-if="filteredTasks.length > 0">
               <GanttChart :tasks="filteredTasks" />
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="font-bold text-xl text-gray-800 flex items-center gap-2"><span>📅</span> รายการงาน</h3>
                <button v-if="canManageProject" @click="openCreateTask" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 text-sm shadow-sm transition-all">เพิ่มงาน</button>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-left">
                  <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                    <tr>
                      <th class="px-4 py-3 rounded-tl-lg">สถานะ</th>
                      <th class="px-4 py-3">ชื่องาน</th>
                      <th class="px-4 py-3 text-center">ผู้รับผิดชอบ</th>
                      <th class="px-4 py-3 text-center">น้ำหนัก</th>
                      <th class="px-4 py-3 text-center">ความคืบหน้า</th>
                      <th class="px-4 py-3">กำหนดส่ง</th>
                      <th class="px-4 py-3 rounded-tr-lg text-right">จัดการ</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="task in filteredTasks" :key="task.id" class="hover:bg-purple-50 transition-colors">
                      <td class="px-4 py-3">
                          <span :class="getTaskStatus(task).class" class="px-2 py-1 text-xs rounded-full font-bold whitespace-nowrap">
                              {{ getTaskStatus(task).label }}
                          </span>
                      </td>
                      <td class="px-4 py-3 font-medium">
                          <button @click="openTaskHistory(task)" class="text-gray-800 hover:text-purple-600 hover:underline text-left font-bold transition-colors">
                              {{ task.name }}
                          </button>
                          <div v-if="task.predecessor_id" class="flex items-center gap-1 mt-1 text-xs text-red-500 bg-red-50 w-fit px-2 py-0.5 rounded-md border border-red-100">
                               <span>🔒 รอ: {{ project.tasks.find(t => t.id === task.predecessor_id)?.name }}</span>
                          </div>
                      </td>
                      <td class="px-4 py-3 text-center">
                          <div v-if="task.users && task.users.length > 0" class="flex justify-center -space-x-2">
                              <div v-for="user in task.users" :key="user.id" class="w-6 h-6 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold ring-2 ring-white" :title="user.name">
                                  {{ user.name.charAt(0) }}
                              </div>
                          </div>
                          <span v-else class="text-xs text-gray-300">-</span>
                      </td>
                      <td class="px-4 py-3 text-center text-gray-600">{{ task.weight }}%</td>
                      <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 text-xs rounded-full font-bold" :class="task.progress==100?'bg-green-100 text-green-800':'bg-gray-100 text-gray-600'">{{ task.progress }}%</span>
                      </td>
                      <td class="px-4 py-3 text-sm" :class="getTaskStatus(task).label === 'ล่าช้า' ? 'text-red-600 font-bold' : 'text-gray-500'">
                          {{ formatDate(task.end_date) }}
                      </td>
                      <td class="px-4 py-3 text-right space-x-1">
                        <button v-if="canUpdateWork" @click="openEditTask(task)" class="p-1 text-yellow-500 hover:bg-yellow-50 rounded">✏️</button>
                        <button v-if="canManageProject" @click="handleDeleteTask(task.id)" class="p-1 text-red-500 hover:bg-red-50 rounded">🗑️</button>
                      </td>
                    </tr>
                    <tr v-if="filteredTasks.length === 0"><td colspan="7" class="text-center py-8 text-gray-400">ไม่พบงานในสถานะนี้</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
        </div>

        <div v-if="currentTab === 'finance'" class="space-y-6 animate-fade-in">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
               <h3 class="font-bold text-xl text-gray-800 mb-4 border-b pb-2 flex items-center gap-2"><span>💰</span> การบริหารงบประมาณ</h3>
               <PaymentManager :projectId="project.id" :contractAmount="project.contract_amount" />
               <div class="mt-8 border-t pt-6" v-if="project.id">
                    <h3 class="font-bold text-xl text-gray-800 mb-4 flex items-center gap-2"><span>📊</span> BOQ Breakdown</h3>
                    <BudgetManager :projectId="project.id" :contractAmount="project.contract_amount" />
                </div>
            </div>
        </div>

        <div v-if="currentTab === 'members'" class="space-y-6 animate-fade-in">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-if="canManageProject" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 h-fit">
                    <h3 class="font-bold text-lg text-gray-800 mb-4">➕ เพิ่มสมาชิกในทีม</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">ค้นหาด้วยชื่อ หรือ อีเมล</label>
                        <div class="flex gap-2">
                            <input
                                v-model="userSearchQuery"
                                @keyup.enter="handleSearchUsers"
                                type="text"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-purple-500 focus:border-purple-500"
                                placeholder="เช่น admin@pea.co.th..."
                            >
                            <button @click="handleSearchUsers" class="bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="userSearchResults.length > 0" class="border rounded-lg divide-y divide-gray-100 max-h-60 overflow-y-auto">
                        <div v-for="user in userSearchResults" :key="user.id" class="p-3 flex justify-between items-center hover:bg-gray-50">
                            <div>
                                <div class="font-bold text-sm text-gray-800">{{ user.name }}</div>
                                <div class="text-xs text-gray-500">{{ user.email }}</div>
                            </div>
                            <button @click="handleAddMember(user)" class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded font-bold hover:bg-green-200">
                                + เพิ่ม
                            </button>
                        </div>
                    </div>
                    <div v-if="isSearchingUser" class="text-center text-gray-400 text-sm py-4">กำลังค้นหา...</div>
                    <div v-if="!isSearchingUser && userSearchQuery.length > 1 && userSearchResults.length === 0" class="text-center text-gray-400 text-sm py-4">
                        ไม่พบผู้ใช้
                    </div>
                </div>

                <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center gap-2">
                        👥 รายชื่อทีมงานปัจจุบัน <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs">{{ project.members?.length }} คน</span>
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3">ชื่อ-นามสกุล</th>
                                    <th class="px-4 py-3">อีเมล</th>
                                    <th class="px-4 py-3 text-center">บทบาท</th>
                                    <th class="px-4 py-3 text-right">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="project.manager" class="bg-purple-50">
                                    <td class="px-4 py-3 font-bold text-purple-900 flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-purple-200 text-purple-700 flex items-center justify-center text-xs">
                                            {{ project.manager.name.charAt(0) }}
                                        </div>
                                        {{ project.manager.name }} (PM)
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ project.manager.email }}</td>
                                    <td class="px-4 py-3 text-center"><span class="bg-purple-200 text-purple-800 text-xs px-2 py-1 rounded font-bold">Manager</span></td>
                                    <td class="px-4 py-3 text-right text-gray-400">-</td>
                                </tr>
                                <tr v-for="member in project.members" :key="member.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-800 flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs">
                                            {{ member.name.charAt(0) }}
                                        </div>
                                        {{ member.name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ member.email }}</td>
                                    <td class="px-4 py-3 text-center"><span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded">Member</span></td>
                                    <td class="px-4 py-3 text-right">
                                        <button v-if="canManageProject" @click="handleRemoveMember(member.id)" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50" title="ลบออกจากทีม">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="currentTab === 'discussion'" class="animate-fade-in">
            <CommentSection v-if="project.id" type="project" :id="project.id" />
        </div>

        <TaskModal :isOpen="showTaskModal" :projectId="project.id" :editData="taskToEdit" :existingTasks="project.tasks" :members="project.members" @close="showTaskModal=false" @saved="handleSaveTask" />
        <ProjectModal :isOpen="showProjectModal" :project="project" @close="showProjectModal=false" @saved="handleUpdateProject" />
        <ProgressModal :isOpen="showProgressModal" :projectId="project.id" @close="showProgressModal=false" @saved="handleUpdateProgress" />
        <TaskHistoryModal :isOpen="showHistoryModal" :task="selectedTaskHistory" @close="showHistoryModal=false" />
        <FileModal :isOpen="showDocsModal" title="📂 เอกสารสัญญา" attachableType="App\Models\Project" :attachableId="project.id" @close="showDocsModal = false" />

        </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>
