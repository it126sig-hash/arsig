import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const routes = [
    {
        path: '/',
        name: 'home',
        component: HomeView
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('../views/LoginView.vue')
    },
    {
        path: '/floors',
        name: 'floors',
        component: () => import('../views/FloorsView.vue')
    },
    {
        path: '/rooms',
        name: 'rooms',
        component: () => import('../views/RoomsView.vue')
    },
    {
        path: '/cabinets',
        name: 'cabinets',
        component: () => import('../views/CabinetsView.vue')
    },
    {
        path: '/cabinet-slots',
        name: 'cabinet-slots',
        component: () => import('../views/CabinetSlotsView.vue')
    }
]

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes
})

router.beforeEach((to, from, next) => {
    const publicPages = ['/login']
    const authRequired = !publicPages.includes(to.path)
    const loggedIn = localStorage.getItem('token')

    if (authRequired && !loggedIn) {
        return next('/login')
    }

    if (to.path === '/login' && loggedIn) {
        return next('/')
    }

    next()
})

export default router
