<template>
  <div>
    <h1>مدیریت موجودی انبار</h1>
    <p>این صفحه برای مدیریت موجودی کالاها در انبار است.</p>

    <!-- Add/Edit Form -->
    <div class="mb-4">
      <button class="btn btn-primary" @click="showForm = !showForm">
        {{ showForm ? 'بستن فرم' : 'افزودن کالا جدید' }}
      </button>
    </div>

    <div v-if="showForm" class="card mb-4">
      <div class="card-header">
        <h5>{{ editingItem ? 'ویرایش کالا' : 'افزودن کالا جدید' }}</h5>
      </div>
      <div class="card-body">
        <form @submit.prevent="saveItem">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="inventory_code" class="form-label">کد انبار</label>
              <input type="text" class="form-control" id="inventory_code" v-model="form.inventory_code" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="item_name" class="form-label">نام کالا</label>
              <input type="text" class="form-control" id="item_name" v-model="form.item_name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="unit" class="form-label">واحد</label>
              <input type="text" class="form-control" id="unit" v-model="form.unit">
            </div>
            <div class="col-md-6 mb-3">
              <label for="min_inventory" class="form-label">حداقل موجودی</label>
              <input type="number" class="form-control" id="min_inventory" v-model.number="form.min_inventory">
            </div>
            <div class="col-md-6 mb-3">
              <label for="supplier" class="form-label">تامین‌کننده</label>
              <input type="text" class="form-control" id="supplier" v-model="form.supplier">
            </div>
            <div class="col-md-6 mb-3">
              <label for="current_inventory" class="form-label">موجودی فعلی</label>
              <input type="number" step="0.01" class="form-control" id="current_inventory" v-model.number="form.current_inventory">
            </div>
            <div class="col-md-6 mb-3">
              <label for="required" class="form-label">مورد نیاز</label>
              <input type="number" step="0.01" class="form-control" id="required" v-model.number="form.required">
            </div>
            <div class="col-md-12 mb-3">
              <label for="notes" class="form-label">یادداشت‌ها</label>
              <textarea class="form-control" id="notes" v-model="form.notes" rows="3"></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-success">{{ editingItem ? 'به‌روزرسانی' : 'افزودن' }}</button>
          <button type="button" class="btn btn-secondary ms-2" @click="cancelEdit">لغو</button>
        </form>
      </div>
    </div>

    <!-- Inventory List -->
    <div class="card">
      <div class="card-header">
        <h5>لیست کالاها</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>کد انبار</th>
                <th>نام کالا</th>
                <th>واحد</th>
                <th>حداقل موجودی</th>
                <th>تامین‌کننده</th>
                <th>موجودی فعلی</th>
                <th>مورد نیاز</th>
                <th>عملیات</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in inventory" :key="item.id">
                <td>{{ item.inventory_code }}</td>
                <td>{{ item.item_name }}</td>
                <td>{{ item.unit }}</td>
                <td>{{ item.min_inventory }}</td>
                <td>{{ item.supplier }}</td>
                <td>{{ item.current_inventory }}</td>
                <td>{{ item.required }}</td>
                <td>
                  <button class="btn btn-sm btn-warning me-2" @click="editItem(item)">ویرایش</button>
                  <button class="btn btn-sm btn-danger" @click="deleteItem(item.id)">حذف</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'Inventory',
  data() {
    return {
      inventory: [],
      showForm: false,
      editingItem: null,
      form: {
        inventory_code: '',
        item_name: '',
        unit: '',
        min_inventory: 0,
        supplier: '',
        current_inventory: 0,
        required: 0,
        notes: ''
      }
    }
  },
  async mounted() {
    await this.fetchInventory()
  },
  methods: {
    async fetchInventory() {
      try {
        const response = await axios.get('/api/inventory')
        this.inventory = response.data
      } catch (error) {
        console.error('Error fetching inventory:', error)
      }
    },
    async saveItem() {
      try {
        if (this.editingItem) {
          await axios.put(`/api/inventory/${this.editingItem.id}`, this.form)
        } else {
          await axios.post('/api/inventory', this.form)
        }
        this.cancelEdit()
        await this.fetchInventory()
      } catch (error) {
        console.error('Error saving item:', error)
      }
    },
    editItem(item) {
      this.editingItem = item
      this.form = { ...item }
      this.showForm = true
    },
    async deleteItem(id) {
      if (confirm('آیا مطمئن هستید که می‌خواهید این کالا را حذف کنید؟')) {
        try {
          await axios.delete(`/api/inventory/${id}`)
          await this.fetchInventory()
        } catch (error) {
          console.error('Error deleting item:', error)
        }
      }
    },
    cancelEdit() {
      this.editingItem = null
      this.form = {
        inventory_code: '',
        item_name: '',
        unit: '',
        min_inventory: 0,
        supplier: '',
        current_inventory: 0,
        required: 0,
        notes: ''
      }
      this.showForm = false
    }
  }
}
</script>

<style scoped>
.table-responsive {
  max-height: 600px;
  overflow-y: auto;
}
</style>
