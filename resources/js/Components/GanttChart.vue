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
    // หาชื่องานแม่ (ถ้ามี)
    const predecessorName = task.predecessor_id
        ? props.tasks.find(t => t.id === task.predecessor_id)?.name
        : null;

    return {
      x: task.name, // แกน X คือชื่องาน
      y: [
        new Date(task.start_date).getTime(), // เวลาเริ่ม
        new Date(task.end_date).getTime()    // เวลาจบ
      ],
      fillColor: getColor(task),
      // ✅ ฝากข้อมูลไว้ใช้ใน Tooltip
      meta: {
          predecessor: predecessorName
      }
    };
  });

  return [{ data: data }];
});

const getColor = (task) => {
  if (task.progress == 100) return '#10B981'; // เขียว
  return '#3B82F6'; // ฟ้า
};

// 2. ตั้งค่าหน้าตากราฟ
const chartOptions = {
  chart: {
    type: 'rangeBar',
    toolbar: { show: false }
  },
  plotOptions: {
    bar: {
      horizontal: true,
      barHeight: '50%',
      borderRadius: 4
    }
  },
  xaxis: {
    type: 'datetime',
    labels: {
      format: 'dd MMM',
      style: { colors: '#64748b' }
    }
  },
  grid: {
    strokeDashArray: 4,
  },
  // ✅ ปรับแต่ง Tooltip
  tooltip: {
    custom: function({ series, seriesIndex, dataPointIndex, w }) {
        const data = w.globals.initialSeries[seriesIndex].data[dataPointIndex];
        const startDate = new Date(data.y[0]).toLocaleDateString('th-TH');
        const endDate = new Date(data.y[1]).toLocaleDateString('th-TH');

        // ถ้ามีงานก่อนหน้า ให้โชว์เตือนสีแดง
        const predText = data.meta.predecessor
            ? `<div class="text-xs text-red-500 mt-2 pt-2 border-t border-gray-200">🔒 <b>เงื่อนไข:</b> ต้องรอ "${data.meta.predecessor}" เสร็จก่อน</div>`
            : '';

        return `
            <div class="px-4 py-3 bg-white border border-gray-200 shadow-xl rounded-lg text-sm text-gray-700">
                <div class="font-bold text-base mb-1 text-purple-700">${data.x}</div>
                <div class="text-gray-500">📅 ${startDate} - ${endDate}</div>
                ${predText}
            </div>
        `;
    }
  }
};
</script>
