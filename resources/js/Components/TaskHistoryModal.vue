<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

// ✅ รับ Props canDelete
const props = defineProps(['isOpen', 'task', 'canDelete']);
const emit = defineEmits(['close']);

const loading = ref(false);
const taskData = ref(null);
const fileInput = ref(null);
const uploadProgress = ref(0);

const fetchDetails = async () => {
  if (!props.task?.id) return;
  loading.value = true;
  try {
    const res = await axios.get(`/api/tasks/${props.task.id}/logs`);
    taskData.value = res.data;
  } catch (e) { console.error(e); alert('ไม่สามารถโหลดข้อมูลได้'); } finally { loading.value = false; }
};

watch(() => props.isOpen, (newVal) => { if (newVal) { taskData.value = null; fetchDetails(); } });

const handleUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  const formData = new FormData();
  formData.append('file', file);
  formData.append('attachable_id', props.task.id);
  formData.append('attachable_type', 'App\\Models\\Task');
  try {
    uploadProgress.value = 1;
    await axios.post('/api/attachments/upload', formData, { headers: { 'Content-Type': 'multipart/form-data' }, onUploadProgress: (e) => uploadProgress.value = Math.round((e.loaded * 100) / e.total) });
    alert('✅ อัปโหลดสำเร็จ'); fetchDetails();
  } catch (e) { console.error(e); alert('❌ อัปโหลดไม่สำเร็จ (ไฟล์ต้องไม่เกิน 10MB)'); } finally { uploadProgress.value = 0; if (fileInput.value) fileInput.value.value = ''; }
};

const handleDeleteFile = async (id) => {
  if (!confirm('ต้องการลบไฟล์นี้หรือไม่?')) return;
  try { await axios.delete(`/api/attachments/${id}`); fetchDetails(); } catch (e) { alert('ลบไม่สำเร็จ'); }
};

const openFile = (path) => { window.open(`/storage/${path}`, '_blank'); };
const formatDateTime = (date) => new Date(date).toLocaleString('th-TH');
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl w-full max-w-4xl h-[80vh] flex flex-col shadow-2xl overflow-hidden">

      <div class="p-4 border-b flex justify-between items-center bg-gray-50">
        <div><h3 class="text-lg font-bold text-gray-800">📌 รายละเอียดงาน: {{ task?.name }}</h3><p class="text-sm text-gray-500">ความคืบหน้าปัจจุบัน: {{ task?.progress }}%</p></div>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
      </div>

      <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
        <div class="flex-1 p-6 overflow-y-auto border-r border-gray-100 bg-white">
           <h4 class="font-bold text-gray-700 mb-4 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> ประวัติการบันทึก (History)</h4>
           <div v-if="loading" class="text-center py-10 text-gray-400">กำลังโหลด...</div>
           <div v-else-if="!taskData?.progress_logs?.length" class="text-center py-10 text-gray-400 border-2 border-dashed rounded-lg">ยังไม่มีประวัติการบันทึก</div>
           <div v-else class="space-y-6 border-l-2 border-purple-100 ml-3 pl-6 relative">
              <div v-for="log in taskData.progress_logs" :key="log.id" class="relative">
                 <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-purple-500 border-2 border-white shadow"></div>
                 <div class="text-sm text-gray-500 mb-1">{{ formatDateTime(log.created_at) }}</div>
                 <div class="bg-gray-50 p-3 rounded-lg border">
                    <div class="font-bold text-gray-800 mb-1">ความคืบหน้า: <span class="text-purple-600">{{ log.progress_percent }}%</span></div>
                    <p v-if="log.note" class="text-gray-600 text-sm mb-2">{{ log.note }}</p>
                    <div class="text-xs text-gray-400 flex items-center gap-1"><span class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center text-[10px]">👤</span> โดย {{ log.user?.name }}</div>
                 </div>
              </div>
           </div>
        </div>

        <div class="w-full md:w-96 bg-gray-50 p-6 overflow-y-auto flex flex-col">
           <h4 class="font-bold text-gray-700 mb-4 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg> ไฟล์แนบ / หลักฐาน</h4>
           <div class="mb-4">
              <input type="file" ref="fileInput" @change="handleUpload" class="hidden">
              <button @click="$refs.fileInput.click()" class="w-full py-2 border-2 border-dashed border-blue-300 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 hover:border-blue-400 transition-colors flex items-center justify-center gap-2">อัปโหลดไฟล์ใหม่</button>
              <div v-if="uploadProgress > 0" class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden"><div class="h-full bg-blue-500 transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div></div>
           </div>
           <div v-if="!taskData?.attachments?.length" class="text-center text-gray-400 text-sm mt-4">ยังไม่มีไฟล์แนบ</div>
           <div v-else class="space-y-3">
              <div v-for="file in taskData.attachments" :key="file.id" class="bg-white p-3 rounded shadow-sm border flex items-center justify-between group">
                 <div class="flex items-center gap-3 overflow-hidden cursor-pointer" @click="openFile(file.file_path)">
                    <div class="w-8 h-8 rounded bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500 uppercase flex-shrink-0">{{ file.file_type }}</div>
                    <div class="truncate"><div class="text-sm font-medium text-gray-700 truncate group-hover:text-blue-600">{{ file.file_name }}</div><div class="text-[10px] text-gray-400">{{ formatDateTime(file.created_at) }}</div></div>
                 </div>
                 <button v-if="canDelete" @click="handleDeleteFile(file.id)" class="text-gray-300 hover:text-red-500 p-1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg></button>
              </div>
           </div>
        </div>
      </div>
    </div>
  </div>
</template>
