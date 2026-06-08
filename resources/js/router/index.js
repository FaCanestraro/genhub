import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    { path: '/login', component: () => import('@/pages/LoginPage.vue'), meta: { guest: true } },
    { path: '/register', component: () => import('@/pages/RegisterPage.vue'), meta: { guest: true } },
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/dashboard' },
            { path: 'dashboard', component: () => import('@/pages/DashboardPage.vue') },
            { path: 'generate', component: () => import('@/pages/GeneratePage.vue') },
            { path: 'generate-prompts', component: () => import('@/pages/GeneratePage2.vue') },
            { path: 'history', component: () => import('@/pages/HistoryPage.vue') },
            { path: 'products', component: () => import('@/pages/ProductsPage.vue') },
            { path: 'templates', component: () => import('@/pages/TemplatesPage.vue') },
            { path: 'gallery', component: () => import('@/pages/GalleryPage.vue') },
            { path: 'campaigns', component: () => import('@/pages/CampaignsPage.vue') },
            { path: 'campaigns/:id', component: () => import('@/pages/CampaignDetailPage.vue') },
            { path: 'campaigns/:campaignId/actions/:actionId', component: () => import('@/pages/ActionDetailPage.vue') },
            { path: 'leads', component: () => import('@/pages/LeadsPage.vue') },
            { path: 'leads/:id', component: () => import('@/pages/LeadDetailPage.vue') },
            { path: 'pipeline', component: () => import('@/pages/PipelinePage.vue') },
            { path: 'tasks', component: () => import('@/pages/TasksPage.vue') },
            { path: 'settings', component: () => import('@/pages/SettingsPage.vue') },
            { path: 'profile', component: () => import('@/pages/ProfilePage.vue') },
        ],
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach(async (to) => {
    const auth = useAuthStore()

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return '/login'
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return '/dashboard'
    }

    if (to.meta.requiresAuth && auth.isAuthenticated && !auth.user) {
        await auth.fetchMe().catch(() => {})
    }
})

export default router
