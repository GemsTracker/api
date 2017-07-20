<template>
    <div>
        <div>
            
        </div>
        <table class="table is-bordered is-striped is-narrow">
            <thead>
                <tr>
                    <th v-for="column in columns">
                        <template v-if="column instanceof Array">                        
                            <template v-for="subcolumn in column">
                                <template v-if="model.hasColumn(subcolumn)">{{model.getLabel(subcolumn)}}<br></template>
                            </template>
                        </template>
                        <template v-else>
                            <template v-if="model.hasColumn(column)">{{model.getLabel(column)}}</template>                            
                        </template>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in model.currentPageData">
                    <td v-for="column in columns">
                        <template v-if="column instanceof Array">                        
                            <template v-for="subcolumn in column">
                                {{model.getColumnResult(subcolumn, row[subcolumn])}}<br>
                            </template>
                        </template>
                        <template v-else>
                            {{model.getColumnResult(row[column])}}
                        </template>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th v-for="column in columns">
                        <template v-if="column instanceof Array">                        
                            <template v-for="subcolumn in column">
                                <template v-if="model.hasColumn(subcolumn)">{{model.getLabel(subcolumn)}}<br></template>
                            </template>
                        </template>
                        <template v-else>
                            <template v-if="model.hasColumn(column)">{{model.getLabel(column)}}</template>                            
                        </template>
                    </th>
                </tr>
                <tr>
                    <td :colspan="columns.length">
                        <table-pagination :totalRows="model.totalRows" :itemsPerPage="model.itemsPerPage" :page="model.currentPage" :paginationNumbers="model.paginationNumbers"></table-pagination>
                    </td>
                </tr>
                <tr>
                    
                </tr>
                <tr>
                    <td :colspan="columns.length">
                        <label for="itemsPerPage">Items per page:</label> <input id="itemsPerPage" type="number" class="" size="3" :value="model.itemsPerPage" @change="updateItemsPerPage">
                    </td>
                </tr>
                <tr v-if="model.itemsPerPage < model.totalRows" class="load-more">
                    <td :colspan="columns.length"><button @click="loadMore" class="button">Load more..</button></td>
                </tr>
            </tfoot>
        </table>
    </div>    
</template>
<script>
    import axios from 'axios'
    import _ from 'lodash'
    //import ModelData from './ModelData'
    import TablePagination from './Table-pagination.vue'
    import ModelTableSearch from './Model-table-search.vue'
    import TableModel from './TableModel'
    export default {
        props: {
            targetModel: {
                type: String,
                required: true
            },
            defaultColumns: {
                type: String
            }
        },
        components: {
            TablePagination, ModelTableSearch
        },
        data: () => ({
            currentPage: 1,
            model: {},
            structure: []            
        }),
        created() {
            
            this.columns = JSON.parse(this.defaultColumns);

            this.model = new TableModel(this.targetModel);

            let model = this.model;
            Event.on('pageChange', function(pageNumber) {
                console.log('Changing page to: '+pageNumber);
                model.page = pageNumber;
            });

        },
        methods: {
            loadMore: function (event) {
                this.model.loadMore();
            },
            updateItemsPerPage: function (event) {
                console.log(event);
                this.model.redistributeData();
                this.model.page = this.model.currentPage;
            }
        }
    }
</script>
<style lang="scss">
.table tfoot .load-more {
    .button {
        width: 100%;
        /*background: rgb(20,167,157);
        color: white;*/
        font-weight: bold;
    }
}
</style>