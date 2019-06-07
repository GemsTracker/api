import Vue from 'vue'
import Router from 'vue-router'
import All from './views/All.vue'
import EditRole from './views/EditRole'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'all',
      component: All
    },
    {
      path: '/role/:roleId',
      name: 'edit-role',
      component: EditRole
    }
  ]
})
