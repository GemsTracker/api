<template>
    <div>
        <nav v-if="numberOfPages > 1" class="pagination is-right">
            <ul class="pagination-list">
                <li>
                    <a @click="currentPage = first" class="pagination-previous" :disabled="currentPage == 1">First</a>
                </li>
                <li>
                    <a @click="currentPage = prev" class="pagination-previous" :disabled="currentPage == 1">Previous</a>
                </li>
                <li v-if="firstNumber > 1">
                    <span class="pagination-ellipsis">&hellip;</span>
                </li>
                <li v-for="pageNumber in numbers">
                    <a @click="currentPage = pageNumber" :class="{ 'is-current': currentPage == pageNumber }"class="pagination-link">{{pageNumber}}</a>
                </li>
                <li v-if="lastNumber < numberOfPages">
                    <span class="pagination-ellipsis">&hellip;</span>
                </li>
                <li>
                    <a @click="currentPage = next" class="pagination-previous" :disabled="currentPage == numberOfPages">Next</a>
                </li>
                <li>
                    <a @click="currentPage = last" class="pagination-previous" :disabled="currentPage == numberOfPages">Last</a>   
                </li>

                <!-- <a v-for="(page, label) in model.links" :disabled="page == currentPage" class="pagination-link">{{label}}</a> -->
            </ul>
        </nav>
        <div class="pagination-description">
            {{firstItemOnPage}} to {{lastItemOnPage}} of {{totalRows}}
        </div>
    </div>
</template>
<script>
    
    export default {
        props: {
            page: {
                type: null,
                default: 0,
            },
            itemsPerPage: {
                type: null,
                default: 0,
            },
            paginationNumbers: {
                type: null,
                default: 5,
            },
            totalRows: {
                type: null,
                default: 0,
            }
        },
        data: () => ({
            internalPage: false,
            first: 1,
            firstNumber: 1,
            lastNumber: 1
        }),
        computed: {
            numberOfPages: function() {
                return Math.ceil(this.totalRows / this.itemsPerPage);
            },

            next: function() {
                let next = this.currentPage+1;
                if (next > this.numberOfPages) {
                    next = this.numberOfPages;
                }
                return next;
            },
            last: function() {
                return this.totalRows;
            },
            prev: function() {
                let prev = this.currentPage-1;
                if (prev < 1) {
                    prev = 1;
                }
                return prev;
            },
            numbers: function() {
                console.log(this.currentPage);
                let numbers = [];


                let numberOfItemsBeforeAfter = Math.floor(this.paginationNumbers / 2);
                let firstNumber = this.currentPage - numberOfItemsBeforeAfter;

                if (firstNumber < 1) {
                    firstNumber = 1;
                }
                console.log('firstNumber' + firstNumber);

                let lastNumber = firstNumber + this.paginationNumbers -1;
                if (lastNumber > this.numberOfPages) {
                    lastNumber = this.numberOfPages;
                    firstNumber = lastNumber - this.paginationNumbers+1;
                }

                if (firstNumber < 1) {
                    firstNumber = 1;
                }


                console.log('lastNumber' +lastNumber);

                for(let i=firstNumber; i<=lastNumber; i++) {
                    numbers.push(i);
                }


                this.firstNumber = firstNumber;
                this.lastNumber = lastNumber;
                return numbers;
            },
            currentPage: {
                get: function() {
                    if (this.internalPage === false) {
                        return this.page;    
                    }
                    return this.internalPage;
                    
                },
                set: function(pageNumber) {
                    this.internalPage = pageNumber;
                    Event.emit('pageChange', pageNumber);
                }
            },
            firstItemOnPage: function() {
                return (this.currentPage - 1) * this.itemsPerPage + 1;
            },
            lastItemOnPage: function() {
                let lastItem = this.itemsPerPage;
                if (lastItem < this.totalRows) {
                    return lastItem;
                }
                return this.totalRows;
            }

        }
    }
</script>
<style lang="scss">
    .pagination-description {
        text-align: right;
    }
</style>