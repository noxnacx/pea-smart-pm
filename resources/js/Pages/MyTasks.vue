<script setup>
import AppLayout from '../Components/AppLayout.vue';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const tasks = ref([]);
const loading = ref(true);
const filter = ref('all'); // time filter
const search = ref(''); // text search
const projectFilter = ref(''); // project specific

// Pagination
const currentPage = ref(1);
const itemsPerPage = 10;

const fetchMyTasks = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/my-tasks');
        tasks.value = res.data.filter(t => t.progress < 100);
    } catch (e) { console.error(e); }
    loading.value = false;
};

// Filter Logic
const uniqueProjects = computed(() => {
    const projects = tasks.value.map(t => t.project).filter(p => !!p);
    return [...new Map(projects.map(p => [p.id, p])).values()]; // unique by id
});

const filteredTasks = computed(() => {
    const today = new Date().setHours(0,0,0,0);
    const endOfMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).setHours(23, 59, 59, 999);

    return tasks.value.filter(task => {
        const endDate = new Date(task.end_date).getTime();
        const matchesSearch = task.name.toLowerCase().includes(search.value.toLowerCase());
        const matchesProject = projectFilter.value ? task.project_id === projectFilter.value : true;

        if (!matchesSearch || !matchesProject) return false;

        if (filter.value === 'late') return endDate < today;
        if (filter.value === 'this_month') return endDate >= today && endDate <= endOfMonth;
        if (filter.value === 'future') return endDate > endOfMonth;
        return true;
    });
});

const paginatedTasks = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    return filteredTasks.value.slice(start, start + itemsPerPage);
});

const totalPages = computed(() => Math.ceil(filteredTasks.value.length / itemsPerPage));
const changePage = (page) => { if (page >= 1 && page <= totalPages.value) currentPage.value = page; };
watch([filter, search, projectFilter], () => { currentPage.value = 1; });

// Stats & Helpers
const stats = computed(() => {
    const today = new Date().setHours(0,0,0,0);
    const endOfMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).setHours(23, 59, 59, 999);
    let late = 0, thisMonth = 0, future = 0;
    tasks.value.forEach(t => {
        const end = new Date(t.end_date).getTime();
        if (end < today) late++; else if (end <= endOfMonth) thisMonth++; else future++;
    });
    return { late, thisMonth, future, all: tasks.value.length };
});

const getStatusBadge = (task) => {
    const today = new Date().setHours(0,0,0,0);
    const end = new Date(task.end_date).getTime();
    if (end < today) return { label: 'ล่าช้า', class: 'bg-red-100 text-red-700' };
    const endOfMonth = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).setHours(23, 59, 59, 999);
    if (end <= endOfMonth) return { label: 'เดือนนี้', class: 'bg-orange-100 text-orange-700' };
    return { label: 'อนาคต', class: 'bg-blue-100 text-blue-700' };
};

const formatDate = (d) => new Date(d).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
const goToProject = (projectId) => { if(projectId) router.push(`/project/${projectId}`); };

onMounted(fetchMyTasks);
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">📋 งานที่ต้องทำ (My Tasks)</h1>
                <p class="text-sm text-gray-500">ติดตามงานคงค้างที่คุณรับผิดชอบ</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <button @click="filter = 'all'" :class="filter === 'all' ? 'ring-2 ring-purple-500 bg-purple-50' : 'bg-white'" class="p-4 rounded-xl border shadow-sm text-left transition-all hover:shadow-md">
                <div class="text-gray-500 text-xs font-bold uppercase">งานค้างทั้งหมด</div>
                <div class="text-2xl font-bold text-gray-800">{{ stats.all }}</div>
            </button>
            <button @click="filter = 'late'" :class="filter === 'late' ? 'ring-2 ring-red-500 bg-red-50' : 'bg-white'" class="p-4 rounded-xl border shadow-sm text-left transition-all hover:shadow-md">
                <div class="text-red-500 text-xs font-bold uppercase">🔥 ล่าช้า</div>
                <div class="text-2xl font-bold text-red-600">{{ stats.late }}</div>
            </button>
            <button @click="filter = 'this_month'" :class="filter === 'this_month' ? 'ring-2 ring-orange-500 bg-orange-50' : 'bg-white'" class="p-4 rounded-xl border shadow-sm text-left transition-all hover:shadow-md">
                <div class="text-orange-500 text-xs font-bold uppercase">📅 ภายในเดือนนี้</div>
                <div class="text-2xl font-bold text-orange-600">{{ stats.thisMonth }}</div>
            </button>
            <button @click="filter = 'future'" :class="filter === 'future' ? 'ring-2 ring-blue-500 bg-blue-50' : 'bg-white'" class="p-4 rounded-xl border shadow-sm text-left transition-all hover:shadow-md">
                <div class="text-blue-500 text-xs font-bold uppercase">🚀 เดือนหน้า++</div>
                <div class="text-2xl font-bold text-blue-600">{{ stats.future }}</div>
            </button>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4">
            <div class="flex-1 relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input v-model="search" type="text" placeholder="ค้นหาชื่องาน..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div class="w-full md:w-64">
                <select v-model="projectFilter" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
                    <option value="">ทุกโครงการ</option>
                    <option v-for="p in uniqueProjects" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div v-if="loading" class="p-10 text-center text-gray-500 animate-pulse">กำลังโหลดรายการงาน...</div>
            <div v-else>
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                        <tr>
                            <th class="px-6 py-4">ชื่องาน / โครงการ</th>
                            <th class="px-6 py-4 text-center">สถานะ</th>
                            <th class="px-6 py-4 text-center">กำหนดส่ง</th>
                            <th class="px-6 py-4 text-center">ความคืบหน้า</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="task in paginatedTasks" :key="task.id" @click="goToProject(task.project_id)" class="hover:bg-purple-50 transition-colors group cursor-pointer">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800 group-hover:text-purple-700 transition-colors">{{ task.name }}</div>
                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                    📁 {{ task.project?.name || 'ไม่ระบุโครงการ' }}
                                    <span class="bg-gray-100 px-1.5 rounded text-[10px] border">{{ task.project?.code }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="getStatusBadge(task).class" class="px-2 py-1 text-xs rounded-full font-bold whitespace-nowrap border border-opacity-20">
                                    {{ getStatusBadge(task).label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-medium" :class="getStatusBadge(task).label === 'ล่าช้า' ? 'text-red-600' : 'text-gray-600'">
                                {{ formatDate(task.end_date) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500" :style="{ width: task.progress + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 w-8 text-right">{{ task.progress }}%</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="paginatedTasks.length === 0"><td colspan="4" class="py-10 text-center text-gray-400 italic">- ไม่มีรายการงาน -</td></tr>
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
  </AppLayout>
</template>
