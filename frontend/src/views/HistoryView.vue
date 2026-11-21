<template>
  <main class="history-page account-page-main">
    <PagePrintDecoration />
    <div class="history-content">
      <header class="history-heading">
        <div>
          <RouterLink v-if="childName" class="history-back" :to="`/children/${route.query.child_id}`">
            ‹ {{ childName }}
          </RouterLink>
          <h1>Points History</h1>
          <p>Every point earned, deducted, or adjusted for your family.</p>
        </div>
      </header>

      <div v-if="loadError" class="history-message history-error" role="alert">
        <p>{{ loadError }}</p>
        <button type="button" @click="loadHistory">Try again</button>
      </div>
      <div v-else-if="isLoading" class="history-message" role="status">Loading points history…</div>
      <template v-else>
        <section v-if="childName" class="history-child-summary">
          <span class="history-avatar">{{ initials(childName) }}</span>
          <span><strong>{{ childName }}</strong><small>Recent point activity</small></span>
        </section>
        <div class="history-filters" aria-label="Filter point history">
          <button
            v-for="filter in filters"
            :key="filter.value"
            type="button"
            :class="{ 'history-filter-active': activeFilter === filter.value }"
            :aria-pressed="activeFilter === filter.value"
            @click="activeFilter = filter.value"
          >
            {{ filter.label }}
          </button>
        </div>
        <ul v-if="filteredEntries.length" class="history-list">
          <li v-for="entry in filteredEntries" :key="entry.id" class="history-entry">
            <span class="history-entry-icon" :class="`history-entry-${entry.type}`">
              <v-icon :icon="entryIcon(entry.type)" />
            </span>
            <span class="history-entry-copy">
              <strong>{{ entry.activity?.title || entry.remarks || typeLabel(entry.type) }}</strong>
              <small>{{ entry.child_name }}<template v-if="entry.activity?.category"> · {{ entry.activity.category }}</template></small>
              <small>{{ formatDate(entry.created_at) }}<template v-if="entry.recorded_by"> · by {{ entry.recorded_by }}</template></small>
            </span>
            <strong class="history-entry-amount" :class="{ 'history-amount-negative': entry.tp_amount < 0 }">
              {{ entry.tp_amount > 0 ? '+' : '' }}{{ entry.tp_amount }} TP
            </strong>
          </li>
        </ul>
        <div v-else class="history-empty">
          <span class="history-empty-icon"><v-icon icon="mdi-history" /></span>
          <h2>{{ entries.length ? 'No matching activity' : 'No points recorded yet' }}</h2>
          <p>{{ entries.length ? 'Try another filter.' : 'Award or deduct points from a child profile to see activity here.' }}</p>
        </div>
      </template>
    </div>
    <nav class="history-mobile-nav" aria-label="Main navigation">
      <RouterLink to="/dashboard"><v-icon icon="mdi-view-dashboard-outline" /><span>Home</span></RouterLink>
      <RouterLink to="/family"><v-icon icon="mdi-account-group-outline" /><span>Family</span></RouterLink>
      <RouterLink to="/activities"><v-icon icon="mdi-format-list-checks" /><span>Activities</span></RouterLink>
      <RouterLink class="history-nav-current" to="/history"><v-icon icon="mdi-history" /><span>History</span></RouterLink>
      <RouterLink to="/profile"><v-icon icon="mdi-account-outline" /><span>Profile</span></RouterLink>
    </nav>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PagePrintDecoration from '../components/PagePrintDecoration.vue'
import {
  ApiError,
  clearAuthToken,
  fetchChildren,
  fetchFamilyLedger,
  getCurrentUser,
} from '../services/auth.js'

const route = useRoute()
const router = useRouter()
const profile = ref(null)
const children = ref([])
const entries = ref([])
const isLoading = ref(true)
const loadError = ref('')
const activeFilter = ref('all')
const filters = [
  { value: 'all', label: 'All' },
  { value: 'earning', label: 'Earned' },
  { value: 'deduction', label: 'Deduction' },
  { value: 'bonus', label: 'Bonus' },
  { value: 'adjustment', label: 'Adjustment' },
]
const childName = computed(() =>
  children.value.find((child) => String(child.id) === String(route.query.child_id))?.name || '',
)
const filteredEntries = computed(() =>
  activeFilter.value === 'all'
    ? entries.value
    : entries.value.filter((entry) => entry.type === activeFilter.value),
)

function initials(name) {
  return name.split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase()
}

function entryIcon(type) {
  return {
    earning: 'mdi-plus',
    deduction: 'mdi-minus',
    bonus: 'mdi-gift-outline',
    adjustment: 'mdi-swap-vertical',
  }[type] || 'mdi-history'
}

function typeLabel(type) {
  return type.charAt(0).toUpperCase() + type.slice(1)
}

function formatDate(value) {
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? 'Date unavailable'
    : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}

async function loadHistory() {
  isLoading.value = true
  loadError.value = ''
  try {
    const [user, result, childData] = await Promise.all([
      getCurrentUser(),
      fetchFamilyLedger({ childId: route.query.child_id }),
      fetchChildren(),
    ])
    profile.value = user
    entries.value = result.ledger
    children.value = childData.children
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      clearAuthToken()
      await router.replace({ name: 'login' })
      return
    }
    loadError.value = error instanceof Error ? error.message : 'Unable to load points history.'
  } finally {
    isLoading.value = false
  }
}

watch(() => route.query.child_id, loadHistory)
onMounted(loadHistory)
</script>

<style scoped>
.history-page{position:relative;isolation:isolate;min-height:100vh;min-height:100dvh;overflow:hidden;background:#f7f9f2;color:#3e472f}
.history-content{width:min(100%,1040px);min-height:calc(100vh - 128px);margin:0 auto;padding:24px}
.history-heading{display:flex;justify-content:space-between;margin-bottom:18px}
.history-heading h1{margin:0;color:#39432c;font-size:var(--text-heading)}
.history-heading p{margin:5px 0 0;color:#7a816f;font-size:var(--text-small)}
.history-back{display:inline-block;margin-bottom:8px;color:#60734a;font-size:var(--text-small);font-weight:700;text-decoration:none}
.history-child-summary{display:flex;align-items:center;gap:10px;margin-bottom:13px;padding:12px;border:1px solid #edf0e7;border-radius:13px;background:#fff}
.history-avatar,.history-entry-icon,.history-empty-icon{display:grid;place-items:center;flex:0 0 auto;width:38px;height:38px;border-radius:50%;background:#e5efce;color:#52643a;font-weight:800}
.history-child-summary>span:last-child{display:grid;gap:3px}.history-child-summary small{color:#828979;font-size:var(--text-caption)}
.history-filters{display:flex;gap:8px;overflow-x:auto;margin:0 0 12px;padding:2px 0 5px}
.history-filters button{flex:0 0 auto;min-height:var(--button-height);padding:0 var(--button-padding-inline);border:1px solid #e6eadf;border-radius:999px;background:#fff;color:#626d50;font:inherit;font-size:var(--text-small);cursor:pointer}
.history-filters .history-filter-active{border-color:#52643a;background:#52643a;color:white}
.history-list{display:grid;gap:8px;margin:0;padding:0;list-style:none}
.history-entry{display:flex;align-items:center;gap:11px;min-width:0;padding:11px 13px;border:1px solid #edf0e7;border-radius:13px;background:#fff}
.history-entry-icon{width:34px;height:34px}.history-entry-deduction{background:#ffefec;color:#df786e}.history-entry-bonus{background:#fff1cf;color:#bb8b21}.history-entry-adjustment{background:#eaf3f7;color:#5591ac}
.history-entry-copy{display:grid;gap:3px;min-width:0;margin-right:auto}.history-entry-copy strong{overflow:hidden;color:#3e472f;font-size:var(--text-small);text-overflow:ellipsis;white-space:nowrap}.history-entry-copy small{color:#818878;font-size:var(--text-caption)}
.history-entry-amount{flex:0 0 auto;color:#65a25b;font-size:var(--text-small);white-space:nowrap}.history-entry-amount.history-amount-negative{color:#d8796c}
.history-message,.history-empty{padding:24px;border:1px solid #edf0e7;border-radius:14px;background:#fff;color:#657052;text-align:center}.history-message p{margin:0 0 10px}.history-message button{padding:7px 12px;border:0;border-radius:999px;background:#f5c766;font-weight:700}
.history-empty{display:grid;justify-items:center;padding:44px 20px}.history-empty-icon{width:48px;height:48px}.history-empty h2{margin:12px 0 4px;font-size:var(--text-large)}.history-empty p{margin:0;color:#828979;font-size:var(--text-small)}
.history-print,.history-mobile-nav{display:none}
@media(max-width:760px){.history-content{min-height:calc(100dvh - 118px);padding:19px 15px 95px}.history-heading h1{font-size:20px}.history-entry{gap:8px;padding:10px}.history-entry-copy strong{font-size:12px}.history-entry-copy small{font-size:10px}.history-entry-amount{font-size:11px}.history-print{position:fixed;bottom:40px;left:-85px;z-index:0;display:block;width:220px;opacity:.95;pointer-events:none}.history-print img{display:block;width:100%;height:auto}.history-content{position:relative;z-index:1}.history-mobile-nav{position:fixed;right:0;bottom:0;left:0;z-index:5;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));min-height:60px;padding:5px 5px max(5px,env(safe-area-inset-bottom));border-top:1px solid #e3e7dd;background:rgba(255,255,255,.97)}.history-mobile-nav a{display:grid;justify-items:center;align-content:center;gap:2px;min-width:0;border-radius:9px;color:#616a57;text-decoration:none}.history-mobile-nav .v-icon{font-size:19px}.history-mobile-nav span{font-size:var(--text-caption)}.history-mobile-nav .history-nav-current{background:#dceda8;color:#3f4a2d}}
@media(max-width:760px){.history-heading h1{font-size:var(--text-heading)}.history-entry-copy strong{font-size:var(--text-small)}.history-entry-copy small{font-size:var(--text-caption)}.history-entry-amount{font-size:var(--text-small)}}
</style>
