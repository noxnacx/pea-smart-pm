import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './Pages/Dashboard.vue';
import ProjectDetail from './Pages/ProjectDetail.vue';
import Login from './Pages/Login.vue'; // <--- 1. Import หน้า Login

const routes = [
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { guest: true } // หน้าสำหรับคนยังไม่ล็อกอิน
    },
    {
        path: '/',
        name: 'dashboard',
        component: Dashboard,
        meta: { requiresAuth: true } // <--- 2. แปะป้ายว่า "ต้องล็อกอิน"
    },
    {
        path: '/project/:id',
        name: 'project.detail',
        component: ProjectDetail,
        meta: { requiresAuth: true } // <--- 2. แปะป้ายว่า "ต้องล็อกอิน"
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// 3. สร้าง "ด่านตรวจ" (Navigation Guard)
router.beforeEach((to, from, next) => {
    // เช็คว่าผู้ใช้มี "บัตรผ่าน" หรือยัง (ดูจาก localStorage ง่ายๆ)
    const isLoggedIn = localStorage.getItem('isLoggedIn');

    // ถ้าจะไปหน้าที่ต้องการ Auth แต่ยังไม่ Login -> ดีดไป Login
    if (to.meta.requiresAuth && !isLoggedIn) {
        next({ name: 'login' });
    }
    // ถ้าล็อกอินแล้ว แต่อยากกลับไปหน้า Login -> ดีดไป Dashboard
    else if (to.meta.guest && isLoggedIn) {
        next({ name: 'dashboard' });
    }
    // กรณีอื่นๆ ปล่อยผ่าน
    else {
        next();
    }
});

export default router;
