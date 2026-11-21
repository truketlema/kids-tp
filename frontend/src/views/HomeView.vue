<template>
  <main class="screen" aria-label="Kids TP welcome">
    <PublicHeader
      prompt="Already a member?"
      link-text="Sign in"
      link-to="/login"
    />

    <section class="hero" aria-label="Welcome">
      <div class="hero-brand">
        <h1 class="hero-title">kids tp</h1>
        <FamilyArtwork />
      </div>

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
