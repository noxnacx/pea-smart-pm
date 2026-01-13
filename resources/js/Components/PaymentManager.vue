<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import PaymentFileModal from './PaymentFileModal.vue'; // <--- 1. Import Modal จัดการไฟล์

const props = defineProps(['projectId', 'contractAmount']);
const payments = ref([]);
const loading = ref(false);

// State สำหรับ Modal ไฟล์แนบ
const showFileModal = ref(false);
const selectedPayment = ref(null);

const form = ref({
  payment_date: new Date().toISOString().substr(0, 10),
  amount: '',
  description: ''
});

const totalPaid = computed(() => payments.value.reduce((sum, p) => sum + Number(p.amount), 0));
const remaining = computed(() => props.contractAmount - totalPaid.value);
const progressPercent = computed(() => (props.contractAmount > 0) ? (totalPaid.value / props.contractAmount) * 100 : 0);

const fetchPayments = async () => {
  try {
    const res = await axios.get(`/api/projects/${props.projectId}/payments`);
    payments.value = res.data;
  } catch (e) { console.error(e); }
};

const handleSave = async () => {
  if (!form.value.amount) return alert('กรุณาระบุจำนวนเงิน');
  try {
    await axios.post('/api/payments', { ...form.value, project_id: props.projectId });
    form.value.amount = '';
    form.value.description = '';
    fetchPayments();
    alert('บันทึกเรียบร้อย');
  } catch (e) { alert('บันทึกไม่สำเร็จ'); }
};

const handleDelete = async (id) => {
  if(!confirm('ลบรายการนี้?')) return;
  await axios.delete(`/api/payments/${id}`);
  fetchPayments();
};

// ฟังก์ชันเปิด Modal ไฟล์แนบ
const openFiles = (payment) => {
  selectedPayment.value = payment;
  showFileModal.value = true;
};

const formatCurrency = (val) => new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(val);
const formatDate = (date) => new Date(date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' });

const formatDateTime = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleString('th-TH', {
    year: '2-digit', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};

onMounted(fetchPayments);
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
        <div class="text-sm text-blue-600 mb-1">มูลค่าสัญญา</div>
        <div class="text-xl font-bold text-blue-800">{{ formatCurrency(contractAmount) }}</div>
      </div>
      <div class="bg-green-50 p-4 rounded-lg border border-green-100">
        <div class="text-sm text-green-600 mb-1">เบิกจ่ายแล้ว</div>
        <div class="text-xl font-bold text-green-800">{{ formatCurrency(totalPaid) }}</div>
        <div class="text-xs text-green-600 mt-1">({{ progressPercent.toFixed(2) }}%)</div>
      </div>
      <div class="bg-orange-50 p-4 rounded-lg border border-orange-100">
        <div class="text-sm text-orange-600 mb-1">คงเหลือ</div>
        <div class="text-xl font-bold text-orange-800">{{ formatCurrency(remaining) }}</div>
      </div>
    </div>

    <div class="bg-white border rounded-lg overflow-hidden">
      <div class="p-4 bg-gray-50 border-b font-bold text-gray-700 flex justify-between items-center">
        <span>📜 ประวัติการเบิกจ่าย</span>
      </div>

      <div class="p-4 bg-gray-50 border-b grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
        <div>
          <label class="text-xs text-gray-500">วันที่</label>
          <input v-model="form.payment_date" type="date" class="w-full border rounded px-2 py-1 text-sm">
        </div>
        <div class="md:col-span-2">
          <label class="text-xs text-gray-500">รายการ (เช่น งวดที่ 1)</label>
          <input v-model="form.description" type="text" class="w-full border rounded px-2 py-1 text-sm" placeholder="ระบุรายละเอียด...">
        </div>
        <div>
          <label class="text-xs text-gray-500">จำนวนเงิน</label>
          <div class="flex gap-2">
             <input v-model="form.amount" type="number" class="w-full border rounded px-2 py-1 text-sm" placeholder="0.00">
             <button @click="handleSave" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 whitespace-nowrap">บันทึก</button>
          </div>
        </div>
      </div>

      <table class="w-full text-left text-sm">
        <thead class="bg-gray-100 text-gray-600">
          <tr>
            <th class="px-4 py-2">วันที่</th>
            <th class="px-4 py-2">รายการ</th>
            <th class="px-4 py-2 text-right">จำนวนเงิน</th>
            <th class="px-4 py-2 text-center">หลักฐาน</th> <th class="px-4 py-2 text-center">ผู้บันทึก</th>
            <th class="px-4 py-2 text-center">ลบ</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="pay in payments" :key="pay.id">
            <td class="px-4 py-2">{{ formatDate(pay.payment_date) }}</td>
            <td class="px-4 py-2">{{ pay.description }}</td>
            <td class="px-4 py-2 text-right font-medium">{{ formatCurrency(pay.amount) }}</td>

            <td class="px-4 py-2 text-center">
               <button @click="openFiles(pay)" class="text-blue-500 hover:text-blue-700 flex items-center justify-center gap-1 w-full transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                  <span class="text-xs font-medium">แนบไฟล์</span>
               </button>
            </td>

            <td class="px-4 py-2 text-center">
              <div v-if="pay.user">
                 <div class="text-gray-700 font-medium">{{ pay.user.name }}</div>
                 <div class="text-[10px] text-gray-400">เมื่อ {{ formatDateTime(pay.created_at) }}</div>
              </div>
              <span v-else>-</span>
            </td>

            <td class="px-4 py-2 text-center">
              <button @click="handleDelete(pay.id)" class="text-red-400 hover:text-red-600">×</button>
            </td>
          </tr>
          <tr v-if="payments.length === 0">
            <td colspan="6" class="text-center py-4 text-gray-400">ยังไม่มีรายการเบิกจ่าย</td>
          </tr>
        </tbody>
      </table>
    </div>

    <PaymentFileModal
      :isOpen="showFileModal"
      :payment="selectedPayment"
      @close="showFileModal = false"
    />
  </div>
</template>
