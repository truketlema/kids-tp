import { createRouter, createWebHistory } from 'vue-router'

import HomeView from '../views/HomeView.vue'
import RegisterView from '../views/RegisterView.vue'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import ProfileView from '../views/ProfileView.vue'
import FamilyView from '../views/FamilyView.vue'
import ChildProfileView from '../views/ChildProfileView.vue'
import ChildActivitySetupView from '../views/ChildActivitySetupView.vue'
import ActivitiesView from '../views/ActivitiesView.vue'
import HistoryView from '../views/HistoryView.vue'
import PointsEntryView from '../views/PointsEntryView.vue'
import { getAuthToken } from '../services/auth.js'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true },
    },
    {
      path: '/activities',
      name: 'activities',
      component: ActivitiesView,
      meta: { requiresAuth: true },
    },
    {
      path: '/activities/:categoryId',
      name: 'category-activities',
      component: ActivitiesView,
      meta: { requiresAuth: true },
    },
    {
      path: '/children/:childId/activities',
      name: 'child-activities',
      component: ActivitiesView,
      meta: { requiresAuth: true },
    },
    {
      path: '/children/setup/activities',
      name: 'child-activity-setup',
      component: ChildActivitySetupView,
      meta: { requiresAuth: true },
    },
    {
      path: '/history',
      name: 'history',
      component: HistoryView,
      meta: { requiresAuth: true },
    },
    {
      path: '/children/:childId/points/:type(award|deduct)',
      name: 'child-points',
      component: PointsEntryView,
      meta: { requiresAuth: true },
    },
    {
      path: '/profile',
      name: 'profile',
      component: ProfileView,
      meta: { requiresAuth: true },
    },
    {
      path: '/family',
      name: 'family',
      component: FamilyView,
      meta: { requiresAuth: true },
    },
    {
      path: '/children/:childId',
      name: 'child-profile',
      component: ChildProfileView,
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