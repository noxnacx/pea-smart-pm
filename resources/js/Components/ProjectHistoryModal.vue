<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="close"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
              <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                ประวัติการแก้ไขโครงการ (Project History)
              </h3>

              <div v-if="loading" class="mt-4 text-center py-4 text-gray-500">
                กำลังโหลดข้อมูล...
              </div>

              <div v-else class="mt-4 max-h-[60vh] overflow-y-auto pr-2">
                <ul v-if="logs.length > 0" class="border-l-2 border-gray-200 ml-3 space-y-6">
                  <li v-for="log in logs" :key="log.id" class="relative pl-6">
                    <div class="absolute -left-[9px] top-1 h-4 w-4 rounded-full border-2 border-white"
                         :class="getActionColor(log.description)"></div>

                    <div class="text-sm">
                      <div class="flex justify-between items-start">
                        <span class="font-semibold text-gray-800">{{ log.causer?.name || 'System' }}</span>
                        <span class="text-xs text-gray-500">{{ formatDate(log.created_at) }}</span>
                      </div>

                      <p class="text-gray-700 mt-1">
                        {{ log.description }}
                      </p>

                      <div v-if="hasChanges(log)" class="mt-2 bg-gray-50 p-2 rounded text-xs text-gray-600 border border-gray-100">
                         <div v-if="log.properties && log.properties.attributes">
                            <div v-for="(value, key) in log.properties.attributes" :key="key" class="mb-1">
                                <span class="font-bold text-gray-700 capitalize">{{ mapFieldName(key) }}:</span>
                                <span v-if="log.properties.old && log.properties.old[key]" class="line-through text-red-400 mr-1">
                                    {{ formatValue(key, log.properties.old[key]) }}
                                </span>
                                <span class="text-green-600">
                                    ➝ {{ formatValue(key, value) }}
                                </span>
                            </div>
                         </div>

                         <div v-else-if="log.properties">
                            <div v-for="(val, k) in log.properties" :key="k">
                                <span class="font-bold">{{ k }}:</span> {{ val }}
                            </div>
                         </div>
                      </div>

                    </div>
                  </li>
                </ul>
                <div v-else class="text-gray-400 italic text-sm">ยังไม่มีประวัติการแก้ไข</div>
              </div>

            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" @click="close">
            ปิดหน้าต่าง
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import dayjs from 'dayjs';
import 'dayjs/locale/th';

const props = defineProps({
  show: Boolean,
  projectId: Number
});

const emit = defineEmits(['close']);
const logs = ref([]);
const loading = ref(false);

const close = () => emit('close');

const fetchLogs = async () => {
    if (!props.projectId) return;
    loading.value = true;
    try {
        const res = await axios.get(`/api/projects/${props.projectId}/logs`);
        logs.value = res.data;
    } catch (error) {
        console.error("Failed to fetch logs", error);
    } finally {
        loading.value = false;
    }
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        fetchLogs();
    }
});

// Helpers
const formatDate = (date) => dayjs(date).locale('th').format('D MMM BB HH:mm น.');

const getActionColor = (desc) => {
    if (desc.includes('created')) return 'bg-green-500';
    if (desc.includes('updated')) return 'bg-yellow-500';
    if (desc.includes('deleted')) return 'bg-red-500';
    return 'bg-blue-500';
};

const hasChanges = (log) => {
    return (log.properties && Object.keys(log.properties).length > 0);
};

// แปลงชื่อ Field Database เป็นภาษาคนที่เข้าใจง่าย
const mapFieldName = (field) => {
    const map = {
        'name': 'ชื่อโครงการ',
        'contract_amount': 'มูลค่าสัญญา',
        'status': 'สถานะ',
        'progress_actual': 'ความคืบหน้า (%)',
        'start_date': 'วันเริ่มสัญญา',
        'end_date': 'วันสิ้นสุดสัญญา',
        'amount': 'จำนวนเงิน'
    };
    return map[field] || field;
};

const formatValue = (key, val) => {
    // ถ้าเป็นเงิน ให้ใส่ลูกน้ำ
    if (['contract_amount', 'amount', 'paid_amount'].includes(key)) {
        return Number(val).toLocaleString();
    }
    // ถ้าเป็นวันที่
    if (key.includes('date')) {
        return dayjs(val).locale('th').format('D MMM BB');
    }
    return val;
};
</script>
