import { computed, ref } from 'vue'

export const THEME_STORAGE_KEY = 'kids-tp-theme'

const theme = ref(document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light')

export function applyTheme(nextTheme) {
  const value = nextTheme === 'dark' ? 'dark' : 'light'
  theme.value = value
  document.documentElement.dataset.theme = value
  localStorage.setItem(THEME_STORAGE_KEY, value)
}

export function useTheme() {
  const isDark = computed(() => theme.value === 'dark')

  function toggleTheme() {
    applyTheme(isDark.value ? 'light' : 'dark')
  }

  return { theme, isDark, toggleTheme }
}
