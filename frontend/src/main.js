
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

import './assets/styles/variables.css'
import './assets/styles/global.css'

import 'vuetify/styles'

import { createVuetify } from 'vuetify'
import { VApp, VIcon } from 'vuetify/components'
import { mdiSvgIconSet } from './mdiIcons.js'

const vuetify = createVuetify({
  components: { VApp, VIcon },
  icons: {
    defaultSet: 'mdi',
    sets: { mdi: mdiSvgIconSet },
  },
  theme: {
    defaultTheme: document.documentElement.dataset.theme === 'dark' ? 'kidsTpDark' : 'kidsTpLight',
    themes: {
      kidsTpLight: {
        dark: false,
        colors: {
          primary: '#075E54',
          secondary: '#075E54',
          background: '#FFFFFF',
          surface: '#FFFFFF',
        },
      },
      kidsTpDark: {
        dark: true,
        colors: {
          primary: '#3DDC97',
          secondary: '#1F8F6A',
          background: '#000000',
          surface: '#101714',
        },
      },
    },
  },
})

const app = createApp(App)

app.use(router)
app.use(vuetify)

app.mount('#app')
