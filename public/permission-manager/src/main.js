import Vue from 'vue'
import PermissionManager from './PermissionManager.vue'
import store from './store'
import router from './router'

import './assets/styles/main.scss'

Vue.config.productionTip = false

Vue.prototype.$apiUrl = window.apiUrl;

new Vue({
  store,
  router,
  render: h => h(PermissionManager)
}).$mount('#app');



/*const app = new Vue({
  el: '#app',
  store,
  router,
  components: { PermissionManager }
})*/
