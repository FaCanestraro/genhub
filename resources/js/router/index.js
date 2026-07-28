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
            { path: 'dashboard', component: () => import('@/pages/DashboardPage.vue'), meta: { menu: 'dashboard' } },
            { path: 'generate', component: () => import('@/pages/GeneratePage.vue'), meta: { menu: 'generate' } },
            { path: 'generate-prompts', component: () => import('@/pages/GeneratePage2.vue'), meta: { menu: 'generate_prompts' } },
            { path: 'history', component: () => import('@/pages/HistoryPage.vue'), meta: { menu: 'history' } },
            { path: 'products', component: () => import('@/pages/ProductsPage.vue'), meta: { menu: 'products' } },
            { path: 'templates', component: () => import('@/pages/TemplatesPage.vue'), meta: { menu: 'templates' } },
            { path: 'gallery', component: () => import('@/pages/GalleryPage.vue'), meta: { menu: 'gallery' } },
            { path: 'campaigns', component: () => import('@/pages/CampaignsPage.vue'), meta: { menu: 'campaigns' } },
            { path: 'campaigns/:id', component: () => import('@/pages/CampaignDetailPage.vue'), meta: { menu: 'campaigns' } },
            { path: 'campaigns/:campaignId/actions/:actionId', component: () => import('@/pages/ActionDetailPage.vue'), meta: { menu: 'campaigns' } },
            { path: 'leads', component: () => import('@/pages/LeadsPage.vue'), meta: { menu: 'leads' } },
            { path: 'leads/:id', component: () => import('@/pages/LeadDetailPage.vue'), meta: { menu: 'leads' } },
            { path: 'pipeline', component: () => import('@/pages/PipelinePage.vue'), meta: { menu: 'pipeline' } },
            { path: 'tasks', component: () => import('@/pages/TasksPage.vue'), meta: { menu: 'tasks' } },
            { path: 'settings', component: () => import('@/pages/SettingsPage.vue'), meta: { menu: 'settings' } },
            { path: 'profile', component: () => import('@/pages/ProfilePage.vue') },
            { path: 'sem-acesso', component: () => import('@/pages/NoAccessPage.vue') },
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

    if (to.meta.menu && !auth.can(to.meta.menu, 'view')) {
        return '/sem-acesso'
    }
})

export default router
