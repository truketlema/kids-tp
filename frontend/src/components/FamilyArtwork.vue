<template>
  <div
    class="family-orbit"
    :class="`family-orbit--${variant}`"
    role="img"
    aria-label="Kids TP family"
  >
    <span class="orbit orbit-outer" aria-hidden="true" />
    <span class="orbit orbit-inner" aria-hidden="true" />
    <span class="orbit-glow" aria-hidden="true" />
    <span class="family-mark" aria-hidden="true" />
    <span class="sparkle sparkle-one" aria-hidden="true" />
    <span class="sparkle sparkle-two" aria-hidden="true" />
  </div>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'landing',
    validator: (value) => ['landing', 'auth'].includes(value),
  },
})
</script>

<style scoped>
.family-orbit {
  position: relative;
  display: grid;
  width: min(54vw, 420px);
  min-width: 260px;
  aspect-ratio: 1;
  place-items: center;
}

.orbit,
.orbit-glow {
  position: absolute;
  border-radius: 50%;
}

.orbit-outer {
  inset: 2% 5% 0 0;
  border: 2.5px solid rgba(7, 94, 84, 0.18);
  transform: rotate(-7deg);
  box-shadow:
    0 0 16px rgba(37, 211, 102, 0.22),
    10px 14px 30px rgba(7, 94, 84, 0.10);
}

.orbit-inner {
  inset: 8% 2% 7% 5%;
  border: 2.5px solid rgba(7, 94, 84, 0.16);
  transform: rotate(5deg);
  box-shadow:
    0 0 12px rgba(37, 211, 102, 0.18),
    8px 10px 24px rgba(7, 94, 84, 0.08);
}

.orbit-glow {
  inset: 9%;
  background: radial-gradient(
    ellipse 58% 56% at 20% 18%,
    rgba(215, 243, 195, 0.82) 0%,
    rgba(190, 239, 202, 0.58) 32%,
    rgba(205, 246, 220, 0.28) 58%,
    rgba(238, 252, 244, 0.10) 82%,
    rgba(255, 255, 255, 0) 100%
  );
  box-shadow: 14px 20px 30px rgba(7, 94, 84, 0.08);
}

.family-mark {
  position: relative;
  z-index: 1;
  display: block;
  width: 108%;
  aspect-ratio: 390 / 315;
  background: var(--color-primary);
  filter: drop-shadow(0 1px 1px rgba(41, 43, 32, 0.14));
  transform: translateY(2%);
  -webkit-mask: url('/logo.png') center / contain no-repeat;
  mask: url('/logo.png') center / contain no-repeat;
}

.sparkle {
  position: absolute;
  z-index: 2;
  width: 15px;
  height: 15px;
  background: var(--color-primary);
  clip-path: polygon(50% 0, 60% 40%, 100% 50%, 60% 60%, 50% 100%, 40% 60%, 0 50%, 40% 40%);
}

.sparkle-one {
  top: 20%;
  left: 18%;
  width: 18px;
  height: 18px;
  background: var(--color-primary);
}

.sparkle-two {
  top: 28%;
  right: 14%;
  background: var(--color-primary);
}

.family-orbit--auth {
  width: min(30vw, 320px);
  min-width: 220px;
}

html[data-theme='dark'] .orbit-outer {
  border-width: 3px;
  border-color: rgba(61, 220, 151, 0.4);
  box-shadow: 0 0 16px rgba(61, 220, 151, 0.28);
}

html[data-theme='dark'] .orbit-inner {
  border-width: 3px;
  border-color: rgba(61, 220, 151, 0.28);
  box-shadow: 0 0 12px rgba(61, 220, 151, 0.2);
}

html[data-theme='dark'] .orbit-glow {
  display: none;
}

@media (max-width: 600px) {
  .family-orbit {
    width: min(70vw, 300px);
    min-width: 0;
  }

  .family-orbit--auth {
    width: min(44vw, 180px);
  }
}

@media (min-width: 1200px) {
  .family-orbit {
    width: min(30vw, 420px);
  }
}

@media (max-width: 360px) {
  .family-orbit {
    width: min(70vw, 280px);
  }

  .family-orbit--auth {
    width: min(42vw, 150px);
  }
}

@media (min-width: 601px) and (max-height: 820px) {
  .family-orbit {
    width: min(48vh, 360px);
  }
}

@media (max-height: 600px) {
  .family-orbit {
    width: min(44vh, 300px);
    min-width: 200px;
  }

  .family-orbit--auth {
    width: min(25vh, 160px);
    min-width: 0;
  }
}
</style>
