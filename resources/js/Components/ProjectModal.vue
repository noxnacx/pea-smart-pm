<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-md">
      <h3 class="text-xl font-bold mb-4">
        {{ isEditMode ? '✏️ แก้ไขโครงการ' : '✨ สร้างโครงการใหม่' }}
      </h3>

      <form @submit.prevent="submitForm">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อโครงการ</label>
          <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2 focus:outline-blue-500" required placeholder="เช่น ก่อสร้างสถานีไฟฟ้า...">
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">มูลค่าสัญญา (บาท)</label>
          <input v-model="form.contract_amount" type="number" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6">
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">สถานะ</label>
            <select v-model="form.status" class="w-full border rounded px-3 py-2 bg-white">
              <option value="draft">ร่าง (Draft)</option>
              <option value="ongoing">กำลังดำเนินการ</option>
              <option value="late">ล่าช้า</option>
              <option value="completed">เสร็จสิ้น</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">ความก้าวหน้า (%)</label>
            <input v-model="form.progress_actual" type="number" class="w-full border rounded px-3 py-2" min="0" max="100">
          </div>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" @click="$emit('close')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">ยกเลิก</button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">บันทึก</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue';

const props = defineProps(['isOpen', 'project']);
const emit = defineEmits(['close', 'saved']);

const isEditMode = computed(() => !!props.project);

const form = reactive({
  name: '',
  status: 'ongoing',
  progress_actual: 0,
  contract_amount: 0
});

// Watch: เมื่อเปิด Modal ให้เช็คว่าจะเติมของเก่า หรือ ล้างฟอร์ม
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    if (props.project) {
      // โหมดแก้ไข
      form.name = props.project.name;
      form.status = props.project.status;
      form.progress_actual = props.project.progress_actual;
      form.contract_amount = props.project.contract_amount;
    } else {
      // โหมดสร้างใหม่ (ล้างค่า)
      form.name = '';
      form.status = 'ongoing';
      form.progress_actual = 0;
      form.contract_amount = 0;
    }
  }
});

const submitForm = () => {
  emit('saved', { ...form });
};
</script>
