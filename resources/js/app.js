import './bootstrap';
import { createApp } from 'vue';
import router from './router';
import App from './App.vue';
import VueApexCharts from "vue3-apexcharts";
import axios from 'axios'; // <--- 1. เพิ่มบรรทัดนี้

// 2. *** เพิ่มส่วนนี้: ตรวจสอบ Token ก่อนเริ่มแอป ***
const token = localStorage.getItem('auth_token');
if (token) {
    // ถ้ามี Token ค้างอยู่ ให้ตั้งค่า header รอไว้เลย
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}
// *************************************************

const app = createApp(App);

app.use(router);
app.use(VueApexCharts);
app.mount('#app');
