<template>
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mt-6">
        <h3 class="font-bold text-xl text-gray-800 mb-4 flex items-center gap-2">
            <span>👥</span> ภาระงานทีม (Workload)
        </h3>

        <div class="overflow-x-auto pb-4">
            <div class="flex gap-4 min-w-max">
                <div v-for="member in workloadData" :key="member.id" class="w-72 bg-gray-50 rounded-lg p-3 border border-gray-200 flex-shrink-0 flex flex-col">

                    <div class="flex items-center gap-3 mb-3 pb-2 border-b border-gray-200">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-700 font-bold shadow-sm">
                            {{ member.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="font-bold text-gray-800 text-sm">{{ member.name }}</div>
                            <div class="text-[10px] text-gray-500 uppercase tracking-wide">{{ member.role }}</div>
                        </div>
                        <div class="ml-auto bg-white px-2 py-0.5 rounded text-xs font-bold shadow-sm text-gray-600">
                            {{ member.tasks.length }}
                        </div>
                    </div>

                    <div class="space-y-2 flex-1 overflow-y-auto max-h-[400px] pr-1 custom-scrollbar">
                        <div v-for="task in member.tasks" :key="task.id" class="bg-white p-3 rounded border shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">

                            <div class="flex justify-between items-start mb-2 relative z-10">
                                <span class="font-semibold text-sm text-gray-700 leading-tight">{{ task.name }}</span>
                            </div>

                            <div class="flex justify-between items-end relative z-10">
                                <span class="text-gray-500 text-xs flex items-center gap-1">
                                    📅 {{ formatDate(task.end_date) }}
                                </span>

                                <span :class="getTaskStatus(task).badgeClass" class="px-2 py-0.5 rounded border font-bold text-[10px] whitespace-nowrap">
                                    {{ task.progress }}% ({{ getTaskStatus(task).label }})
                                </span>
                            </div>

                            <div class="absolute bottom-0 left-0 h-1.5 opacity-20" :class="getTaskStatus(task).barClass" :style="{ width: task.progress + '%' }"></div>
                            <div class="absolute bottom-0 left-0 h-1.5" :class="getTaskStatus(task).barClass" :style="{ width: task.progress + '%' }"></div>
                        </div>

                        <div v-if="member.tasks.length === 0" class="h-20 flex items-center justify-center text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-lg">
                            - ว่าง -
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps(['members', 'tasks']);

const workloadData = computed(() => {
    if (!props.members || !props.tasks) return [];

    return props.members.map(member => {
        // กรองงานที่ user คนนี้รับผิดชอบ
        const userTasks = props.tasks.filter(task => {
            return task.users && task.users.some(u => u.id === member.id);
        });

        // เรียงตามกำหนดส่ง (งานด่วนขึ้นก่อน)
        userTasks.sort((a, b) => new Date(a.end_date) - new Date(b.end_date));

        return {
            ...member,
            role: member.pivot?.role || 'Member',
            tasks: userTasks
        };
    });
});

// ฟังก์ชันคำนวณสถานะและคืนค่าสี
const getTaskStatus = (task) => {
    // 1. เสร็จสิ้น (สีเขียว)
    if (task.progress == 100) {
        return {
            label: 'เสร็จสิ้น',
            badgeClass: 'bg-green-100 text-green-700 border-green-200',
            barClass: 'bg-green-500'
        };
    }

    // เช็ควันที่ปัจจุบัน
    const today = new Date();
    today.setHours(0,0,0,0);
    const endDate = new Date(task.end_date);

    // 2. ล่าช้า (สีแดง) - ยังไม่เสร็จ และ เลยกำหนดส่ง
    if (endDate < today) {
        return {
            label: 'ล่าช้า',
            badgeClass: 'bg-red-100 text-red-700 border-red-200',
            barClass: 'bg-red-500'
        };
    }

    // 3. ปกติ (สีน้ำเงิน) - ยังไม่เสร็จ แต่ยังไม่ถึงกำหนด
    return {
        label: 'ปกติ',
        badgeClass: 'bg-blue-100 text-blue-700 border-blue-200',
        barClass: 'bg-blue-500'
    };
};

const formatDate = (date) => {
    if(!date) return '-';
    return new Date(date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
</style>
