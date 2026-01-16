<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

// ✅ รับ Props canDelete
const props = defineProps(['isOpen', 'payment', 'canDelete']);
const emit = defineEmits(['close']);

const loading = ref(false);
const attachments = ref([]);
const fileInput = ref(null);
const uploadProgress = ref(0);

const fetchFiles = async () => {
  if (!props.payment?.id) return;
  loading.value = true;
  try {
    const res = await axios.get(`/api/payments/${props.payment.id}/files`);
    attachments.value = res.data;
  } catch (e) { console.error(e); } finally { loading.value = false; }
};

watch(() => props.isOpen, (newVal) => { if (newVal) { attachments.value = []; fetchFiles(); } });

const handleUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  const formData = new FormData();
  formData.append('file', file);
  formData.append('attachable_id', props.payment.id);
  formData.append('attachable_type', 'App\\Models\\Payment');
  try {
    uploadProgress.value = 1;
    await axios.post('/api/attachments/upload', formData, { headers: { 'Content-Type': 'multipart/form-data' }, onUploadProgress: (e) => uploadProgress.value = Math.round((e.loaded * 100) / e.total) });
    alert('✅ อัปโหลดสำเร็จ'); fetchFiles();
  } catch (e) { alert('❌ อัปโหลดไม่สำเร็จ'); } finally { uploadProgress.value = 0; if (fileInput.value) fileInput.value.value = ''; }
};

const handleDelete = async (id) => {
  if (!confirm('ลบไฟล์นี้?')) return;
  await axios.delete(`/api/attachments/${id}`);
  fetchFiles();
};

const openFile = (path) => window.open(`/storage/${path}`, '_blank');
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-lg w-full max-w-lg shadow-2xl overflow-hidden">
      <div class="p-4 border-b flex justify-between items-center bg-gray-50">
        <h3 class="font-bold text-gray-700">📎 ไฟล์แนบ: {{ payment?.description }}</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>

      <div class="p-6 bg-white min-h-[300px] flex flex-col">
        <div class="mb-4">
           <input type="file" ref="fileInput" @change="handleUpload" class="hidden">
           <button @click="$refs.fileInput.click()" class="w-full py-2 border-2 border-dashed border-blue-300 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors flex items-center justify-center gap-2">📂 เลือกไฟล์เอกสาร / สลิปโอนเงิน</button>
           <div v-if="uploadProgress > 0" class="mt-2 h-1 bg-gray-200 rounded overflow-hidden"><div class="h-full bg-blue-500 transition-all" :style="{ width: uploadProgress + '%' }"></div></div>
        </div>

        <div v-if="loading" class="text-center py-4 text-gray-400">กำลังโหลด...</div>
        <div v-else-if="attachments.length === 0" class="text-center py-10 text-gray-400 border rounded-lg bg-gray-50">ไม่มีไฟล์แนบ</div>
        <div v-else class="space-y-2 overflow-y-auto max-h-[300px]">
           <div v-for="file in attachments" :key="file.id" class="flex items-center justify-between p-2 border rounded hover:bg-gray-50">
              <div class="flex items-center gap-3 cursor-pointer overflow-hidden" @click="openFile(file.file_path)">
                 <div class="text-xs font-bold bg-gray-200 text-gray-600 px-2 py-1 rounded uppercase">{{ file.file_type }}</div>
                 <div class="truncate text-sm text-gray-700">{{ file.file_name }}</div>
              </div>
              <button v-if="canDelete" @click="handleDelete(file.id)" class="text-red-400 hover:text-red-600 px-2">ลบ</button>
           </div>
        </div>
      </div>
    </div>
  </div>
</template>
