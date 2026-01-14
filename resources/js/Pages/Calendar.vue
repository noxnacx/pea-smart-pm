<script setup>
import AppLayout from '../Components/AppLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import multiMonthPlugin from '@fullcalendar/multimonth'; // ✅ 1. Import Plugin รายปี
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const calendarOptions = ref({
  plugins: [ dayGridPlugin, interactionPlugin, multiMonthPlugin ], // ✅ 2. ใส่ Plugin ลงไป
  initialView: 'dayGridMonth',
  locale: 'th',
  events: [],

  // ✅ 3. ปรับแต่งปุ่มและภาษา
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    // ใช้เว้นวรรค (space) แทนลูกน้ำ (,) เพื่อให้ปุ่มห่างกัน
    right: 'multiMonthYear dayGridMonth dayGridWeek'
  },
  buttonText: {
    today: 'วันนี้',
    year: 'ปี',
    month: 'เดือน',
    week: 'สัปดาห์',
    day: 'วัน',
    list: 'รายการ'
  },

  eventClick: handleEventClick,
  height: 'auto'
});

const loading = ref(true);

const fetchEvents = async () => {
    try {
        const res = await axios.get('/api/calendar/events');
        calendarOptions.value.events = res.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

function handleEventClick(info) {
    alert(`📌 งาน: ${info.event.title}\n📊 ความคืบหน้า: ${info.event.extendedProps.progress}%\n📁 โครงการ: ${info.event.extendedProps.project_name}`);
}

onMounted(() => {
    fetchEvents();
});
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex justify-between items-center">
          <h1 class="text-2xl font-bold text-gray-800">📅 ปฏิทินงาน (Global Calendar)</h1>
          <span class="text-sm text-gray-500">รวมงานจากทุกโครงการที่คุณเกี่ยวข้อง</span>
      </div>

      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div v-if="loading" class="text-center py-10 text-gray-500">กำลังโหลดปฏิทิน...</div>
          <FullCalendar v-else :options="calendarOptions" class="custom-calendar" />
      </div>

      <div class="flex gap-4 justify-center text-sm">
          <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-blue-500"></div> ปกติ</div>
          <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-green-500"></div> เสร็จสิ้น</div>
          <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-red-500"></div> ล่าช้า</div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
/* ปรับแต่ง CSS ของ FullCalendar */
.fc-toolbar-title { font-size: 1.25rem !important; font-weight: bold; color: #374151; }

/* ปรับปุ่มให้เป็นธีมสีม่วง และมีระยะห่าง */
.fc-button-primary {
    background-color: #7c3aed !important;
    border-color: #7c3aed !important;
    margin-left: 5px !important; /* เพิ่มระยะห่างระหว่างปุ่ม */
}
.fc-button-primary:hover {
    background-color: #6d28d9 !important;
    border-color: #6d28d9 !important;
}
.fc-button-active {
    background-color: #5b21b6 !important; /* สีเข้มขึ้นเมื่อถูกเลือก */
    border-color: #5b21b6 !important;
}

.fc-event { cursor: pointer; border-radius: 4px; padding: 2px; font-size: 0.85rem; }
</style>
