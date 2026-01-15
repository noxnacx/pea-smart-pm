<script setup>
import { computed } from 'vue';

const props = defineProps(['labels', 'series']);

const chartOptions = computed(() => ({
  chart: { type: 'donut', fontFamily: 'Sarabun, sans-serif' },
  labels: props.labels,
  colors: ['#10B981', '#3B82F6', '#EF4444', '#9CA3AF'], // เขียว, ฟ้า, แดง, เทา
  plotOptions: {
    pie: {
      donut: {
        size: '65%',
        labels: {
          show: true,
          total: {
            show: true,
            label: 'ทั้งหมด',
            color: '#373d3f',
            formatter: function (w) {
              return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + " โครงการ"
            }
          }
        }
      }
    }
  },
  dataLabels: { enabled: false },
  legend: { position: 'bottom' }
}));
</script>

<template>
  <apexchart type="donut" height="320" :options="chartOptions" :series="series"></apexchart>
</template>
