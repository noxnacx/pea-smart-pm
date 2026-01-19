<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

// รับ Props
const props = defineProps({
    isOpen: Boolean,
    editData: Object,       // ถ้าแก้ไข ส่ง object program มา
    strategyId: Number,     // จำเป็น: สร้างภายใต้ยุทธศาสตร์ไหน
    parentId: Number,       // Optional: ถ้าสร้างเป็นลูกของแผนงานอื่น
    parentName: String      // Optional: ชื่อแผนงานแม่ (เอาไว้โชว์เฉยๆ)
});

const emit = defineEmits(['close', 'saved']);

// รายชื่อ Users (ผอ.)
const users = ref([]);
const loading = ref(false);

// Form Data (รวม Field เดิม + Field ใหม่)
const form = ref({
    name: '',
    fiscal_year: new Date().getFullYear() + 543, // Default ปีปัจจุบัน (พ.ศ.)
    total_budget: 0,
    start_date: '',
    end_date: '',
    description: '',
    status: 'active',
    owner_id: null,
    strategy_id: null,
    parent_id: null
});

// โหลดรายชื่อ ผอ.
const fetchUsers = async () => {
    try {
        const res = await axios.get('/api/users');
        users.value = Array.isArray(res.data) ? res.data : res.data.data;
    } catch (e) { console.error(e); }
};

// เมื่อเปิด Modal
watch(() => props.isOpen, (val) => {
    if (val) {
        fetchUsers();
        if (props.editData) {
            // --- โหมดแก้ไข ---
            // Spread ค่าเดิม แล้วแปลงวันที่ให้ตรง format input date
            form.value = {
                ...props.editData,
                start_date: props.editData.start_date ? props.editData.start_date.split('T')[0] : '',
                end_date: props.editData.end_date ? props.editData.end_date.split('T')[0] : ''
            };
        } else {
            // --- โหมดสร้างใหม่ ---
            form.value = {
                name: '',
                fiscal_year: new Date().getFullYear() + 543,
                total_budget: 0,
                start_date: '',
                end_date: '',
                description: '',
                status: 'active',
                owner_id: null,
                // ✅ Auto link Hierarchy
                strategy_id: props.strategyId,
                parent_id: props.parentId
            };
        }
    }
});

const save = async () => {
    // Validate พื้นฐาน
    if (!form.value.name || !form.value.fiscal_year) {
        return alert('กรุณาระบุชื่อแผนงาน และ ปีงบประมาณ');
    }

    loading.value = true;
    try {
        // แปลงข้อมูลให้ถูกต้องก่อนส่ง (เหมือนใน ProgramsIndex เดิม)
        const payload = { ...form.value };
        payload.fiscal_year = String(payload.fiscal_year); // ส่งเป็น String ตาม Controller
        if (!payload.start_date) payload.start_date = null;
        if (!payload.end_date) payload.end_date = null;

        if (form.value.id) {
            await axios.put(`/api/programs/${form.value.id}`, payload);
        } else {
            await axios.post('/api/programs', payload);
        }
        emit('saved'); // แจ้งแม่ว่าเสร็จแล้ว
    } catch (e) {
        console.error(e);
        // แสดง Error จาก Backend ถ้ามี
        alert('บันทึกไม่สำเร็จ: ' + (e.response?.data?.message || 'เกิดข้อผิดพลาด'));
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="$emit('close')"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b">
                    <h3 class="text-lg font-bold leading-6 text-gray-900">
                        {{ form.id ? '✏️ แก้ไขแผนงาน' : '➕ สร้างแผนงานใหม่' }}
                    </h3>
                    <div v-if="!form.id && parentName" class="mt-2 flex items-center gap-2 text-sm text-purple-700 bg-purple-50 px-3 py-1 rounded-md border border-purple-100">
                        <span class="text-xs font-semibold uppercase text-purple-400">Under:</span>
                        <span class="font-medium truncate max-w-[250px]">{{ parentName }}</span>
                    </div>
                </div>

                <div class="px-4 py-5 sm:p-6 space-y-4">

                    <div class="grid grid-cols-4 gap-4">
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-gray-700">ปีงบฯ *</label>
                            <input v-model="form.fiscal_year" type="number" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-purple-500 focus:border-purple-500 sm:text-sm" placeholder="2567">
                        </div>
                        <div class="col-span-3">
                            <label class="block text-sm font-bold text-gray-700">ชื่อแผนงาน *</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-purple-500 focus:border-purple-500 sm:text-sm" placeholder="ระบุชื่อแผนงาน">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700">งบประมาณรวม (บาท)</label>
                        <div class="relative mt-1 rounded-md shadow-sm">
                            <input v-model="form.total_budget" type="number" min="0" class="block w-full border border-gray-300 rounded-md py-2 pl-3 pr-12 focus:ring-purple-500 focus:border-purple-500 sm:text-sm" placeholder="0.00">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">THB</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">รายละเอียดสังเขป</label>
                        <textarea v-model="form.description" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-purple-500 focus:border-purple-500 sm:text-sm"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">วันที่เริ่มต้น</label>
                            <input v-model="form.start_date" type="date" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">วันที่สิ้นสุด</label>
                            <input v-model="form.end_date" type="date" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 sm:text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ผู้รับผิดชอบ (ผอ.)</label>
                            <select v-model="form.owner_id" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                                <option :value="null">- เลือก -</option>
                                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">สถานะ</label>
                            <select v-model="form.status" class="mt-1 block w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-purple-500 focus:border-purple-500 sm:text-sm">
                                <option value="active">🟢 กำลังดำเนินการ</option>
                                <option value="inactive">⚪ พักงาน (Inactive)</option>
                                <option value="completed">🔵 เสร็จสิ้น</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button @click="save" :disabled="loading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                        <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ loading ? 'กำลังบันทึก...' : 'บันทึกข้อมูล' }}
                    </button>
                    <button @click="$emit('close')" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
