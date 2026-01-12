import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// --- ส่วนที่เพิ่ม: ตั้งค่า Token ---
// 1. ดึง Token ที่เก็บไว้ในเครื่อง
const token = localStorage.getItem('token');

// 2. ถ้ามี Token ให้แนบไปกับ "ทุก" Request ที่ส่งไปหา Server
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// 3. (แถม) ดักจับกรณี Token หมดอายุ (401) ให้เด้งไปหน้า Login อัตโนมัติ
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            // ลบข้อมูลเก่าทิ้ง
            localStorage.removeItem('token');
            localStorage.removeItem('isLoggedIn');
            // ดีดกลับหน้า Login
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);
