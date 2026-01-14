<script setup>
import AppLayout from '../Components/AppLayout.vue';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const tasks = ref([]);
const loading = ref(true);
const filter = ref('all'); // all, late, this_month, future

// โหลดข้อมูล
const fetchMyTasks = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/my-tasks');
        // กรองเอาเฉพาะงานที่ยังไม่เสร็จ (Progress < 100)
        tasks.value = res.data.filter(t => t.progress < 100);
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

// คำนวณวันสิ้นเดือน
const getEndOfMonth = () => {
    const date = new Date();
    return new Date(date.getFullYear(), date.getMonth() + 1, 0).setHours(23, 59, 59, 999);
};

// คำนวณและกรอง
const filteredTasks = computed(() => {
    const today = new Date().setHours(0,0,0,0);
    const endOfMonth = getEndOfMonth();

    return tasks.value.filter(task => {
        const endDate = new Date(task.end_date).getTime();

        if (filter.value === 'late') return endDate < today;
        // เดือนนี้: ตั้งแต่วันนี้ จนถึงวันสุดท้ายของเดือน
        if (filter.value === 'this_month') return endDate >= today && endDate <= endOfMonth;
        // อนาคต: มากกว่าเดือนนี้ (เดือนหน้าเป็นต้นไป)
        if (filter.value === 'future') return endDate > endOfMonth;

        return true; // case 'all'
    });
});

const stats = computed(() => {
    const today = new Date().setHours(0,0,0,0);
    const endOfMonth = getEndOfMonth();
    let late = 0, thisMonth = 0, future = 0;

    tasks.value.forEach(t => {
        const end = new Date(t.end_date).getTime();
        if (end < today) late++;
        else if (end <= endOfMonth) thisMonth++;
        else future++;
    });

    return { late, thisMonth, future, all: tasks.value.length };
});

// Helper Functions
const getStatusBadge = (task) => {
    const today = new Date().setHours(0,0,0,0);
    const endOfMonth = getEndOfMonth();
    const end = new Date(task.end_date).getTime();

    if (end < today) return { label: 'ล่าช้า', class: 'bg-red-100 text-red-700' };
    if (end <= endOfMonth) return { label: 'เดือนนี้', class: 'bg-orange-100 text-orange-700' };
    return { label: 'เดือนหน้า+', class: 'bg-blue-100 text-blue-700' };
};

const formatDate = (d) => new Date(d).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });

const goToProject = (projectId) => {
    if(projectId) {
        router.push(`/project/${projectId}`);
    }
};

onMounted(() => fetchMyTasks());
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    📋 งานที่ต้องทำ (My Tasks)
                </h1>
                <p class="text-sm text-gray-500">ติดตามงานคงค้างของคุณ (คลิกที่งานเพื่อไปหน้าโครงการ)</p>
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

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div v-if="loading" class="p-10 text-center text-gray-500">กำลังโหลดรายการงาน...</div>

            <table v-else class="w-full text-left">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase border-b">
                    <tr>
                        <th class="px-6 py-3">ชื่องาน / โครงการ</th>
                        <th class="px-6 py-3 text-center">สถานะ</th>
                        <th class="px-6 py-3 text-center">กำหนดส่ง</th>
                        <th class="px-6 py-3 text-center">ความคืบหน้า</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr
                        v-for="task in filteredTasks"
                        :key="task.id"
                        @click="goToProject(task.project_id)"
                        class="hover:bg-purple-50 transition-colors group cursor-pointer"
                    >
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800 group-hover:text-purple-700 transition-colors">{{ task.name }}</div>
                            <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                📁 {{ task.project?.name || 'ไม่ระบุโครงการ' }}
                                <span class="bg-gray-100 px-1.5 rounded text-[10px]">{{ task.project?.code }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span :class="getStatusBadge(task).class" class="px-2 py-1 text-xs rounded-full font-bold whitespace-nowrap">
                                {{ getStatusBadge(task).label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-medium" :class="getStatusBadge(task).label === 'ล่าช้า' ? 'text-red-600' : 'text-gray-600'">
                            {{ formatDate(task.end_date) }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: task.progress + '%' }"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-600 w-8 text-right">{{ task.progress }}%</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="filteredTasks.length === 0">
                        <td colspan="4" class="py-10 text-center text-gray-400 italic">
                            - ไม่มีรายการงานในหมวดหมู่นี้ -
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
  </AppLayout>
</template>
