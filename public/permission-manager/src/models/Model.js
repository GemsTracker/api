import Store from './Store';
import Validator from './Validator';

export default class Model extends Store
{
    constructor(name)
    {
        super(name);
        this.idField = null;
        this.idInUrl = true;
        this.filters = {};
        this.groupBy = null;
        this.loadingAll = null;
        this.loadingIds = {};
        this.storeOne = false;
        this.idInUrl = true;
        this.storeAllAsId = false;
        this.structureData = null;
        this.updating = false;
        this.inserting = false;

        this.validator = null;
        this.excludeFieldsFromValidation = null;

        this.name = name;

        /*this.store.registerModule('models', {
            namespaced: true,
        });*/

        /*this.register({
            state: {
                loading: false,
                models: [],
            },
            mutations,
            actions,
            getters
        });*/
    }

    afterLoad()
    {}

    addFilters(filters)
    {
        this.filters = Object.assign(this.filters, filters);
    }

    async all(refresh=false)
    {
        if (refresh ===  true || (this.isEmptyObject(this.state.data) && !this.state.loading && this.loadingAll === null)) {
            let loadData = this.getLoadData();
            loadData.filters = this.filters;
            this.loadingAll = this.dispatch('load', loadData)
            const data = await this.loadingAll;
            this.afterLoad();
            this.loadingAll = null;

            return data;
        }

        if (this.loadingAll != null) {
            return await this.loadingAll;
        }

        return this.state.data;
    }

    async findById(id, refresh=false)
    {
        if ((typeof id == 'number' || typeof id == 'string')) {
            //console.log('Find by ID call to ' + this.endpoint + '...' + id);
            if (refresh === true || ((this.isEmptyObject(this.state.data) || (this.state.data[id] === undefined && this.storeOne === false))
                && this.loadingIds[id] === undefined
            )) {
                let loadData = this.getLoadData();
                loadData.filters = this.filters;
                if (this.idField) {
                    loadData.filters[this.idField] = id;
                }
                if (this.idInUrl) {
                    loadData.endpoint += '/' + id;
                }
                loadData.id = id;

                this.loadingIds[id] = this.dispatch('load', loadData);
                const data = await this.loadingIds[id];
                delete this.loadingIds[id];
                if (!this.storeOne) {
                    if (!this.storeAllAsId) {
                        return data[id];
                    }
                }
                return data;
            }

            if (this.loadingIds[id] !== undefined) {
                const data = await this.loadingIds[id];
                if (!this.storeOne) {
                    if (!this.storeAllAsId) {
                        return data[id];
                    }
                    else { }

                }
                return data;
            }

            if (!this.storeOne) {
                return this.state.data[id];
            }
            return this.state.data;
        }
        return null;
    }

    getLoadData()
    {
        const store = this.store;
        let data = {
            apiUrl: store.state.apiBaseUrl,
            endpoint: this.endpoint,
            groupBy: this.groupBy,
            idField: this.idField,
            storeOne: this.storeOne,
            id: null
        };
        return data;
    }

    async structure()
    {
        if (this.state.structure === null) {
            const structurePromise = this.dispatch('structure', {
                apiUrl: this.store.state.apiBaseUrl,
                endpoint: this.endpoint
            });

            const structureData = await structurePromise;
        }
        return this.state.structure;
    }

    clear()
    {
        this.dispatch('clear', true);
    }

    async deleteById(id, data)
    {
        let deleteData = {
            apiUrl: this.store.state.apiBaseUrl,
            endpoint: this.endpoint + '/' + id,
            data: data,
        }
        this.updating = this.dispatch('delete', deleteData);

        return this.updating;
    }

    async insert(data)
    {
        let insertData = {
            apiUrl: this.store.state.apiBaseUrl,
            endpoint: this.endpoint,
            data: data,
        }
        this.inserting = this.dispatch('insert', insertData);

        return this.inserting;
    }

    isEmptyObject(obj) 
    {
        return obj !== null && obj !== undefined && Object.keys(obj).length === 0 && obj.constructor === Object;
    }

    async updateById(id, data)
    {
        let updateData = {
            apiUrl: this.store.state.apiBaseUrl,
            endpoint: this.endpoint + '/' + id,
            data: data,
        }
        this.updating = this.dispatch('update', updateData);

        return this.updating;
    }

    async validate(row, index=null)
    {
        if (this.validator === null) {
            const structure = await this.structure();
            console.log('TEST');
            console.log(this.name);
            console.log(structure);
            if (this.excludeFieldsFromValidation !== null) {
                for (const key in structure) {
                    if (this.excludeFieldsFromValidation.indexOf(key) !== -1) {
                        delete structure[key];
                    }
                }
            }       

            this.validator = new Validator(structure, this.idField);
        }

        if (this.validator.validate(row)) {

            this.dispatch('removeValidationErrors', {index});

            /*if (index === null) {
                this.validationErrors = null;
            } else {
                if (typeof this.validationErrors === 'object' && this.validationErrors.hasOwnProperty(index)) {
                    delete this.validationErrors[index];
                }
            }*/
            return true;
        }
        console.log('HELLO VALIDATION ERRORS');

        this.dispatch('addValidationErrors', {index, errors: this.validator.errors})
        /*if (index === null) {
            this.validationErrors = this.validator.errors;
        } else {
            if (typeof this.validationErrors != 'object' || this.validationErrors === null) {
                this.validationErrors = {};
            }
            this.validationErrors[index] = this.validator.errors;
        }*/

        
        return false;

    }

    get defaultModelState()
    {
        return Object.assign({}, {
            loading: false,
            data: {},
            validationErrors: null,
            structure: null,
        });
    }

    get validationErrors()
    {
        console.log('GETTING VALIDATION ERRORS FROM THE MODEL');
        console.log(this.state.validationErrors);
        return this.state.validationErrors;
    }
}