<template>
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd" />
            </svg>
            ความคิดเห็น & บันทึกงาน ({{ comments.length }})
        </h3>

        <div class="flex gap-4 mb-6">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold shrink-0">
                {{ currentUserInitials }}
            </div>
            <div class="flex-1">
                <textarea
                    v-model="newComment"
                    rows="2"
                    class="w-full border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 shadow-sm"
                    placeholder="พิมพ์ข้อความที่นี่... (เช่น อัปเดตความคืบหน้า, แจ้งปัญหา)"
                ></textarea>
                <div class="mt-2 flex justify-end">
                    <button
                        @click="submitComment"
                        :disabled="!newComment.trim() || sending"
                        class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 disabled:opacity-50 transition-colors text-sm font-medium"
                    >
                        {{ sending ? 'กำลังส่ง...' : 'ส่งข้อความ' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-6 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
            <div v-if="loading" class="text-center text-gray-400 py-4">กำลังโหลด...</div>

            <div v-else-if="comments.length === 0" class="text-center text-gray-400 py-8 italic bg-gray-50 rounded-lg">
                ยังไม่มีการพูดคุยในหัวข้อนี้
            </div>

            <div v-for="comment in comments" :key="comment.id" class="flex gap-4 group">
                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold shrink-0 text-sm">
                    {{ getInitials(comment.user.name) }}
                </div>

                <div class="flex-1">
                    <div class="bg-gray-50 p-3 rounded-2xl rounded-tl-none border border-gray-100">
                        <div class="flex justify-between items-baseline mb-1">
                            <span class="font-bold text-sm text-gray-900">{{ comment.user.name }}</span>
                            <span class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</span>
                        </div>
                        <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ comment.body }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/th';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);
dayjs.locale('th');

const props = defineProps({
    type: { type: String, required: true }, // 'project' หรือ 'task'
    id: { type: Number, required: true }
});

const comments = ref([]);
const newComment = ref('');
const loading = ref(true);
const sending = ref(false);

// ดึงชื่อย่อ User ปัจจุบันจาก LocalStorage
const currentUser = JSON.parse(localStorage.getItem('user_info') || '{}');
const currentUserInitials = computed(() => getInitials(currentUser.name || 'Me'));

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const formatDate = (date) => {
    return dayjs(date).fromNow();
};

const fetchComments = async () => {
    try {
        const { data } = await axios.get(`/api/comments?type=${props.type}&id=${props.id}`);
        comments.value = data;
    } catch (error) {
        console.error("Failed to load comments", error);
    } finally {
        loading.value = false;
    }
};

const submitComment = async () => {
    if (!newComment.value.trim()) return;

    sending.value = true;
    try {
        const { data } = await axios.post('/api/comments', {
            type: props.type,
            id: props.id,
            body: newComment.value
        });

        // เพิ่มคอมเมนต์ใหม่ไปบนสุด
        comments.value.unshift(data);
        newComment.value = '';
    } catch (error) {
        alert('ส่งข้อความไม่สำเร็จ กรุณาลองใหม่');
    } finally {
        sending.value = false;
    }
};

onMounted(fetchComments);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
</style>
