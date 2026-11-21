
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

import './assets/styles/variables.css'
import './assets/styles/global.css'

import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'

import { createVuetify } from 'vuetify'
import { VApp, VIcon } from 'vuetify/components'

const vuetify = createVuetify({
  components: { VApp, VIcon },
  theme: {
    defaultTheme: 'kidsTpTheme',
    themes: {
      kidsTpTheme: {
        dark: false,
        colors: {
          primary: '#4B5531',
          secondary: '#D5F58C',
          background: '#F8F9F5',
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
