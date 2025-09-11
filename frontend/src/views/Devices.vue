<template>
  <div>
    <h1>Devices</h1>
    <button class="btn btn-primary mb-3" @click="showAddForm = !showAddForm">Add Device</button>
    <form v-if="showAddForm" @submit.prevent="addDevice" class="mb-3">
      <div class="mb-3">
        <input type="text" v-model="newDevice.device_name" placeholder="Device Name" class="form-control" required>
      </div>
      <div class="mb-3">
        <input type="text" v-model="newDevice.device_code" placeholder="Device Code" class="form-control" required>
      </div>
      <div class="mb-3">
        <textarea v-model="newDevice.description" placeholder="Description" class="form-control"></textarea>
      </div>
      <div class="mb-3">
        <input type="text" v-model="newDevice.location" placeholder="Location" class="form-control">
      </div>
      <div class="mb-3">
        <select v-model="newDevice.status" class="form-control">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <button type="submit" class="btn btn-success">Add</button>
    </form>
    <table class="table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Code</th>
          <th>Description</th>
          <th>Location</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="device in devices" :key="device.id">
          <td>{{ device.device_name }}</td>
          <td>{{ device.device_code }}</td>
          <td>{{ device.description }}</td>
          <td>{{ device.location }}</td>
          <td>{{ device.status }}</td>
          <td>
            <button class="btn btn-sm btn-warning">Edit</button>
            <button class="btn btn-sm btn-danger">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'Devices',
  data() {
    return {
      devices: [],
      showAddForm: false,
      newDevice: {
        device_name: '',
        device_code: '',
        description: '',
        location: '',
        status: 'active'
      }
    }
  },
  async mounted() {
    await this.fetchDevices()
  },
  methods: {
    async fetchDevices() {
      try {
        const response = await axios.get('/api/devices')
        this.devices = response.data
      } catch (error) {
        console.error('Error fetching devices:', error)
      }
    },
    async addDevice() {
      try {
        await axios.post('/api/devices', this.newDevice)
        this.newDevice = { device_name: '', device_code: '', description: '', location: '', status: 'active' }
        this.showAddForm = false
        await this.fetchDevices()
      } catch (error) {
        console.error('Error adding device:', error)
      }
    }
  }
}
</script>

<style scoped>
/* Styles for Devices */
</style>
