
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
    defaultTheme: 'kidsTpTheme',
    themes: {
      kidsTpTheme: {
        dark: false,
        colors: {
          primary: '#075E54',
          secondary: '#075E54',
          background: '#F0F2F5',
          surface: '#FFFFFF',
        },
      },
    },
  },
})

const app = createApp(App)

app.use(router)
app.use(vuetify)

app.mount('#app')
