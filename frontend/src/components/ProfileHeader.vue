<template>
  <header class="account-header">
    <div class="account-header-inner">
      <RouterLink class="account-brand" to="/dashboard" aria-label="Kids TP dashboard">
        <img src="/Logo.png" alt="" />
        <span>kids tp</span>
      </RouterLink>

      <nav class="account-desktop-nav" aria-label="Main navigation">
        <RouterLink to="/dashboard">Dashboard</RouterLink>
        <RouterLink to="/dashboard#children">Children</RouterLink>
        <RouterLink
          to="/activities"
          :class="{ 'account-nav-current': active === 'activities' }"
          :aria-current="active === 'activities' ? 'page' : undefined"
        >
          Activities
        </RouterLink>
        <span aria-disabled="true">History</span>
      </nav>

      <div class="account-header-details">
        <span class="account-date">
          <span aria-hidden="true"></span>
          {{ todayLabel }}
        </span>
        <span class="account-bell" aria-label="Notifications">
          <v-icon icon="mdi-bell-outline" />
        </span>
        <RouterLink
          v-if="profile"
          class="account-header-user"
          to="/profile"
          aria-label="Open profile"
        >
          <span class="account-header-initials">{{ initials(profile.name) }}</span>
          <span>
            <strong>{{ profile.name }}</strong>
            <small>{{ profile.family?.family_name || 'Family account' }}</small>
          </span>
        </RouterLink>
      </div>

      <div class="account-mobile-header">
        <RouterLink class="account-brand" to="/dashboard" aria-label="Kids TP dashboard">
          <img src="/Logo.png" alt="" />
          <span>kids tp</span>
        </RouterLink>
        <span class="account-bell" aria-label="Notifications">
          <v-icon icon="mdi-bell-outline" />
        </span>
      </div>
    </div>
  </header>
</template>

<script setup>
const { profile, active } = defineProps({
  profile: {
    type: Object,
    default: null,
  },
  active: {
    type: String,
    default: '',
  },
})

const todayLabel = new Intl.DateTimeFormat(undefined, {
  weekday: 'long',
  day: 'numeric',
  month: 'long',
  year: 'numeric',
}).format(new Date())

function initials(name) {
  return (name || 'P')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
}
</script>

<style scoped>
.account-header {
  border-bottom: 1px solid #e6e9df;
  background: #fff;
}

.account-header-inner {
  display: flex;
  align-items: center;
  gap: 30px;
  width: min(100%, 1250px);
  min-height: 74px;
  margin: 0 auto;
  padding: 0 24px;
}

.account-brand {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 6px;
  color: #4b5632;
  font-size: 19px;
  font-weight: 900;
  letter-spacing: -0.8px;
  text-decoration: none;
}

.account-brand img {
  display: block;
  width: 42px;
  height: 36px;
  object-fit: contain;
}

.account-desktop-nav {
  display: flex;
  align-items: center;
  gap: clamp(14px, 2.2vw, 30px);
  margin-left: auto;
}

.account-desktop-nav a,
.account-desktop-nav span {
  padding: 27px 0 24px;
  border-bottom: 3px solid transparent;
  color: #6d7565;
  font-size: var(--text-small);
  text-decoration: none;
  white-space: nowrap;
}

.account-desktop-nav a:hover {
  border-bottom-color: #667342;
  color: #38432c;
}

.account-desktop-nav .account-nav-current {
  border-bottom-color: #667342;
  color: #38432c;
}

.account-desktop-nav [aria-disabled='true'] {
  color: #9da394;
}

.account-header-details {
  display: flex;
  align-items: center;
  gap: 16px;
}

.account-date {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  background: #f1f4e9;
  color: #70785f;
  font-size: var(--text-caption);
  white-space: nowrap;
}

.account-date > span {
  width: 7px;
  height: 7px;
  flex: 0 0 auto;
  border-radius: 50%;
  background: #98b449;
}

.account-bell {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  color: #455333;
}

.account-header-user {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  color: inherit;
  text-decoration: none;
}

.account-header-initials {
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  flex: 0 0 auto;
  border-radius: 50%;
  background: #4b5632;
  color: #fff;
  font-size: var(--text-caption);
  font-weight: 800;
}

.account-header-user > span:last-child {
  display: grid;
  gap: 2px;
  min-width: 0;
}

.account-header-user strong,
.account-header-user small {
  overflow: hidden;
  max-width: 130px;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.account-header-user strong {
  color: #37402d;
  font-size: var(--text-caption);
}

.account-header-user small {
  color: #828979;
  font-size: 11px;
}

.account-mobile-header {
  display: none;
}

@media (max-width: 1050px) and (min-width: 761px) {
  .account-header-inner {
    gap: 16px;
    padding-right: 16px;
    padding-left: 16px;
  }

  .account-desktop-nav {
    gap: 12px;
  }

  .account-desktop-nav a,
  .account-desktop-nav span {
    font-size: var(--text-caption);
  }

  .account-header-details {
    gap: 8px;
  }

  .account-header-user small {
    display: none;
  }
}

@media (max-width: 760px) {
  .account-header-inner {
    min-height: 58px;
    padding: 10px 18px;
  }

  .account-header-inner > .account-brand,
  .account-desktop-nav,
  .account-header-details {
    display: none;
  }

  .account-mobile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
  }

  .account-mobile-header .account-brand {
    gap: 5px;
    font-size: 17px;
  }

  .account-mobile-header .account-brand img {
    width: 34px;
    height: 30px;
  }
}
</style>
