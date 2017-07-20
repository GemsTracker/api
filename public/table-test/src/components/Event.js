import Vue from 'vue'
export default class Event
{
    constructor() {
        this.vue = new Vue();
    }

    emit(event, data = null)
    {
        this.vue.$emit(event, data)
    }

    on(event, callback)
    {
        this.vue.$on(event, callback);
    }
}