import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './Pages/Dashboard.vue';
import ProjectDetail from './Pages/ProjectDetail.vue';
import ProjectsIndex from './Pages/ProjectsIndex.vue'; // 1. Import มาแล้ว (ดีมาก)
import Login from './Pages/Login.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { guest: true }
    },
    {
        path: '/',
        name: 'dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },
    // 2. *** ส่วนที่เพิ่ม: ต้องบอก Router ว่า /projects ให้ไปหน้าไหน ***
    {
        path: '/projects',
        name: 'projects.index',
        component: ProjectsIndex,
        meta: { requiresAuth: true }
    },
    // -----------------------------------------------------------
    {
        path: '/project/:id',
        name: 'project.detail',
        component: ProjectDetail,
        meta: { requiresAuth: true }
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const isLoggedIn = localStorage.getItem('isLoggedIn');

    if (to.meta.requiresAuth && !isLoggedIn) {
        next({ name: 'login' });
    }
    else if (to.meta.guest && isLoggedIn) {
        next({ name: 'dashboard' });
    }
    else {
        next();
    }
});

export default router;
