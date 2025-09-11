<template>
  <div id="app">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
      <div class="container-fluid">
        <router-link class="navbar-brand" to="/">Corporate Portal</router-link>
        <div class="navbar-nav">
          <router-link class="nav-link" to="/dashboard">Dashboard</router-link>
          <router-link class="nav-link" to="/users">Users</router-link>
          <router-link class="nav-link" to="/inventory">Inventory</router-link>
          <!-- Add more links as needed -->
        </div>
      </div>
    </nav>
    <main class="container mt-4">
      <router-view />
    </main>
    <footer class="footer mt-auto py-3 bg-light">
      <div class="container">
        <span class="text-muted">Mahdi Alizadeh - M.alizadeh7@live.com - Alizadehx.ir</span>
      </div>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'App'
}
</script>

<style>
/* Custom styles */
</style>
