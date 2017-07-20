import Vue from 'vue'
import App from './App.vue'
import ModelTable from './components/Model-table.vue';
import Event from './components/Event.js'

window.Event = new Event();

new Vue({
  el: '#app',
  components: { ModelTable },
  data: {
    msg: "test123"
  }
})