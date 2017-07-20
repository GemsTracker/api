import axios from 'axios'
import _ from 'lodash'

export default class TableModel
{
    constructor(modelName)
    {
        this.modelName = modelName;

        console.log('HELLO! ' + this.modelName);

        this.currentPage = 1;
        this.originalData = {};
        this.currentPageData = {};
        this.structure = {};

        this.paginationNumbers = 5;
        this.itemsPerPage = 5;

        this.originalItemsPerPage = this.itemsPerPage

        this.totalRows = 0;

        this.loadModel();


    }

    /*set data(originalData)
    {
        if (Object.keys(this.originalData).length === 0 && this.originalData.constructor === Object) {
            this.itemsPerPage = originalData.length;
        }

        this.originalData = originalData;
    }*/

    hasColumn(column)
    {
        return _.has(this.structure, column);
    }

    getLabel(column)
    {
        if (_.has(this.structure, column) && _.has(this.structure[column], 'label')) {
            return this.structure[column].label;
        }

        return undefined;
    }

    getColumnData(pageNumber, column, index)
    {
        if (_.has(this.originalData[pageNumber], index) && _.has(this.originalData[pageNumber][index], column)) {
            let value = this.originalData[pageNumber][index][column];

            if (_.has(this.structure[column], 'multiOptions')) {
                let multiOptions = this.structure[column].multiOptions;

                if (_.has(multiOptions, value)) {
                    return this.structure[column].multiOptions[value];
                }
            } 
            
            return value;
        }

        return undefined;
    }

    getColumnResult(columnName, value)
    {
        if (_.has(this.structure[columnName], 'multiOptions')) {
            let multiOptions = this.structure[columnName].multiOptions;

            if (_.has(multiOptions, value)) {
                return this.structure[columnName].multiOptions[value];
            }
        } 
            
        return value;
    }

    get labeledColumns()
    {
        return _.filter(this.originalData, 'label');
    }

    set page(pageNumber)
    {
        this.currentPage = pageNumber;
        if (_.has(this.originalData, pageNumber)) {
            this.currentPageData = this.originalData[pageNumber];
        } else {
            this.loadPage(pageNumber);    
        }
    }

    get itemCount()
    {
        return this.itemsPerPage;
    }

    set itemCount(itemNumber)
    {
        console.log('test');
        this.itemsPerPage = itemNumber;
        if (this.itemsPerPage > this.totalRows) {
            this.itemsPerPage = this.totalRows;
        }

        this.redistributeData();
        this.page = this.currentPage;
    }

    getNumberOfPages()
    {
        if (this.totalRows !== undefined && this.itemsPerPage !== undefined) {
            return Math.ceil(this.totalRows / this.itemsPerPage);
        }
    }

    /*extractHeaderLinks(linkString)
    {
        /*let linkArray = linkString.split(',<');
        let tempLinks = {};
        for (let i=0; i < linkArray.length; i++) {
            if (linkArray[i].charAt(0) === '<') {
                linkArray[i] = linkArray[i].substring(1);
            }

            let url = linkArray[i].split('>; rel=');
            tempLinks[url[1]] = url[0];
        }

        if (_.has(tempLinks, 'last')) {
            let lastLinkPageArray = tempLinks.last.split('page=');
            let numberOfPagesArray = lastLinkPageArray[1].split('&');
            this.numberOfPages = numberOfPagesArray[0];
        } else {
            this.numberOfPages = this.currentPage;
        }



        let links = {
            numbers: {}
        };
        if (this.numberOfPages > 1) {            

            links.first = 1
            links.prev = this.currentPage -1;
            if (links.prev < 1) {
                links.prev = 1;
            }

            let firstNumber = this.currentPage - this.paginationNumbers;
            if (firstNumber < 1) {
                firstNumber = 1;
            }

            let lastNumber = firstNumber + this.paginationNumbers - 1;
            if (lastNumber > this.numberOfPages) {
                lastNumber = this.numberOfPages;
            }

            for(let i=firstNumber; i<=lastNumber; i++) {
                console.log(i);
                links.numbers[i] = i;
            }

            
            links.next = this.currentPage +1;
            if (links.next > this.numberOfPages) {
                links.next = this.numberOfPages;
            }
            
            links.last = this.numberOfPages;

        }

        return links;
    }*/

    loadMore()
    {
        console.log('loading more...');
        
        if (this.itemsPerPage < this.totalRows) {
            this.itemsPerPage += this.originalItemsPerPage;

            this.redistributeData();

            this.page = this.currentPage;

            //this.loadPage(this.currentPage);
        }
    }

    loadModel()
    {
        axios.get('https://expressive.dev/'+this.modelName+'/structure')
            .then(response => {
                console.log(response.data);
                this.structure = response.data;
            }).catch(e => {
                console.log(e);
            });
        
        this.loadPage();

        console.log('LOADING');
    }

    loadPage(page=1)
    {
        console.log('loading page: '+page);

        axios.get('https://expressive.dev/'+this.modelName+'?per_page='+this.itemsPerPage+'&page='+page)
            .then(response => {
                console.log(response);
                console.log(this.originalData);
                this.originalData[page] = response.data;
                console.log(this.originalData);
                this.currentPageData = this.originalData[page];
                if (response.headers['x-total-count'] !== undefined) {
                    this.totalRows = response.headers['x-total-count'];
                    this.numberOfPages = this.getNumberOfPages();
                }
                /*if (response.headers.link !== undefined) {

                    this.links = this.extractHeaderLinks(response.headers.link);
                    //console.log('LINKS FOUND:');
                    //console.log(this.links);
                }*/

                console.log(this.originalData);
                console.log(this.itemsPerPage);
                //console.log(this.getNumberOfPages());
                console.log(this.totalRows);
            }).catch(e => {
                console.log(e);
            });
    }

    redistributeData()
    {
        //let originalDataKeys = _.take(this.originalData);
        //let previousItemsPerPage = _.take(this.originalData).length;
        let previousItemsPerPage = this.originalItemsPerPage;
        if (_.has(this.originalData, this.currentPage)) {
            previousItemsPerPage = this.originalData[this.currentPage].length;
        }



        /*let loadedPages = _.keys(this.originalData);

        _.each(this.originalData, function(value, key) {

            console.log(key);
            console.log(value);
        });*/

        //_.each(loadedPages)

        let numberOfPages = this.getNumberOfPages();
        let newData = {};
        let blocksPerPage = this.itemsPerPage / previousItemsPerPage;
        
        if (blocksPerPage > 1) {
            blocksPerPage = Math.floor(blocksPerPage);
            console.log('NYAN!!!' + blocksPerPage);

            
            for (let page=1; page<=numberOfPages;page++) {
                let newPageData = [];
                for (let block=1;block<=blocksPerPage;block++) {
                    let oldPageNumber = page*block;

                    if (_.has(this.originalData, oldPageNumber)) {
                        _.each(this.originalData[oldPageNumber], function(value) {
                            newPageData.push(value);
                        });
                    } else {
                        newPageData = false;
                    }
                }

                if (newPageData) {
                    newData[page] = newPageData;
                }
            }
        } else {
            // When the number of items on a page is smaller than the previous entry
            // 
            /*_.each(this.originalData, function(pageData, oldPage){
                let newPage = oldPage*(1/blocksPerPage);
                let i=0;
                _.each(pageData, function(row) {
                    if (i = this.itemsPerPage) {
                        newPage++;
                        i=0;
                    }
                    newData[newPage].push = row;
                    i++;
                });

                let newPageNumber = Math.ceil(oldPage*blocksPerPage);


            });*/
            
        }

        this.originalData = newData;

    }
}