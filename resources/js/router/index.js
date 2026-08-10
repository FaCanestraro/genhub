import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useCompanyStore } from '@/stores/company'

const routes = [
    { path: '/login', component: () => import('@/pages/LoginPage.vue'), meta: { guest: true } },
    { path: '/register', component: () => import('@/pages/RegisterPage.vue'), meta: { guest: true } },
    { path: '/choose-area', component: () => import('@/pages/ChooseAreaPage.vue'), meta: { requiresAuth: true } },
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
    {
        path: '/admin',
        component: () => import('@/layouts/AdminLayout.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
        children: [
            { path: '', redirect: 'clients' },
            { path: 'clients', component: () => import('@/pages/admin/AdminClientsPage.vue') },
            { path: 'templates', component: () => import('@/pages/admin/AdminTemplatesPage.vue') },
            { path: 'users', component: () => import('@/pages/admin/AdminUsersPage.vue') },
        ],
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

// Whether this account needs an explicit choice before landing anywhere: either it can reach
// both the admin panel and the client system, or it belongs to more than one company.
function needsAreaChoice(auth, companyStore) {
    return (auth.isPlatformAdmin && auth.isClient) || (auth.isClient && companyStore.hasMultiple)
}

// Where a user should land once no choice is needed (or right after making one).
export function landingPath(auth, companyStore) {
    if (needsAreaChoice(auth, companyStore)) return '/choose-area'
    if (auth.isPlatformAdmin) return '/admin/clients'
    return '/dashboard'
}

router.beforeEach(async (to) => {
    const auth = useAuthStore()
    const companyStore = useCompanyStore()

    if (auth.isAuthenticated && !auth.user) {
        await auth.fetchMe().catch(() => {})
    }

    if (auth.isAuthenticated && auth.isClient && !companyStore.companies.length) {
        await companyStore.fetchCompanies().catch(() => {})
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return '/login'
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return landingPath(auth, companyStore)
    }

    // Only accounts that actually face a choice should see the chooser.
    if (to.path === '/choose-area' && !needsAreaChoice(auth, companyStore)) {
        return landingPath(auth, companyStore)
    }

    if (to.meta.requiresAdmin && !auth.isPlatformAdmin) {
        return '/dashboard'
    }

    // Platform-admin-only accounts (no client company) never see the client-facing system.
    if (to.meta.requiresAuth && !to.meta.requiresAdmin && to.path !== '/choose-area' && !auth.isClient) {
        return '/admin/clients'
    }

    if (to.meta.menu && !auth.can(to.meta.menu, 'view')) {
        return '/sem-acesso'
    }
})

export default router
