<template>
  <main class="screen" aria-label="Kids TP welcome">
    <div class="content">

      <!-- Welcome section -->
      <section class="hero" aria-label="Welcome">
        <h1 class="brand">kids tp</h1>

        <p class="tagline">
          good habits grow into brighter futures.
        </p>

        <img
          class="logo"
          src="/Logo.png"
          alt="Kids TP logo"
        />

        <section class="actions" aria-label="Get started">
          <RouterLink to="/register" class="start">
            GET STARTED
          </RouterLink>

          <p class="signin-line">
            Already have a space?
            <RouterLink to="/login" class="signin">
              Sign in
            </RouterLink>
          </p>
        </section>
      </section>

      <!-- How it works: desktop only -->
      <section class="how" aria-labelledby="how-title">
        <h2 id="how-title">How it works</h2>

        <p class="intro">
          A simple family routine that helps kids stay on track,
          earn rewards, and feel proud of their progress.
        </p>

        <div class="cards">
  <!-- Create routines -->
  <article class="card">
    <span class="icon" aria-hidden="true">
      <v-icon icon="mdi-calendar-check-outline" />
    </span>

    <h3>Create routines</h3>
    <p>
      Set up daily activities, chores, and habits that
      make sense for your family and your child's age.
    </p>
  </article>

  <!-- Earn reward points -->
  <article class="card">
    <span class="icon" aria-hidden="true">
      <v-icon icon="mdi-star-outline" />
    </span>

    <h3>Earn reward points</h3>
    <p>
      Kids collect points for completed activities,
      helping them see progress and stay motivated.
    </p>
  </article>

  <!-- Celebrate progress -->
  <article class="card">
    <span class="icon" aria-hidden="true">
      <v-icon icon="mdi-medal-outline" />
    </span>

    <h3>Celebrate progress</h3>
    <p>
      Use points to unlock rewards, praise effort,
      and make everyday routines feel fun and meaningful.
    </p>
  </article>
</div>

      </section>

    </div>
  </main>
</template>

<style scoped src="../assets/styles/home.css"></style>


