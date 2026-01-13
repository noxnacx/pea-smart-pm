<script setup>
import AppLayout from '../Components/AppLayout.vue';
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const program = ref({});
const projects = ref([]);
const currentUser = ref(null);

// Status Badge Labels
const statusLabels = {
  'ongoing': 'กำลังดำเนินการ',
  'late': 'ล่าช้า',
  'completed': 'เสร็จสิ้น',
  'draft': 'ร่าง (Draft)'
};

// Check Permissions
const canManageProgram = computed(() => {
    return currentUser.value && currentUser.value.role === 'admin';
});

const fetchData = async () => {
    loading.value = true;
    try {
        // 1. ดึง User ปัจจุบัน
        const userRes = await axios.get('/api/user');
        currentUser.value = userRes.data;

        // 2. ดึงข้อมูลแผนงาน
        const pId = route.params.id;
        const progRes = await axios.get(`/api/programs/${pId}`);
        program.value = progRes.data;

        // 3. ดึงโครงการในแผนงานนี้
        const projRes = await axios.get(`/api/projects?program_id=${pId}`);
        projects.value = projRes.data.data; // .data เพราะ Laravel paginate ส่งมา
    } catch (e) {
        console.error(e);
        // ถ้าหาไม่เจอให้เด้งกลับ
        if(e.response && e.response.status === 404) router.push('/programs');
    }
    loading.value = false;
};

// --- Actions ---
const goToProject = (id) => router.push(`/project/${id}`);

const editProgram = () => {
    // ในที่นี้อาจจะ Reuse Modal จากหน้า Index หรือ Redirect ไปหน้า Edit
    // เพื่อความง่ายในบริบทนี้ ผมจะ Alert ไว้ก่อน หรือคุณสามารถ import Modal มาใช้ได้
    alert('คุณสามารถแก้ไขแผนงานได้ที่หน้า "จัดการแผนงาน (Programs)"');
    router.push('/programs');
};

const deleteProgram = async () => {
    if (!confirm('ยืนยันลบแผนงานนี้? (ต้องไม่มีโครงการคงเหลือ)')) return;
    try {
        await axios.delete(`/api/programs/${program.value.id}`);
        alert('ลบแผนงานเรียบร้อย');
        router.push('/programs');
    } catch (e) {
        alert(e.response?.data?.message || 'ลบไม่สำเร็จ');
    }
};

const formatCurrency = (val) => new Intl.NumberFormat('th-TH').format(val || 0);
const formatDate = (date) => date ? new Date(date).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';

onMounted(fetchData);
</script>

<template>
    <AppLayout>
        <div v-if="loading" class="text-center py-20 text-gray-500">กำลังโหลดข้อมูล...</div>

        <div v-else class="space-y-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <button @click="router.push('/programs')" class="text-gray-500 hover:text-purple-600 mb-2 flex items-center gap-1 text-sm font-medium transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" /></svg>
                        กลับหน้าแผนงานรวม
                    </button>
                    <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                        {{ program.name }}
                        <span :class="program.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'" class="text-xs px-2 py-1 rounded-full font-bold border">
                            {{ program.status === 'active' ? 'Active' : 'Closed' }}
                        </span>
                    </h1>
                    <div class="flex flex-wrap gap-4 mt-2 text-sm text-gray-600">
                        <span class="flex items-center gap-1"><span class="text-gray-400">📅</span> ปีงบประมาณ {{ program.fiscal_year }}</span>
                        <span class="flex items-center gap-1"><span class="text-gray-400">💰</span> งบรวม {{ formatCurrency(program.total_budget) }} บาท</span>
                        <span class="flex items-center gap-1"><span class="text-gray-400">🕒</span> {{ formatDate(program.start_date) }} - {{ formatDate(program.end_date) }}</span>
                    </div>
                </div>

                <div v-if="canManageProgram" class="flex gap-2">
                    <button @click="editProgram" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 text-sm font-medium transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-500" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                        แก้ไข
                    </button>
                    <button @click="deleteProgram" class="bg-white border border-red-200 text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg shadow-sm flex items-center gap-2 text-sm font-medium transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        ลบแผนงาน
                    </button>
                </div>
            </div>

            <div v-if="program.description" class="bg-purple-50 p-4 rounded-lg border border-purple-100 text-purple-900 text-sm">
                <strong>รายละเอียด:</strong> {{ program.description }}
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-700">โครงการภายใต้แผนงานนี้ ({{ projects.length }})</h3>
                </div>

                <table class="w-full text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-6 py-3">รหัส</th>
                            <th class="px-6 py-3">ชื่อโครงการ</th>
                            <th class="px-6 py-3 text-center">สถานะ</th>
                            <th class="px-6 py-3 text-right">วงเงินสัญญา</th>
                            <th class="px-6 py-3 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="p in projects" :key="p.id" @click="goToProject(p.id)" class="hover:bg-purple-50 cursor-pointer transition-colors group">
                            <td class="px-6 py-4 font-medium text-purple-700">{{ p.code }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-800 group-hover:text-purple-700 transition-colors">{{ p.name }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">PM: {{ p.manager?.name || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="{
                                    'bg-green-100 text-green-800': p.status === 'ongoing',
                                    'bg-red-100 text-red-800': p.status === 'late',
                                    'bg-blue-100 text-blue-800': p.status === 'completed',
                                    'bg-gray-100 text-gray-800': p.status === 'draft'
                                }" class="px-2 py-1 text-xs rounded-full font-bold border">
                                    {{ statusLabels[p.status] || p.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-gray-600">{{ formatCurrency(p.contract_amount) }}</td>
                            <td class="px-6 py-4 text-center">
                                <button class="text-blue-500 hover:bg-blue-50 p-1.5 rounded-lg transition-colors text-xs font-bold flex items-center gap-1 mx-auto">
                                    ดูรายละเอียด ➜
                                </button>
                            </td>
                        </tr>
                        <tr v-if="projects.length === 0">
                            <td colspan="5" class="text-center py-10 text-gray-400 flex-col">
                                <div class="mb-2 text-2xl">📭</div>
                                ยังไม่มีโครงการในแผนงานนี้
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
