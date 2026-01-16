import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './Pages/Dashboard.vue';
import ProjectDetail from './Pages/ProjectDetail.vue';
import ProjectsIndex from './Pages/ProjectsIndex.vue';
import Login from './Pages/Login.vue';
import Calendar from './Pages/Calendar.vue';
import ProgramsIndex from './Pages/ProgramsIndex.vue';
import UsersIndex from './Pages/UsersIndex.vue';
import ProgramDetail from './Pages/ProgramDetail.vue';
import MyProjects from './Pages/MyProjects.vue';
import MyTasks from './Pages/MyTasks.vue'
import NotificationsIndex from './Pages/NotificationsIndex.vue';
import FinanceIndex from './Pages/FinanceIndex.vue';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
        meta: { guest: true }
    },
    {
        path: '/',
        redirect: '/dashboard'
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
        name: 'programs.index', // ตั้งชื่อ route ไว้หน่อยก็ดีครับ
        component: ProgramsIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/users',
        name: 'users.index', // ตั้งชื่อ route ไว้หน่อยก็ดีครับ
        component: UsersIndex,
        meta: { requiresAuth: true }
    },
    {
        path: '/programs/:id',
        component: ProgramDetail,
        meta: { requiresAuth: true }
    },
    {
        path: '/my-projects',
        component: MyProjects,
        meta: { requiresAuth: true }
    },
    {
        path: '/calendar',
        name: 'calendar',
        component: Calendar,
        meta: { requiresAuth: true }
    },
    {
        path: '/my-tasks',
        name: 'my-tasks',
        component: MyTasks,
        meta: { requiresAuth: true }
    },
    {   path: '/notifications',
        name: 'notifications',
        component: NotificationsIndex,
        meta: { requiresAuth: true }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('auth_token');

    if (to.meta.requiresAuth && !token) {
        next({ name: 'Login' });
    }
    else if (to.meta.guest && token) {
        next({ name: 'dashboard' });
    }
    else {
        next();
    }
});

export default router;
