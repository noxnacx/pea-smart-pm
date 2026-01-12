<template>
  <div class="w-full bg-white rounded-lg shadow p-4">
    <h3 class="font-bold text-gray-700 mb-4">📅 แผนภูมิระยะเวลา (Gantt Chart)</h3>
    <div v-if="series[0].data.length > 0">
      <apexchart
        type="rangeBar"
        height="350"
        :options="chartOptions"
        :series="series"
      ></apexchart>
    </div>
    <div v-else class="text-center text-gray-400 py-10">
      ไม่มีข้อมูลช่วงเวลา
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps(['tasks']);

// 1. แปลงข้อมูล Tasks ให้เป็นรูปแบบที่กราฟเข้าใจ
const series = computed(() => {
  if (!props.tasks) return [{ data: [] }];

  const data = props.tasks.map(task => {
    return {
      x: task.name, // แกน X คือชื่องาน
      y: [
        new Date(task.start_date).getTime(), // เวลาเริ่ม (ต้องแปลงเป็น Timestamp)
        new Date(task.end_date).getTime()    // เวลาจบ
      ],
      fillColor: getColor(task) // เลือกสีตามสถานะ
    };
  });

  return [{ data: data }];
});

// ฟังก์ชันเลือกสี: ถ้าเสร็จแล้วสีเขียว, ยังไม่เสร็จสีฟ้า
const getColor = (task) => {
  if (task.progress == 100) return '#10B981'; // เขียว
  return '#3B82F6'; // ฟ้า
};

// 2. ตั้งค่าหน้าตากราฟ
const chartOptions = {
  chart: {
    type: 'rangeBar',
    toolbar: { show: false } // ปิดเมนูหัวกราฟ
  },
  plotOptions: {
    bar: {
      horizontal: true, // แนวนอน
      barHeight: '50%',
      borderRadius: 4
    }
  },
  xaxis: {
    type: 'datetime', // แกนล่างเป็นวันที่
    labels: {
      format: 'dd MMM', // รูปแบบวันที่ (เช่น 12 Jan)
      style: { colors: '#64748b' }
    }
  },
  tooltip: {
    x: { format: 'dd MMM yyyy' } // Tooltip ตอนเอาเมาส์ชี้
  },
  grid: {
    strokeDashArray: 4, // เส้นประพื้นหลัง
  }
};
</script>
