<script setup>
import { computed } from 'vue';

const props = defineProps(['categories', 'series']);

const chartSeries = computed(() => props.series);

// ✅ คำนวณหาค่าที่มากที่สุดในข้อมูลทั้งหมด เพื่อเอามาตั้งเป็นขอบเขตแกน X
const maxValue = computed(() => {
    if (!props.series || props.series.length === 0) return 0;
    // ดึงตัวเลขทั้งหมดจากทุกชุดข้อมูลมารวมกัน แล้วหาค่ามากที่สุด
    const allValues = props.series.flatMap(s => s.data);
    return Math.max(...allValues) || 0;
});

const chartOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: 350,
    fontFamily: 'Sarabun, sans-serif',
    toolbar: { show: false },
    redrawOnParentResize: true // ✅ บังคับวาดใหม่เมื่อขนาด parent เปลี่ยน
  },
  plotOptions: {
    bar: {
      borderRadius: 4,
      horizontal: true,
      barHeight: '60%',
      dataLabels: { position: 'top' }
    }
  },
  colors: ['#E5E7EB', '#7C3AED'], // สีเทา (แผน), สีม่วง (ใช้จริง)
  dataLabels: {
      enabled: true,
      textAnchor: 'start',
      style: { colors: ['#374151'], fontSize: '10px' },
      formatter: function (val) {
          return new Intl.NumberFormat('en-US', { notation: "compact", maximumFractionDigits: 1 }).format(val);
      },
      offsetX: 10,
  },
  xaxis: {
    categories: props.categories,
    // ✅ กำหนดค่าสูงสุดของแกน X = ค่ามากสุด + 10% (กันกราฟชนขอบขวา)
    max: maxValue.value > 0 ? maxValue.value * 1.1 : undefined,
    labels: {
        formatter: (val) => new Intl.NumberFormat('en-US', { notation: "compact", maximumFractionDigits: 1 }).format(val)
    }
  },
  tooltip: {
      y: {
          formatter: (val) => new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(val)
      }
  },
  legend: { show: true, position: 'top', horizontalAlign: 'left' },
  grid: {
      borderColor: '#f3f4f6',
      strokeDashArray: 4,
      xaxis: { lines: { show: true } }
  }
}));
</script>

<template>
  <div class="w-full">
      <apexchart type="bar" height="350" width="100%" :options="chartOptions" :series="chartSeries"></apexchart>
  </div>
</template>
