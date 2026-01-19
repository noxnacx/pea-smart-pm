<script setup>
import AppLayout from '@/Components/AppLayout.vue';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/th';
import buddhistEra from 'dayjs/plugin/buddhistEra'; // ✅ 1. Import Plugin พ.ศ.

// ✅ 2. เปิดใช้งาน Plugin
dayjs.extend(buddhistEra);

const logs = ref({});

const fetchLogs = async (url = '/api/audit-logs') => {
    try {
        const res = await axios.get(url);
        logs.value = res.data;
    } catch (e) {
        console.error(e);
    }
};

const fetchPage = (url) => {
    if(url) fetchLogs(url);
};

// ✅ ตอนนี้ BB จะแสดงเป็นปี พ.ศ. แล้วครับ (เช่น 16 ม.ค. 2569 13:23)
const formatDate = (d) => dayjs(d).locale('th').format('D MMM BB HH:mm');

const getActionBadge = (desc) => {
    const base = "px-2 inline-flex text-xs leading-5 font-semibold rounded-full ";
    if(desc.includes('created')) return base + "bg-green-100 text-green-800";
    if(desc.includes('updated')) return base + "bg-yellow-100 text-yellow-800";
    if(desc.includes('deleted')) return base + "bg-red-100 text-red-800";
    return base + "bg-blue-100 text-blue-800";
};

onMounted(() => {
    fetchLogs();
});
</script>

<template>
  <AppLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">📋 บันทึกการใช้งานระบบ (Audit Logs)</h2>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วัน-เวลา</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้ใช้งาน</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">กิจกรรม</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(log.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ log.causer?.name || 'System' }}</div>
                    <div class="text-xs text-gray-500">{{ log.causer?.email }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                    <span :class="getActionBadge(log.description)">
                        {{ log.description }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                    <span v-if="log.subject_type" class="text-xs bg-gray-100 px-2 py-1 rounded">
                        {{ log.subject_type.split('\\').pop() }} #{{ log.subject_id }}
                    </span>
                    <span v-if="log.properties && Object.keys(log.properties).length > 0" class="text-xs text-gray-400 ml-2">
                       (มีรายละเอียด)
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4 flex justify-end" v-if="logs.links">
             <div class="space-x-1">
                <button
                  v-for="(link, k) in logs.links"
                  :key="k"
                  @click="fetchPage(link.url)"
                  :disabled="!link.url"
                  class="px-3 py-1 border rounded text-sm"
                  :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                  v-html="link.label"
                ></button>
             </div>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>
