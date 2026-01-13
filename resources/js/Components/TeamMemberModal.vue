<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps(['isOpen', 'projectId']);
const emit = defineEmits(['close', 'saved']);

const searchQuery = ref('');
const searchResults = ref([]);
const selectedUser = ref(null);
const loading = ref(false);

// ค้นหา User แบบ Realtime
watch(searchQuery, async (newVal) => {
  if (newVal.length < 2) {
    searchResults.value = [];
    return;
  }
  try {
    const res = await axios.get(`/api/users/search?q=${newVal}`);
    searchResults.value = res.data;
  } catch (e) { console.error(e); }
});

const selectUser = (user) => {
  selectedUser.value = user;
  searchResults.value = []; // ซ่อนผลการค้นหา
  searchQuery.value = user.name; // โชว์ชื่อในช่อง
};

const submit = async () => {
  if (!selectedUser.value) return alert('กรุณาเลือกสมาชิก');
  loading.value = true;
  try {
    await axios.post(`/api/projects/${props.projectId}/members`, {
      user_id: selectedUser.value.id,
      role: 'member'
    });
    emit('saved'); // บอกตัวแม่ว่าเสร็จแล้ว
    emit('close'); // ปิด Modal
    // Reset ค่า
    searchQuery.value = '';
    selectedUser.value = null;
  } catch (e) {
    alert('เกิดข้อผิดพลาด');
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[80] p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm overflow-hidden">
      <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
        <h3 class="font-bold text-gray-700">👤 เชิญสมาชิกเข้าทีม</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="p-6">
        <div class="relative">
           <label class="block text-sm font-bold text-gray-700 mb-1">ค้นหาชื่อ หรือ อีเมล</label>
           <input
             v-model="searchQuery"
             type="text"
             class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-purple-500"
             placeholder="พิมพ์เพื่อค้นหา..."
             autofocus
           >

           <div v-if="searchResults.length > 0" class="absolute left-0 right-0 bg-white border rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto z-10">
              <div
                v-for="user in searchResults" :key="user.id"
                @click="selectUser(user)"
                class="p-2 hover:bg-purple-50 cursor-pointer flex items-center gap-2 border-b last:border-0"
              >
                 <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-xs font-bold text-purple-600">
                    {{ user.name.charAt(0) }}
                 </div>
                 <div>
                    <div class="text-sm font-bold text-gray-700">{{ user.name }}</div>
                    <div class="text-xs text-gray-400">{{ user.email }}</div>
                 </div>
              </div>
           </div>
        </div>

        <div v-if="selectedUser" class="mt-4 p-3 bg-purple-50 rounded-lg border border-purple-100 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-purple-500 text-white flex items-center justify-center font-bold">
               {{ selectedUser.name.charAt(0) }}
            </div>
            <div>
               <div class="text-sm font-bold text-gray-800">กำลังเลือก:</div>
               <div class="text-sm text-purple-700">{{ selectedUser.name }}</div>
            </div>
        </div>
      </div>

      <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
         <button @click="$emit('close')" class="px-4 py-2 text-gray-500 hover:text-gray-700">ยกเลิก</button>
         <button
           @click="submit"
           :disabled="loading || !selectedUser"
           class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg shadow disabled:opacity-50"
         >
            {{ loading ? 'กำลังบันทึก...' : 'ยืนยันเพิ่มสมาชิก' }}
         </button>
      </div>
    </div>
  </div>
</template>
