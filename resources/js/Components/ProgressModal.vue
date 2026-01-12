<script setup>
import { reactive } from 'vue';

const props = defineProps(['isOpen', 'projectId']);
const emit = defineEmits(['close', 'saved']);

const form = reactive({
  month: new Date().toISOString().slice(0, 7), // yyyy-mm
  percent: ''
});

const submit = () => {
  // แปลงเดือน yyyy-mm เป็นวันที่ 1 ของเดือน (yyyy-mm-01) เพื่อเก็บลง DB
  const dateLogged = form.month + '-01';
  emit('saved', { date_logged: dateLogged, actual_percent: form.percent, projectId: props.projectId });
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg w-96 shadow-xl">
      <h3 class="text-lg font-bold mb-4">📈 อัปเดตผลงานประจำเดือน</h3>

      <div class="space-y-4">
        <div>
          <label class="block text-sm font-bold mb-1">เดือน</label>
          <input v-model="form.month" type="month" class="w-full border rounded px-3 py-2">
        </div>
        <div>
          <label class="block text-sm font-bold mb-1">ผลงานสะสม (%)</label>
          <input v-model="form.percent" type="number" min="0" max="100" class="w-full border rounded px-3 py-2" placeholder="เช่น 50.5">
        </div>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700 px-3 py-2">ยกเลิก</button>
        <button @click="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">บันทึก</button>
      </div>
    </div>
  </div>
</template>
