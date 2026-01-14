<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-lg"> <h3 class="text-xl font-bold mb-4">{{ isEditing ? '✏️ แก้ไขงาน' : '📝 เพิ่มงานใหม่' }}</h3>

      <form @submit.prevent="submitForm">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">ชื่องาน</label>
          <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">👥 ผู้รับผิดชอบ (เลือกได้หลายคน)</label>
            <div class="border rounded p-3 max-h-40 overflow-y-auto bg-gray-50">
                <div v-for="member in members" :key="member.id" class="flex items-center gap-2 mb-2 last:mb-0">
                    <input
                        type="checkbox"
                        :id="'user-'+member.id"
                        :value="member.id"
                        v-model="form.user_ids"
                        class="rounded text-purple-600 focus:ring-purple-500"
                    >
                    <label :for="'user-'+member.id" class="text-sm text-gray-700 cursor-pointer flex-1">
                        {{ member.name }} <span class="text-xs text-gray-500">({{ member.pivot?.role }})</span>
                    </label>
                </div>
                <div v-if="members.length === 0" class="text-sm text-gray-400 text-center">ไม่มีสมาชิกในทีม</div>
            </div>
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

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">น้ำหนัก (%)</label>
            <input v-model="form.weight" type="number" step="0.01" class="w-full border rounded px-3 py-2" required>
          </div>
          <div>
            <label class="block text-gray-700 text-sm font-bold mb-2">ความคืบหน้า (%)</label>
            <input v-model="form.progress" type="number" class="w-full border rounded px-3 py-2">
          </div>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">⛓️ งานที่ต้องทำก่อน</label>
            <select v-model="form.predecessor_id" @change="onPredecessorChange" class="w-full border rounded px-3 py-2 bg-white">
                <option :value="null">-- ไม่มี --</option>
                <option v-for="task in availableTasks" :key="task.id" :value="task.id">
                    {{ task.name }} (จบ: {{ formatDate(task.end_date) }})
                </option>
            </select>
        </div>

        <div class="flex justify-end gap-2 border-t pt-4">
          <button type="button" @click="$emit('close')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">ยกเลิก</button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">บันทึก</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, computed } from 'vue';

const props = defineProps(['isOpen', 'projectId', 'editData', 'existingTasks', 'members']);
const emit = defineEmits(['close', 'saved']);

const isEditing = computed(() => !!props.editData);

const form = reactive({
  id: null,
  name: '',
  start_date: '',
  end_date: '',
  weight: 0,
  progress: 0,
  predecessor_id: null,
  user_ids: [] // ✅ เปลี่ยนเป็น Array
});

const availableTasks = computed(() => {
    if (!props.existingTasks) return [];
    return props.existingTasks.filter(t => t.id !== form.id);
});

// Logic: วันที่อัตโนมัติ
const onPredecessorChange = () => {
    if (!form.predecessor_id) return;
    const parentTask = props.existingTasks.find(t => t.id === form.predecessor_id);
    if (parentTask) {
        const nextDate = new Date(parentTask.end_date);
        nextDate.setDate(nextDate.getDate() + 1);
        form.start_date = nextDate.toISOString().split('T')[0];
        if (new Date(form.end_date) < nextDate) form.end_date = form.start_date;
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString('th-TH');

watch(() => props.editData, (newData) => {
  if (newData) {
    Object.assign(form, newData);
    // ✅ ดึง user_ids จาก relation users
    form.user_ids = newData.users ? newData.users.map(u => u.id) : [];
  } else {
    form.id = null;
    form.name = '';
    form.start_date = '';
    form.end_date = '';
    form.weight = 0;
    form.progress = 0;
    form.predecessor_id = null;
    form.user_ids = [];
  }
}, { immediate: true });

const submitForm = () => {
  emit('saved', { ...form, project_id: props.projectId });
};
</script>
