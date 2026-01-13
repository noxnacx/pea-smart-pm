<script setup>
import AppLayout from '../Components/AppLayout.vue'; // <--- 1. Import Layout หลัก
import GanttChart from '../Components/GanttChart.vue';
import TaskModal from '../Components/TaskModal.vue';
import ProjectModal from '../Components/ProjectModal.vue';
import PaymentManager from '../Components/PaymentManager.vue';
import ProgressModal from '../Components/ProgressModal.vue';
import TaskHistoryModal from '../Components/TaskHistoryModal.vue';
import FileModal from '../Components/FileModal.vue';
import TeamMemberModal from '../Components/TeamMemberModal.vue';
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const project = ref({});
const budgetSummary = ref({});
const lastUpdateInfo = ref(null);
const riskAnalysis = ref(null);

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
const showTeamModal = ref(false);
const taskToEdit = ref(null);
const selectedTaskHistory = ref(null);

const fetchProjectDetail = async () => {
  try {
    const id = route.params.id;
    const response = await axios.get(`/api/projects/${id}`);
    project.value = response.data.project;
    budgetSummary.value = response.data.budget_summary;
    lastUpdateInfo.value = response.data.last_update;
    riskAnalysis.value = response.data.risk_analysis;
    loading.value = false;
  } catch (error) {
    console.error("Error fetching project:", error);
    loading.value = false;
  }
};

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
  } catch (error) { alert('บันทึกงานไม่สำเร็จ'); }
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

const handleRemoveMember = async (userId) => {
  if(!confirm('ต้องการลบสมาชิกคนนี้ออกจากทีม?')) return;
  try {
      await axios.delete(`/api/projects/${project.value.id}/members/${userId}`);
      fetchProjectDetail();
  } catch(e) { alert('ลบไม่สำเร็จ'); }
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

onMounted(() => { fetchProjectDetail(); });
</script>

<template>
  <AppLayout>

    <div class="space-y-6"> <div class="mb-4">
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

                      <button @click="showProjectModal = true" class="text-gray-400 hover:text-purple-600 p-1" title="แก้ไขโครงการ">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                      </button>

                      <button @click="showDocsModal = true" class="ml-2 flex items-center gap-1 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg text-sm shadow-sm transition-all hover:text-purple-700 hover:border-purple-300">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                          เอกสารสัญญา
                      </button>
                  </div>

                  <div class="mt-4 flex flex-col md:flex-row md:items-center gap-4 border-t border-b py-4 border-gray-100">
                      <div class="flex items-center gap-2">
                          <span class="text-sm text-gray-500 font-medium">ผู้จัดการ:</span>
                          <div class="flex items-center gap-2 bg-purple-50 px-3 py-1 rounded-full border border-purple-100">
                              <div class="w-6 h-6 rounded-full bg-purple-500 text-white flex items-center justify-center text-xs font-bold shadow-sm">
                                 {{ project.manager?.name?.charAt(0).toUpperCase() || 'P' }}
                              </div>
                              <span class="text-sm font-bold text-gray-700">{{ project.manager?.name || '-' }}</span>
                          </div>
                      </div>

                      <div class="h-6 w-px bg-gray-300 hidden md:block"></div>

                      <div class="flex items-center gap-2">
                          <span class="text-sm text-gray-500 font-medium">ทีมงาน:</span>
                          <div class="flex -space-x-2 overflow-hidden items-center p-1">
                              <div v-for="member in project.members" :key="member.id"
                                   class="inline-block h-8 w-8 rounded-full ring-2 ring-white bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 cursor-pointer relative group shadow-sm"
                                   :title="member.name">
                                   {{ member.name.charAt(0).toUpperCase() }}
                                   <button @click="handleRemoveMember(member.id)" class="absolute inset-0 bg-red-500/90 text-white flex items-center justify-center opacity-0 hover:opacity-100 rounded-full transition-all font-bold text-lg shadow-inner">×</button>
                              </div>

                              <button @click="showTeamModal = true" class="ml-2 h-8 w-8 rounded-full ring-2 ring-gray-200 bg-white border border-gray-300 hover:bg-gray-50 flex items-center justify-center text-gray-400 hover:text-purple-600 transition-colors shadow-sm" title="เพิ่มสมาชิก">
                                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                              </button>
                          </div>
                      </div>
                  </div>

                  <div class="mt-4 flex flex-col md:flex-row md:items-center gap-4">
                      <div>
                          <p class="text-sm text-gray-500 mb-1">ความก้าวหน้าสะสม:</p>
                          <div class="flex items-center gap-3">
                              <span class="font-bold text-blue-600 text-2xl">{{ project.progress_actual }}%</span>
                              <button @click="showProgressModal = true" class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded-md transition-colors font-medium">อัปเดต</button>
                          </div>
                          <p v-if="lastUpdateInfo" class="text-xs text-gray-400 mt-1">
                              ล่าสุด: {{ formatDateTime(lastUpdateInfo.date_logged) }} โดย {{ lastUpdateInfo.user?.name || 'ไม่ระบุ' }}
                          </p>
                      </div>

                      <div v-if="riskAnalysis" class="flex-1 max-w-md p-3 rounded-lg border flex items-center gap-3 transition-colors"
                          :class="{
                          'bg-red-50 border-red-200': riskAnalysis.level === 'high',
                          'bg-yellow-50 border-yellow-200': riskAnalysis.level === 'medium',
                          'bg-green-50 border-green-200': riskAnalysis.level === 'low',
                          'bg-gray-50 border-gray-200': riskAnalysis.color === 'gray'
                          }">
                          <div :class="{
                              'text-red-600': riskAnalysis.level === 'high',
                              'text-yellow-600': riskAnalysis.level === 'medium',
                              'text-green-600': riskAnalysis.level === 'low',
                              'text-gray-400': riskAnalysis.color === 'gray'
                          }">
                              <svg v-if="riskAnalysis.level !== 'low' && riskAnalysis.color !== 'gray'" xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                          </div>
                          <div class="flex-1">
                              <p class="text-xs font-bold text-gray-700 uppercase">สถานะความเสี่ยง: {{ riskAnalysis.label }}</p>
                              <p class="text-[10px] text-gray-500">
                                  แผนควรได้ {{ riskAnalysis.expected }}% |
                                  <span :class="riskAnalysis.gap > 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold'">
                                      Gap: {{ riskAnalysis.gap }}%
                                  </span>
                              </p>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
           <h3 class="font-bold text-xl text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
             <span>💰</span> การบริหารงบประมาณและการเบิกจ่าย
           </h3>
           <PaymentManager :projectId="project.id" :contractAmount="project.contract_amount" />
        </div>

        <div v-if="project.tasks && project.tasks.length > 0">
           <GanttChart :tasks="project.tasks" />
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="font-bold text-xl text-gray-800 flex items-center gap-2">
              <span>📅</span> แผนการดำเนินงาน (Tasks)
            </h3>
            <button @click="openCreateTask" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 text-sm shadow-sm transition-all active:scale-95">
               <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg> เพิ่มงาน
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                  <th class="px-4 py-3 rounded-tl-lg">ชื่องาน</th>
                  <th class="px-4 py-3 text-center">ผู้บันทึก</th>
                  <th class="px-4 py-3 text-center">น้ำหนัก</th>
                  <th class="px-4 py-3 text-center">ความคืบหน้า</th>
                  <th class="px-4 py-3">เริ่ม-สิ้นสุด</th>
                  <th class="px-4 py-3 rounded-tr-lg text-right">จัดการ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="task in project.tasks" :key="task.id" class="hover:bg-purple-50 transition-colors">
                  <td class="px-4 py-3 font-medium">
                      <button @click="openTaskHistory(task)" class="text-gray-800 hover:text-purple-600 hover:underline text-left font-bold transition-colors flex items-center gap-2">
                         {{ task.name }}
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                      </button>
                  </td>
                  <td class="px-4 py-3 text-center">
                      <div v-if="task.user">
                          <div class="text-sm text-gray-700 font-medium">{{ task.user.name }}</div>
                          <div class="text-[10px] text-gray-400">เมื่อ {{ formatDateTime(task.updated_at) }}</div>
                      </div>
                      <span v-else class="text-xs text-gray-300">-</span>
                  </td>
                  <td class="px-4 py-3 text-center text-gray-600">{{ task.weight }}%</td>
                  <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 text-xs rounded-full font-bold" :class="task.progress==100?'bg-green-100 text-green-800':'bg-gray-100 text-gray-600'">{{ task.progress==100?'เสร็จสิ้น':task.progress+'%' }}</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(task.start_date) }} - {{ formatDate(task.end_date) }}</td>
                  <td class="px-4 py-3 text-right space-x-1">
                    <button @click="openEditTask(task)" class="p-1 text-yellow-500 hover:bg-yellow-50 rounded transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg></button>
                    <button @click="handleDeleteTask(task.id)" class="p-1 text-red-500 hover:bg-red-50 rounded transition-colors"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
                  </td>
                </tr>
                <tr v-if="!project.tasks?.length"><td colspan="6" class="text-center py-8 text-gray-400">ยังไม่มีข้อมูลงานในโครงการนี้</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <TaskModal :isOpen="showTaskModal" :projectId="project.id" :editData="taskToEdit" @close="showTaskModal=false" @saved="handleSaveTask" />
        <ProjectModal :isOpen="showProjectModal" :project="project" @close="showProjectModal=false" @saved="handleUpdateProject" />
        <ProgressModal :isOpen="showProgressModal" :projectId="project.id" @close="showProgressModal=false" @saved="handleUpdateProgress" />
        <TaskHistoryModal :isOpen="showHistoryModal" :task="selectedTaskHistory" @close="showHistoryModal=false" />

        <FileModal
            :isOpen="showDocsModal"
            title="📂 เอกสารสัญญา / TOR / ข้อมูลโครงการ"
            attachableType="App\Models\Project"
            :attachableId="project.id"
            @close="showDocsModal = false"
        />

        <TeamMemberModal
            :isOpen="showTeamModal"
            :projectId="project.id"
            @close="showTeamModal = false"
            @saved="fetchProjectDetail"
        />

      </div>
    </div>
  </AppLayout>
</template>
