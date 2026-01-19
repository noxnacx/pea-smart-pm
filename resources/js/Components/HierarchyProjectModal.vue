<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    isOpen: Boolean,
    editData: Object,
    programId: Number,      // กรณีสร้างใต้แผนงาน (Main Project)
    parentId: Number,       // กรณีสร้างใต้โครงการอื่น (Sub-Project)
    parentName: String      // ชื่อแม่ (เอาไว้โชว์)
});

const emit = defineEmits(['close', 'saved']);

// ข้อมูล Master Data สำหรับตัวเลือก
const managers = ref([]);
const programs = ref([]); // เอาไว้เผื่อกรณีต้องเลือก (แต่ปกติเราจะ Auto)

const form = ref({
    id: null,
    name: '',
    code: '',
    contract_amount: 0,
    start_date: '',
    end_date: '',
    manager_id: '',
    program_id: '',
    parent_id: null, // ✅ Field ใหม่
    status: 'draft',
    progress_actual: 0
});

// ดึงข้อมูลตัวเลือก
const fetchOptions = async () => {
    try {
        const res = await axios.get('/api/master-data/project-options');
        managers.value = res.data.managers;
        programs.value = res.data.programs;
    } catch (e) {
        console.error("Error fetching options:", e);
    }
};

// เคลียร์ฟอร์ม
const resetForm = () => {
    form.value = {
        id: null,
        name: '',
        code: '',
        contract_amount: 0,
        start_date: '',
        end_date: '',
        manager_id: '',
        program_id: props.programId || '', // ✅ Auto fill ถ้ามี
        parent_id: props.parentId || null, // ✅ Auto fill ถ้ามี
        status: 'draft',
        progress_actual: 0
    };
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        fetchOptions();
        if (props.editData) {
            // โหมดแก้ไข: ใส่ข้อมูลเดิม
            form.value = { ...props.editData };
            // จัดรูปแบบวันที่ให้เข้ากับ <input type="date">
            if (form.value.start_date) form.value.start_date = new Date(form.value.start_date).toISOString().split('T')[0];
            if (form.value.end_date) form.value.end_date = new Date(form.value.end_date).toISOString().split('T')[0];
        } else {
            resetForm();
        }
    }
});

const submit = async () => {
    // ตรวจสอบความถูกต้องเบื้องต้น
    if (!form.value.name || !form.value.code || !form.value.start_date || !form.value.end_date) {
        alert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน (รหัส, ชื่อ, วันที่)');
        return;
    }

    try {
        if (form.value.id) {
            await axios.put(`/api/projects/${form.value.id}`, form.value);
        } else {
            await axios.post('/api/projects', form.value);
        }
        emit('saved');
    } catch (e) {
        console.error(e);
        alert('บันทึกไม่สำเร็จ: ' + (e.response?.data?.message || 'เกิดข้อผิดพลาด'));
    }
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[100] p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden">

      <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
        <div>
            <h3 class="text-xl font-bold text-gray-800">
              {{ form.id ? '📝 แก้ไขข้อมูลโครงการ' : '➕ สร้างโครงการใหม่' }}
            </h3>
            <p v-if="!form.id && parentName" class="text-xs text-blue-600 mt-1 font-medium bg-blue-50 px-2 py-0.5 rounded inline-block">
                ภายใต้: {{ parentName }}
            </p>
        </div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>

      <div class="p-6 overflow-y-auto max-h-[80vh]">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="md:col-span-1">
            <label class="block text-sm font-bold text-gray-700 mb-1">รหัสโครงการ *</label>
            <input v-model="form.code" type="text" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" placeholder="เช่น PEA-67-001">
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm font-bold text-gray-700 mb-1">สถานะโครงการ</label>
            <select v-model="form.status" class="w-full border rounded-lg px-3 py-2 outline-none">
              <option value="draft">ร่าง (Draft)</option>
              <option value="ongoing">กำลังดำเนินการ</option>
              <option value="late">ล่าช้า</option>
              <option value="completed">เสร็จสิ้น</option>
            </select>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700 mb-1">ชื่อโครงการ *</label>
            <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2 outline-none" placeholder="ระบุชื่อโครงการเต็ม">
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm font-bold text-gray-700 mb-1">แผนงานหลัก (Program)</label>
            <select v-model="form.program_id" :disabled="!!programId" class="w-full border rounded-lg px-3 py-2 outline-none bg-gray-50">
              <option value="" disabled>เลือกแผนงาน...</option>
              <option v-for="pg in programs" :key="pg.id" :value="pg.id">{{ pg.name }}</option>
            </select>
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm font-bold text-gray-700 mb-1">ผู้จัดการโครงการ (PM)</label>
            <select v-model="form.manager_id" class="w-full border rounded-lg px-3 py-2 outline-none">
              <option value="" disabled>เลือกผู้ดูแล...</option>
              <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm font-bold text-gray-700 mb-1">มูลค่าสัญญา (บาท)</label>
            <input v-model="form.contract_amount" type="number" class="w-full border rounded-lg px-3 py-2 outline-none">
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm font-bold text-gray-700 mb-1">ความคืบหน้าสะสม (%)</label>
            <input v-model="form.progress_actual" type="number" min="0" max="100" class="w-full border rounded-lg px-3 py-2 outline-none">
          </div>

          <div class="md:col-span-1">
            <label class="block text-sm font-bold text-gray-700 mb-1">วันที่เริ่มต้นโครงการ *</label>
            <input v-model="form.start_date" type="date" class="w-full border rounded-lg px-3 py-2 outline-none">
          </div>
          <div class="md:col-span-1">
            <label class="block text-sm font-bold text-gray-700 mb-1">วันที่สิ้นสุดตามสัญญา *</label>
            <input v-model="form.end_date" type="date" class="w-full border rounded-lg px-3 py-2 outline-none">
          </div>
        </div>
      </div>

      <div class="p-6 border-t bg-gray-50 flex justify-end gap-3">
        <button @click="$emit('close')" class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">ยกเลิก</button>
        <button @click="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-bold shadow-lg transition-all active:scale-95">
          💾 {{ form.id ? 'บันทึกการแก้ไข' : 'สร้างโครงการ' }}
        </button>
      </div>
    </div>
  </div>
</template>
