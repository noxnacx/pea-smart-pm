<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

// รับ props ให้ยืดหยุ่น (title, type, id)
const props = defineProps(['isOpen', 'title', 'attachableType', 'attachableId']);
const emit = defineEmits(['close']);

const loading = ref(false);
const attachments = ref([]);
const fileInput = ref(null);
const uploadProgress = ref(0);

// ฟังก์ชันดึงไฟล์ (ใช้ API กลางที่เราจะไปเพิ่ม)
const fetchFiles = async () => {
  if (!props.attachableId || !props.attachableType) return;
  loading.value = true;
  try {
    const res = await axios.get('/api/attachments', {
        params: {
            type: props.attachableType,
            id: props.attachableId
        }
    });
    attachments.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    attachments.value = [];
    fetchFiles();
  }
});

const handleUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('attachable_id', props.attachableId);
  formData.append('attachable_type', props.attachableType); // ส่ง Type ที่รับมาจาก Props

  try {
    uploadProgress.value = 1;
    await axios.post('/api/attachments/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (e) => uploadProgress.value = Math.round((e.loaded * 100) / e.total)
    });
    alert('✅ อัปโหลดสำเร็จ');
    fetchFiles();
  } catch (e) {
    alert('❌ อัปโหลดไม่สำเร็จ (ไฟล์ต้องไม่เกิน 10MB)');
  } finally {
    uploadProgress.value = 0;
    if (fileInput.value) fileInput.value.value = '';
  }
};

const handleDelete = async (id) => {
  if (!confirm('ลบไฟล์นี้?')) return;
  await axios.delete(`/api/attachments/${id}`);
  fetchFiles();
};

const openFile = (path) => window.open(`/storage/${path}`, '_blank');
const formatDateTime = (date) => new Date(date).toLocaleString('th-TH');
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[70] p-4">
    <div class="bg-white rounded-lg w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[80vh]">
      <div class="p-4 border-b flex justify-between items-center bg-gray-50">
        <h3 class="font-bold text-gray-700 flex items-center gap-2">
            📎 {{ title || 'รายการไฟล์แนบ' }}
        </h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="p-6 bg-white flex-1 overflow-y-auto">
        <div class="mb-4">
           <input type="file" ref="fileInput" @change="handleUpload" class="hidden">
           <button @click="$refs.fileInput.click()"
                   class="w-full py-2 border-2 border-dashed border-purple-300 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-100 transition-colors flex items-center justify-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
              อัปโหลดเอกสารใหม่
           </button>
           <div v-if="uploadProgress > 0" class="mt-2 h-1 bg-gray-200 rounded overflow-hidden">
              <div class="h-full bg-purple-500 transition-all" :style="{ width: uploadProgress + '%' }"></div>
           </div>
        </div>

        <div v-if="loading" class="text-center py-4 text-gray-400">กำลังโหลด...</div>
        <div v-else-if="attachments.length === 0" class="text-center py-10 text-gray-400 border rounded-lg bg-gray-50">
           ยังไม่มีเอกสารแนบ
        </div>
        <div v-else class="space-y-2">
           <div v-for="file in attachments" :key="file.id"
                class="flex items-center justify-between p-3 border rounded hover:bg-gray-50 transition-colors">
              <div class="flex items-center gap-3 cursor-pointer overflow-hidden flex-1" @click="openFile(file.file_path)">
                 <div class="w-10 h-10 flex items-center justify-center bg-gray-100 rounded text-xs font-bold text-gray-500 uppercase">
                    {{ file.file_type }}
                 </div>
                 <div class="truncate">
                    <div class="text-sm font-medium text-gray-800 truncate">{{ file.file_name }}</div>
                    <div class="text-[10px] text-gray-400">โดย {{ file.user?.name || '-' }} • {{ formatDateTime(file.created_at) }}</div>
                 </div>
              </div>
              <button @click="handleDelete(file.id)" class="text-gray-300 hover:text-red-500 p-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
              </button>
           </div>
        </div>
      </div>
    </div>
  </div>
</template>
