<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps(['projectId', 'contractAmount']);
const items = ref([]);
const loading = ref(false);

const form = ref({
  category: 'งานต้นทุนทางตรง',
  name: '',
  amount: ''
});

const categories = ['งานต้นทุนทางตรง', 'งานระบบไฟฟ้า', 'งานโยธา', 'ค่าใช้จ่ายพิเศษ', 'อื่นๆ'];

// --- 1. ฟังก์ชันดึงข้อมูล (เพิ่มการเช็คและกรองข้อมูล) ---
const fetchBudgetItems = async () => {
  if (!props.projectId) return; // ถ้าไม่มี ID ไม่ต้องดึง

  loading.value = true;
  try {
    const res = await axios.get(`/api/projects/${props.projectId}/budget`);
    // กรองเอาเฉพาะข้อมูลที่มี ID จริงๆ ป้องกันแถวว่าง
    items.value = res.data.filter(i => i && i.id);
  } catch (e) {
    console.error("Load BOQ Error:", e);
  } finally {
    loading.value = false;
  }
};

// --- 2. Watcher: ดึงข้อมูลทันทีที่ projectId เปลี่ยน ---
watch(() => props.projectId, (newId) => {
    if (newId) {
        console.log("Project ID loaded:", newId); // เช็คว่า ID มาจริงไหม
        fetchBudgetItems();
    }
}, { immediate: true });

// --- 3. ฟังก์ชันบันทึก (ปรับปรุง Error Handling) ---
const addItem = async () => {
  // Validation เบื้องต้น
  if (!form.value.name || !form.value.amount) {
      return alert('กรุณากรอกชื่อรายการและจำนวนเงินให้ครบถ้วน');
  }

  // Debug: ดูค่าที่จะส่งไป
  console.log("กำลังส่งข้อมูล:", {
      project_id: props.projectId,
      category: form.value.category,
      name: form.value.name,
      amount: form.value.amount
  });

  try {
    await axios.post('/api/budget-items', {
      project_id: props.projectId,
      category: form.value.category,
      name: form.value.name,
      amount: form.value.amount
    });

    // Reset Form
    form.value.name = '';
    form.value.amount = '';

    // โหลดข้อมูลใหม่
    await fetchBudgetItems();

    // (Optional) Alert บอกว่าสำเร็จ
    // alert('บันทึกรายการสำเร็จ');

  } catch (e) {
    // ปริ้นท์ Error ลง Console
    console.error("SAVE ERROR:", e);

    // ดึงข้อความ Error จาก Server มาโชว์
    const serverMessage = e.response?.data?.message || e.message;
    alert(`บันทึกไม่สำเร็จ: ${serverMessage}`);
  }
};

const removeItem = async (id) => {
  if(!confirm('ยืนยันลบรายการนี้?')) return;
  try {
    await axios.delete(`/api/budget-items/${id}`);
    fetchBudgetItems();
  } catch (e) {
    console.error(e);
    alert('ลบไม่สำเร็จ');
  }
};

// --- Computed Values ---
const totalAllocated = computed(() => items.value.reduce((sum, item) => sum + Number(item.amount), 0));
const remainingBudget = computed(() => Number(props.contractAmount) - totalAllocated.value);
const progressPercent = computed(() => (props.contractAmount > 0) ? (totalAllocated.value / props.contractAmount) * 100 : 0);

const formatCurrency = (val) => new Intl.NumberFormat('th-TH').format(val || 0);

onMounted(() => {
    if(props.projectId) fetchBudgetItems();
});
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
       <div class="p-4 bg-purple-50 rounded-lg border border-purple-100">
          <div class="text-xs text-purple-600 font-bold uppercase">วงเงินตามสัญญา</div>
          <div class="text-2xl font-bold text-purple-800">{{ formatCurrency(contractAmount) }}</div>
       </div>
       <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
          <div class="text-xs text-blue-600 font-bold uppercase">ยอดจัดสรรแล้ว (BOQ)</div>
          <div class="text-2xl font-bold text-blue-800">{{ formatCurrency(totalAllocated) }}</div>
          <div class="text-xs text-blue-500 mt-1">คิดเป็น {{ progressPercent.toFixed(1) }}% ของสัญญา</div>
       </div>
       <div class="p-4 rounded-lg border transition-colors" :class="remainingBudget < 0 ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'">
          <div class="text-xs font-bold uppercase" :class="remainingBudget < 0 ? 'text-red-600' : 'text-green-600'">คงเหลือจัดสรร</div>
          <div class="text-2xl font-bold" :class="remainingBudget < 0 ? 'text-red-700' : 'text-green-700'">{{ formatCurrency(remainingBudget) }}</div>
       </div>
    </div>

    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 flex flex-wrap gap-3 items-end">
       <div class="flex-1 min-w-[150px]">
          <label class="text-xs font-bold text-gray-500 mb-1 block">หมวดงาน</label>
          <select v-model="form.category" class="w-full border rounded px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-blue-500 outline-none">
             <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
          </select>
       </div>
       <div class="flex-[2] min-w-[200px]">
          <label class="text-xs font-bold text-gray-500 mb-1 block">รายการ</label>
          <input v-model="form.name" type="text" class="w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="เช่น ค่าวัสดุอุปกรณ์">
       </div>
       <div class="flex-1 min-w-[120px]">
          <label class="text-xs font-bold text-gray-500 mb-1 block">จำนวนเงิน (บาท)</label>
          <input v-model="form.amount" type="number" class="w-full border rounded px-3 py-2 text-sm text-right focus:ring-2 focus:ring-blue-500 outline-none" placeholder="0.00">
       </div>
       <button @click="addItem" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm shadow h-[38px] flex items-center gap-2 font-bold transition-transform active:scale-95">
          <span>+</span> เพิ่มรายการ
       </button>
    </div>

    <div class="border rounded-lg overflow-hidden">
       <table class="w-full text-left">
          <thead class="bg-gray-100 text-gray-600 text-xs uppercase font-semibold">
             <tr>
                <th class="px-4 py-3">หมวดงาน</th>
                <th class="px-4 py-3">รายการ</th>
                <th class="px-4 py-3 text-right">จำนวนเงิน</th>
                <th class="px-4 py-3 text-center">ลบ</th>
             </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 text-sm">
             <template v-for="item in items" :key="item.id">
                <tr v-if="item && item.id" class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-500">{{ item.category }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ item.name }}</td>
                    <td class="px-4 py-3 text-right font-mono text-gray-700">{{ formatCurrency(item.amount) }}</td>
                    <td class="px-4 py-3 text-center">
                        <button @click="removeItem(item.id)" class="text-red-400 hover:text-red-600 p-1 rounded hover:bg-red-50 transition-colors">
                           <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        </button>
                    </td>
                </tr>
             </template>
             <tr v-if="items.length === 0">
                <td colspan="4" class="text-center py-8 text-gray-400">ยังไม่มีรายการงบประมาณ (BOQ)</td>
             </tr>
          </tbody>
          <tfoot class="bg-gray-50 font-bold text-gray-700 border-t">
             <tr>
                <td colspan="2" class="px-4 py-3 text-right">รวมทั้งหมด</td>
                <td class="px-4 py-3 text-right text-blue-700">{{ formatCurrency(totalAllocated) }}</td>
                <td></td>
             </tr>
          </tfoot>
       </table>
    </div>
  </div>
</template>
