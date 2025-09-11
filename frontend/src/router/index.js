import { createRouter, createWebHistory } from 'vue-router'
import Dashboard from '../views/Dashboard.vue'
import Login from '../views/Login.vue'

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/devices',
    name: 'Devices',
    component: () => import('../views/Devices.vue')
  },
  {
    path: '/inventory',
    name: 'Inventory',
    component: () => import('../views/Inventory.vue')
  },
  {
    path: '/users',
    name: 'Users',
    component: () => import('../views/Users.vue')
  },
  {
    path: '/production-orders',
    name: 'ProductionOrders',
    component: () => import('../views/ProductionOrders.vue')
  },
  {
    path: '/suppliers',
    name: 'Suppliers',
    component: () => import('../views/Suppliers.vue')
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('../views/Reports.vue')
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('../views/Settings.vue')
  },
  {
    path: '/backup',
    name: 'Backup',
    component: () => import('../views/Backup.vue')
  },
  {
    path: '/migrations',
    name: 'Migrations',
    component: () => import('../views/Migrations.vue')
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
