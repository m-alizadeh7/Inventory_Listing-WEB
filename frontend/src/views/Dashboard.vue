<template>
  <div>
    <h1>داشبورد پورتال سازمانی</h1>
    <p>خوش آمدید به داشبورد پورتال سازمانی.</p>

    <!-- Welcome Section -->
    <div class="welcome-section mb-4">
      <h2 class="welcome-title">
        <i class="bi bi-house-door"></i>
        {{ businessName }}
      </h2>
      <p class="welcome-subtitle">مدیریت هوشمند موجودی، انبارگردانی و تولید</p>
    </div>

    <!-- Dashboard Statistics -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card text-white bg-primary">
          <div class="card-body">
            <div class="card-icon">
              <i class="bi bi-box-seam"></i>
            </div>
            <div class="card-value">{{ stats.total_inventory }}</div>
            <div class="card-label">کالاهای موجود</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-info">
          <div class="card-body">
            <div class="card-icon">
              <i class="bi bi-hdd-stack"></i>
            </div>
            <div class="card-value">{{ stats.total_devices }}</div>
            <div class="card-label">دستگاه‌ها</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-success">
          <div class="card-body">
            <div class="card-icon">
              <i class="bi bi-list-check"></i>
            </div>
            <div class="card-value">{{ stats.total_production_orders }}</div>
            <div class="card-label">سفارشات تولید</div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card text-white bg-warning">
          <div class="card-body">
            <div class="card-icon">
              <i class="bi bi-clock-history"></i>
            </div>
            <div class="card-value">{{ stats.pending_orders }}</div>
            <div class="card-label">سفارشات آماده</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-4">
      <h2 class="section-title">
        <i class="bi bi-lightning-fill"></i>
        دسترسی سریع
      </h2>
      <div class="row">
        <div class="col-md-3 mb-3">
          <div class="card quick-action-card" @click="navigateTo('/inventory')">
            <div class="card-body text-center">
              <div class="quick-action-icon bg-primary bg-opacity-10 text-primary mb-2">
                <i class="bi bi-plus-circle"></i>
              </div>
              <h5 class="quick-action-title">انبارگردانی جدید</h5>
              <p class="quick-action-text">شروع یک انبارگردانی جدید</p>
              <span class="quick-action-btn btn-primary">
                <i class="bi bi-arrow-left"></i>
                شروع
              </span>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card quick-action-card" @click="navigateTo('/production-orders')">
            <div class="card-body text-center">
              <div class="quick-action-icon bg-warning bg-opacity-10 text-warning mb-2">
                <i class="bi bi-plus-square"></i>
              </div>
              <h5 class="quick-action-title">سفارش تولید جدید</h5>
              <p class="quick-action-text">ایجاد سفارش تولید جدید</p>
              <span class="quick-action-btn btn-warning">
                <i class="bi bi-arrow-left"></i>
                ایجاد
              </span>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card quick-action-card" @click="navigateTo('/settings')">
            <div class="card-body text-center">
              <div class="quick-action-icon bg-info bg-opacity-10 text-info mb-2">
                <i class="bi bi-gear"></i>
              </div>
              <h5 class="quick-action-title">تنظیمات سیستم</h5>
              <p class="quick-action-text">مدیریت تنظیمات سیستم</p>
              <span class="quick-action-btn btn-info">
                <i class="bi bi-arrow-left"></i>
                تنظیمات
              </span>
            </div>
          </div>
        </div>
        <div class="col-md-3 mb-3">
          <div class="card quick-action-card" @click="navigateTo('/backup')">
            <div class="card-body text-center">
              <div class="quick-action-icon bg-success bg-opacity-10 text-success mb-2">
                <i class="bi bi-download"></i>
              </div>
              <h5 class="quick-action-title">پشتیبان‌گیری</h5>
              <p class="quick-action-text">ایجاد پشتیبان از داده‌ها</p>
              <span class="quick-action-btn btn-success">
                <i class="bi bi-arrow-left"></i>
                پشتیبان‌گیری
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity and Quick Stats -->
    <div class="row">
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="section-title mb-0">
              <i class="bi bi-activity"></i>
              فعالیت‌های اخیر
            </h5>
          </div>
          <div class="card-body">
            <div v-if="recentActivities.length > 0" class="activity-list">
              <div v-for="activity in recentActivities" :key="activity.id" class="activity-item mb-3">
                <div class="activity-icon bg-primary bg-opacity-10 text-primary">
                  <i class="bi bi-box-seam"></i>
                </div>
                <div class="activity-content">
                  <div class="activity-text">
                    <strong>{{ activity.item_name }}</strong>
                    موجودی به‌روزرسانی شد
                  </div>
                  <small class="activity-time">{{ activity.time_ago }}</small>
                </div>
              </div>
            </div>
            <div v-else class="text-center text-muted py-4">
              <i class="bi bi-clock-history fs-1 mb-2"></i>
              <p>فعالیت‌های اخیر به زودی نمایش داده خواهد شد</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="card">
          <div class="card-header">
            <h5 class="section-title mb-0">
              <i class="bi bi-bar-chart"></i>
              آمار سریع
            </h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-6">
                <div class="text-center">
                  <div class="fs-4 fw-bold text-primary">{{ stats.total_suppliers }}</div>
                  <small class="text-muted">تامین‌کننده</small>
                </div>
              </div>
              <div class="col-6">
                <div class="text-center">
                  <div class="fs-4 fw-bold text-info">{{ stats.total_categories }}</div>
                  <small class="text-muted">گروه کالا</small>
                </div>
              </div>
            </div>
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
      businessName: 'سیستم مدیریت انبار',
      stats: {
        total_inventory: 0,
        total_devices: 0,
        total_production_orders: 0,
        pending_orders: 0,
        total_suppliers: 0,
        total_categories: 0
      },
      recentActivities: []
    }
  },
  async mounted() {
    await this.fetchStats()
    await this.fetchRecentActivities()
  },
  methods: {
    async fetchStats() {
      try {
        const response = await axios.get('/api/dashboard-stats')
        this.stats = response.data
      } catch (error) {
        console.error('Error fetching dashboard stats:', error)
      }
    },
    async fetchRecentActivities() {
      try {
        const response = await axios.get('/api/recent-activities')
        this.recentActivities = response.data
      } catch (error) {
        console.error('Error fetching recent activities:', error)
      }
    },
    navigateTo(route) {
      this.$router.push(route)
    }
  }
}
</script>

<style scoped>
.welcome-section {
  text-align: center;
  margin-bottom: 2rem;
}

.welcome-title {
  font-size: 2.5rem;
  color: #007bff;
  margin-bottom: 0.5rem;
}

.welcome-subtitle {
  font-size: 1.2rem;
  color: #6c757d;
}

.card {
  margin-bottom: 1rem;
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-2px);
}

.card-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.card-value {
  font-size: 2rem;
  font-weight: bold;
}

.card-label {
  font-size: 0.9rem;
}

.section-title {
  color: #495057;
  margin-bottom: 1rem;
}

.quick-action-card {
  cursor: pointer;
  transition: all 0.3s;
}

.quick-action-card:hover {
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.quick-action-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  font-size: 1.5rem;
}

.quick-action-title {
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}

.quick-action-text {
  font-size: 0.9rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.quick-action-btn {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
}

.activity-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e9ecef;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-text {
  margin-bottom: 0.25rem;
}

.activity-time {
  color: #6c757d;
  font-size: 0.8rem;
}
</style>
