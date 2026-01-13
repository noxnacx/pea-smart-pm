<script setup>
import { computed } from 'vue';

const props = defineProps(['data']);

// แปลงข้อมูลให้เป็น Format ของ ApexCharts
const series = computed(() => [{
  name: 'แผนงานสะสม (%)',
  data: props.data.map(d => d.planned)
}, {
  name: 'ผลงานจริงสะสม (%)',
  data: props.data.map(d => d.actual)
}]);

const chartOptions = computed(() => ({
  chart: {
    type: 'line',
    height: 350,
    fontFamily: 'Sarabun, sans-serif',
    toolbar: { show: false }
  },
  colors: ['#9CA3AF', '#7C3AED'], // สีเทา (แผน), สีม่วง (จริง)
  stroke: { curve: 'smooth', width: 3 },
  xaxis: {
    categories: props.data.map(d => d.date), // วันที่แกน X
    type: 'datetime',
    labels: { format: 'dd MMM yy' }
  },
  yaxis: {
    min: 0,
    max: 100,
    labels: { formatter: (val) => val.toFixed(0) }
  },
  tooltip: { x: { format: 'dd MMM yyyy' } },
  legend: { position: 'top' },
  grid: { borderColor: '#f1f1f1' }
}));
</script>

<template>
  <apexchart type="line" height="300" :options="chartOptions" :series="series"></apexchart>
</template>
