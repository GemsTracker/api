<template>
    <div>
        <label>Name: <input type="text" v-model="search"></label>
        {{search}}
    </div>
</template>
<script>
    export default {
        props: {
            defaultSearchFields: {
                type: String
            }
        },
        created() {
            this.searchFields = JSON.parse(this.defaultSearchFields);
            console.log(this.searchFields);
        },
        data: () => ({
            search: ""
        })
    }

</script>