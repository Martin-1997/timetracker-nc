import '../../css/all.css'
import '../../css/style.css'
import '../../css/vue-app.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router/index.js'

const app = createApp(App)
app.use(router)
app.mount('#timetracker-app')
