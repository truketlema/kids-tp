<template>
  <main class="screen" aria-label="Kids TP welcome">
    <header class="topbar">
      <RouterLink to="/" class="brand-lockup" aria-label="Kids TP home">
        <span class="brand-mark" aria-hidden="true" />
        <span class="brand-name">kids<span> tp</span></span>
      </RouterLink>

      <p class="member-link">
        Already a member?
        <RouterLink to="/login" class="signin">
          Sign in
          <span class="small-arrow" aria-hidden="true">↗</span>
        </RouterLink>
      </p>
    </header>

    <section class="hero" aria-label="Welcome">
      <div class="family-orbit">
        <span class="orbit orbit-outer" aria-hidden="true" />
        <span class="orbit orbit-inner" aria-hidden="true" />
        <span class="orbit-glow" aria-hidden="true" />
        <span class="family-mark" role="img" aria-label="Kids TP family" />
        <span class="sparkle sparkle-one" aria-hidden="true" />
        <span class="sparkle sparkle-two" aria-hidden="true" />
      </div>

      <RouterLink to="/register" class="start">
        Let's get started
        <span class="action-arrow" aria-hidden="true">↗</span>
      </RouterLink>
    </section>
  </main>
</template>

<style scoped src="../assets/styles/home.css"></style>
