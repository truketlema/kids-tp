<template>
  <main aria-label="Signing out"></main>
</template>

<script setup>
import { clearAuthToken } from '../services/auth.js'

clearAuthToken()
sessionStorage.removeItem('kids-tp:child-setup-draft')

window.location.replace('/')
</script>
