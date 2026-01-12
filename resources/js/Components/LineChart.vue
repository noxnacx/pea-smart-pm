<template>
  <div class="w-full h-80">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js';
import { Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const props = defineProps(['data']);

const chartData = computed(() => ({
  labels: props.data.map(d => d.date), // วันที่แกน X
  datasets: [
    {
      label: 'แผนงานสะสม (%)',
      borderColor: '#3B82F6', // สีฟ้า
      backgroundColor: '#3B82F6',
      data: props.data.map(d => d.planned),
      tension: 0.3
    },
    {
      label: 'ผลงานจริง (%)',
      borderColor: '#10B981', // สีเขียว
      backgroundColor: '#10B981',
      data: props.data.map(d => d.actual),
      tension: 0.3
    }
  ]
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: { beginAtZero: true, max: 100 }
  }
};
</script>
