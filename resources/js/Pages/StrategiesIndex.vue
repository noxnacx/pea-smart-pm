<script setup>
import AppLayout from '@/Components/AppLayout.vue';
import StrategyModal from '@/Components/StrategyModal.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router'; // ✅ Import useRouter

const router = useRouter(); // ✅ เรียกใช้ Router
const strategies = ref([]);
const loading = ref(true);
const showModal = ref(false);
const editData = ref(null);

const fetchStrategies = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/strategies');
        strategies.value = res.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    editData.value = null;
    showModal.value = true;
};

// ✅ ปุ่มแก้ไข: หยุดการส่ง event ไปที่ parent (click.stop) เพื่อไม่ให้เด้งไปหน้า Detail
const openEdit = (item) => {
    editData.value = item;
    showModal.value = true;
};

const handleDelete = async (id) => {
    if(!confirm('ยืนยันการลบยุทธศาสตร์? ข้อมูลแผนงานที่เกี่ยวข้องอาจได้รับผลกระทบ')) return;
    try {
        await axios.delete(`/api/strategies/${id}`);
        fetchStrategies();
    } catch (e) {
        alert('ลบไม่สำเร็จ');
    }
};

// ✅ ฟังก์ชันไปหน้า Detail
const goToDetail = (id) => {
    router.push(`/strategies/${id}`);
};

const handleSaved = () => {
    showModal.value = false;
    fetchStrategies();
};

onMounted(() => {
    fetchStrategies();
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">🎯 ยุทธศาสตร์องค์กร (Strategic Goals)</h1>
                    <p class="text-sm text-gray-500">กำหนดเป้าหมายหลักระดับสูงสุดขององค์กร</p>
                </div>
                <button @click="openCreate" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow flex items-center gap-2 transition-all active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                    เพิ่มยุทธศาสตร์
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
                <div v-if="loading" class="text-center py-12 text-gray-500 animate-pulse">กำลังโหลดข้อมูล...</div>

                <div v-else-if="strategies.length === 0" class="text-center py-16">
                    <div class="bg-purple-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">ยังไม่มีข้อมูลยุทธศาสตร์</h3>
                    <p class="text-gray-500 mt-1">เริ่มสร้างยุทธศาสตร์แรกเพื่อกำหนดทิศทางของโครงการ</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                    <div
                        v-for="strategy in strategies"
                        :key="strategy.id"
                        @click="goToDetail(strategy.id)"
                        class="group bg-white border rounded-xl p-5 hover:shadow-lg transition-all hover:border-purple-300 relative cursor-pointer"
                    >
                        <div class="absolute top-4 right-4 flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity z-10">
                            <button @click.stop="openEdit(strategy)" class="p-1 text-gray-400 hover:text-yellow-600 bg-white rounded-full shadow-sm border border-gray-100" title="แก้ไข">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
                            </button>
                            <button @click.stop="handleDelete(strategy.id)" class="p-1 text-gray-400 hover:text-red-600 bg-white rounded-full shadow-sm border border-gray-100" title="ลบ">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-md shrink-0">
                                {{ strategy.name.charAt(0) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-gray-800 line-clamp-2 group-hover:text-purple-700 transition-colors">{{ strategy.name }}</h3>
                                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ strategy.description || '-' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">ภาพรวม</span>
                            <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 px-3 py-1 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                <span>{{ strategy.programs_count || 0 }} แผนงาน</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <StrategyModal :isOpen="showModal" :editData="editData" @close="showModal = false" @saved="handleSaved" />
        </div>
    </AppLayout>
</template>
