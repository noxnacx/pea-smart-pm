<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
      <h3 class="text-xl font-bold mb-4">{{ isEditing ? '✏️ แก้ไขงาน' : '📝 เพิ่มงานใหม่' }}</h3>

      <form @submit.prevent="submitForm">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">ชื่องาน</label>
          <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2 focus:outline-blue-500" required>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">วันเริ่ม</label>
            <input v-model="form.start_date" type="date" class="w-full border rounded px-3 py-2" required>
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">วันสิ้นสุด</label>
            <input v-model="form.end_date" type="date" class="w-full border rounded px-3 py-2" required>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">น้ำหนักงาน (%)</label>
            <input v-model="form.weight" type="number" step="0.01" class="w-full border rounded px-3 py-2" required>
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">ความคืบหน้า (%)</label>
            <input v-model="form.progress" type="number" class="w-full border rounded px-3 py-2">
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" @click="$emit('close')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">ยกเลิก</button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            {{ isEditing ? 'บันทึกการแก้ไข' : 'สร้างงานใหม่' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue';

const props = defineProps(['isOpen', 'projectId', 'editData']); // รับ editData เพิ่ม
const emit = defineEmits(['close', 'saved']);

// เช็คว่ากำลังแก้ไขอยู่หรือเปล่า? (ถ้ามี editData ส่งมา = แก้ไข)
const isEditing = computed(() => !!props.editData);

const form = reactive({
  id: null,
  name: '',
  start_date: '',
  end_date: '',
  weight: 0,
  progress: 0
});

// เมื่อเปิด Modal หรือ editData เปลี่ยน ให้โหลดข้อมูลลงฟอร์ม
watch(() => props.editData, (newData) => {
  if (newData) {
    // โหมดแก้ไข: เอาข้อมูลเดิมใส่ฟอร์ม
    form.id = newData.id;
    form.name = newData.name;
    form.start_date = newData.start_date; // วันที่ต้อง format yyyy-mm-dd
    form.end_date = newData.end_date;
    form.weight = newData.weight;
    form.progress = newData.progress;
  } else {
    // โหมดสร้างใหม่: ล้างฟอร์ม
    form.id = null;
    form.name = '';
    form.start_date = '';
    form.end_date = '';
    form.weight = 0;
    form.progress = 0;
  }
}, { immediate: true });

const submitForm = () => {
  emit('saved', { ...form, project_id: props.projectId });
  // ไม่ต้องรีเซ็ตตรงนี้ เดี๋ยว Parent component จัดการปิด Modal เอง
};
</script>
