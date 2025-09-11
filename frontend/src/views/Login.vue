<template>
  <div>
    <h1>Login</h1>
    <form @submit.prevent="login">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" v-model="username" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" v-model="password" required>
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
    </form>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'Login',
  data() {
    return {
      username: '',
      password: ''
    }
  },
  methods: {
    async login() {
      try {
        const response = await axios.post('/api/login.php', {
          username: this.username,
          password: this.password
        })
        // Handle login success
        this.$router.push('/dashboard')
      } catch (error) {
        console.error('Login failed:', error)
      }
    }
  }
}
</script>

<style scoped>
/* Styles for Login */
</style>
