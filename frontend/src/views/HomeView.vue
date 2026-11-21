<template>
  <main class="screen" aria-label="Kids TP welcome">
    <PublicHeader
      prompt="Already a member?"
      link-text="Sign in"
      link-to="/login"
    />

    <section class="hero" aria-label="Welcome">
      <FamilyArtwork />

      <RouterLink to="/register" class="start">
        Let's get started
        <span class="action-arrow" aria-hidden="true">↗</span>
      </RouterLink>
    </section>
  </main>
</template>

<script setup>
import FamilyArtwork from '../components/FamilyArtwork.vue'
import PublicHeader from '../components/PublicHeader.vue'
</script>

<style scoped src="../assets/styles/home.css"></style>
