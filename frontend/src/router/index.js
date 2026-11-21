import { createRouter, createWebHistory } from 'vue-router'

import { getAuthToken } from '../services/auth.js'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('../views/HomeView.vue'),
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: () => import('../views/DashboardView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/activities',
      name: 'activities',
      component: () => import('../views/ActivitiesView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/activities/:categoryId',
      name: 'category-activities',
      component: () => import('../views/ActivitiesView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/children/:childId/activities',
      name: 'child-activities',
      component: () => import('../views/ActivitiesView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/children/setup/activities',
      name: 'child-activity-setup',
      component: () => import('../views/ChildActivitySetupView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/history',
      name: 'history',
      component: () => import('../views/HistoryView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/children/:childId/points/:type(award|deduct)',
      name: 'child-points',
      component: () => import('../views/PointsEntryView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/family',
      name: 'family',
      component: () => import('../views/FamilyView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/children/:childId',
      name: 'child-profile',
      component: () => import('../views/ChildProfileView.vue'),
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach((to) => {
  const isAuthenticated = Boolean(getAuthToken())

  if (to.meta.requiresAuth && !isAuthenticated) {
    return { name: 'login' }
  }

  if (isAuthenticated && (to.name === 'login' || to.name === 'register')) {
    return { name: 'dashboard' }
  }
})

export default router