<template>
    <div>
        <model-table target-model="organizations"></model-table>
    </div>
</template>

<script>
import ModelTable from './components/Model-table.vue';
export default {
  name: 'app',
  components: { ModelTable },
  data () {
    return {
      msg: 'Welcome to Your Vue.js App',

    }
  }
}
</script>

<style lang="scss">

</style>
