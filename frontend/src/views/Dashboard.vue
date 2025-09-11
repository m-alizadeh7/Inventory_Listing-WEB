<template>
  <div>
    <h1>داشبورد پورتال سازمانی</h1>
    <p>خوش آمدید به داشبورد پورتال سازمانی.</p>

    <!-- Stats Widgets -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card text-white bg-primary">
          <div class="card-body">
            <h5 class="card-title">کل موجودی انبار</h5>
            <p class="card-text">{{ stats.total_inventory }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-success">
          <div class="card-body">
            <h5 class="card-title">کل دستگاه‌ها</h5>
            <p class="card-text">{{ stats.total_devices }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-warning">
          <div class="card-body">
            <h5 class="card-title">سفارش‌های تولید</h5>
            <p class="card-text">{{ stats.total_production_orders }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-info">
          <div class="card-body">
            <h5 class="card-title">کل کاربران</h5>
            <p class="card-text">{{ stats.total_users }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>لینک‌های پر کاربرد</h5>
          </div>
          <div class="card-body">
            <ul class="list-group list-group-flush">
              <li class="list-group-item">
                <router-link to="/inventory" class="text-decoration-none">مدیریت موجودی انبار</router-link>
              </li>
              <li class="list-group-item">
                <router-link to="/devices" class="text-decoration-none">مدیریت دستگاه‌ها</router-link>
              </li>
              <li class="list-group-item">
                <router-link to="/users" class="text-decoration-none">مدیریت کاربران</router-link>
              </li>
              <li class="list-group-item">
                <router-link to="/production-orders" class="text-decoration-none">سفارش‌های تولید</router-link>
              </li>
              <li class="list-group-item">
                <router-link to="/suppliers" class="text-decoration-none">تامین‌کنندگان</router-link>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>گزارش‌ها و ابزارها</h5>
          </div>
          <div class="card-body">
            <ul class="list-group list-group-flush">
              <li class="list-group-item">
                <router-link to="/reports" class="text-decoration-none">گزارش‌گیری</router-link>
              </li>
              <li class="list-group-item">
                <router-link to="/settings" class="text-decoration-none">تنظیمات</router-link>
              </li>
              <li class="list-group-item">
                <router-link to="/backup" class="text-decoration-none">پشتیبان‌گیری</router-link>
              </li>
              <li class="list-group-item">
                <router-link to="/migrations" class="text-decoration-none">مهاجرت داده‌ها</router-link>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'Dashboard',
  data() {
    return {
      stats: {
        total_inventory: 0,
        total_devices: 0,
        total_production_orders: 0,
        total_users: 0
      }
    }
  },
  async mounted() {
    await this.fetchStats()
  },
  methods: {
    async fetchStats() {
      try {
        const response = await axios.get('/api/dashboard-stats')
        this.stats = response.data
      } catch (error) {
        console.error('Error fetching dashboard stats:', error)
      }
    }
  }
}
</script>

<style scoped>
.card {
  margin-bottom: 1rem;
}
</style>
