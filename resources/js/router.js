import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './Pages/Dashboard.vue';
import ProjectDetail from './Pages/ProjectDetail.vue';
import ProjectsIndex from './Pages/ProjectsIndex.vue';
import Login from './Pages/Login.vue';

const routes = [
    {
        path: '/login',
        name: 'Login', // ตั้งชื่อให้ชัดเจน
        component: Login,
        meta: { guest: true } // หน้าสำหรับคนยังไม่ล็อกอิน
    },
    {
        path: '/',
        redirect: '/dashboard' // Redirect ไป Dashboard
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/projects',
        name: 'projects.index',
        component: ProjectsIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/project/:id',
        name: 'project.detail',
        component: ProjectDetail,
        meta: { requiresAuth: true }
    },
    {
        path: '/programs',
        component: ProgramsIndex,
        meta: { requiresAuth: true } // ถ้าอยากเข้มงวดกว่านี้ ต้องเช็ค Role ใน router guard ด้วย
    },
    {
        path: '/users',
        component: UsersIndex,
        meta: { requiresAuth: true }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// *** Navigation Guard (ยามเฝ้าประตู) ***
router.beforeEach((to, from, next) => {
    // เปลี่ยนจากเช็ค isLoggedIn เป็นเช็ค Token ที่ได้จาก API
    const token = localStorage.getItem('auth_token');

    // 1. ถ้าจะไปหน้า 'ต้องล็อกอิน' (requiresAuth) แต่ไม่มี Token -> ดีดไป Login
    if (to.meta.requiresAuth && !token) {
        next({ name: 'Login' });
    }
    // 2. ถ้าจะไปหน้า 'Login' (guest) แต่มี Token แล้ว -> ดีดเข้า Dashboard เลย (ไม่ต้องล็อกอินซ้ำ)
    else if (to.meta.guest && token) {
        next({ name: 'dashboard' });
    }
    // 3. กรณีปกติ -> ปล่อยผ่าน
    else {
        next();
    }
});

export default router;
