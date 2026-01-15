<script setup>
import { computed } from 'vue';

const props = defineProps(['data']);

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
    toolbar: { show: false },
    redrawOnParentResize: true // ✅ แก้ปัญหาหด
  },
  colors: ['#9CA3AF', '#7C3AED'],
  stroke: { curve: 'smooth', width: 3 },
  xaxis: {
    categories: props.data.map(d => d.date),
    type: 'datetime',
    labels: { format: 'dd MMM yy' }
  },
  yaxis: {
    min: 0,
    max: 100, // ✅ ล็อกสเกล 0-100 เสมอ
    labels: { formatter: (val) => val.toFixed(0) }
  },
  tooltip: { x: { format: 'dd MMM yyyy' } },
  legend: { position: 'top' },
  grid: { borderColor: '#f1f1f1' }
}));
</script>

<template>
  <div class="w-full">
      <apexchart type="line" height="350" width="100%" :options="chartOptions" :series="series"></apexchart>
  </div>
</template>
