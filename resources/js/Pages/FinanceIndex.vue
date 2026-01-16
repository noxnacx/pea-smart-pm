<script setup>
import AppLayout from '../Components/AppLayout.vue';
import BarChart from '../Components/BarChart.vue'; // ใช้ตัวเดิมที่แก้แล้ว
import { ref, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(true);

const stats = ref({ total_budget: 0, total_paid: 0, balance: 0, usage_percent: 0 });
const chartData = ref({ categories: [], series: [] });
const projects = ref([]);

const fetchFinanceData = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/finance');
        stats.value = res.data.stats;

        // จัด Format ให้ BarChart
        chartData.value = {
            categories: res.data.chart_monthly.categories,
            series: [{ name: 'ยอดเบิกจ่ายจริง', data: res.data.chart_monthly.series }]
        };

        projects.value = res.data.projects;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

// Auto Resize Graph Trick (เหมือน Dashboard)
watch(loading, (isLoading) => {
    if (!isLoading) {
        nextTick(() => { setTimeout(() => window.dispatchEvent(new Event('resize')), 300); });
    }
});

const formatCurrency = (val) => new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(val || 0);
const goToProject = (id) => router.push(`/project/${id}`);

onMounted(fetchFinanceData);
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">💰 ภาพรวมการเงิน (Financial Overview)</h1>
            <p class="text-sm text-gray-500">ติดตามสถานะงบประมาณและกระแสเงินสดของโครงการทั้งหมด</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-xs font-bold uppercase">งบประมาณรวมทั้งพอร์ต</p>
                <h3 class="text-2xl font-extrabold text-gray-800 mt-1">{{ formatCurrency(stats.total_budget) }}</h3>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-xs font-bold uppercase">เบิกจ่ายสะสม</p>
                <h3 class="text-2xl font-extrabold text-blue-600 mt-1">{{ formatCurrency(stats.total_paid) }}</h3>
                <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
                    <div class="bg-blue-500 h-1.5 rounded-full" :style="{ width: stats.usage_percent + '%' }"></div>
                </div>
                <p class="text-xs text-right text-gray-400 mt-1">{{ stats.usage_percent }}%</p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-gray-500 text-xs font-bold uppercase">งบคงเหลือ</p>
                <h3 class="text-2xl font-extrabold text-green-600 mt-1">{{ formatCurrency(stats.balance) }}</h3>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-xs font-bold uppercase">จำนวนโครงการ</p>
                    <h3 class="text-2xl font-extrabold text-gray-800 mt-1">{{ projects.length }}</h3>
                </div>
                <div class="p-3 bg-purple-50 rounded-lg text-purple-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 36v-3m-6 6h6m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100 min-w-0 overflow-hidden">
                <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                    ยอดเบิกจ่ายรายเดือน (1 ปีล่าสุด)
                </h3>
                <div v-if="!loading && chartData.series.length > 0" class="w-full">
                    <BarChart :key="chartData.series[0].data.length" :categories="chartData.categories" :series="chartData.series" class="w-full" />
                </div>
                <div v-else class="h-64 flex items-center justify-center text-gray-400 bg-gray-50 rounded-lg border border-dashed">
                    ไม่มีข้อมูลการเบิกจ่าย
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                <div class="p-4 border-b bg-gray-50">
                    <h3 class="font-bold text-gray-700">📌 สถานะการเงินรายโครงการ</h3>
                </div>
                <div class="flex-1 overflow-y-auto max-h-[400px]">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-white text-gray-500 text-xs uppercase sticky top-0 shadow-sm">
                            <tr>
                                <th class="px-4 py-2">โครงการ</th>
                                <th class="px-4 py-2 text-right">เบิกแล้ว</th>
                                <th class="px-4 py-2 text-right">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="p in projects" :key="p.id" @click="goToProject(p.id)" class="hover:bg-purple-50 cursor-pointer transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-800 truncate w-32" :title="p.name">{{ p.name }}</div>
                                    <div class="text-xs text-gray-400">{{ p.code }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-mono">{{ formatCurrency(p.paid) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold" :class="p.percent > 90 ? 'bg-green-100 text-green-700' : (p.percent > 50 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600')">
                                        {{ p.percent }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
  </AppLayout>
</template>
