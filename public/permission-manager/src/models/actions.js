import Api from '../api';

function addIdFieldAsKey(data, idField)
{
    let filteredData = {};
    if (Array.isArray(data)) {
        for (const key in data) {
            const newKey = data[key][idField];
            filteredData[newKey] = data[key];
        }
    } else {
        const newKey = data[idField];
        filteredData[newKey] = data;
    }

    return filteredData;
}

function addIdAsKey(data, id)
{
    let filteredData = {};
    filteredData[id] = data;

    return filteredData;
}

function groupDataBy(groupBy, idField, data)
{
    let groupedData = {};
    for (const key in data) {
        const groupKey = data[key][groupBy];
        if (groupedData[groupKey] === undefined) {
            groupedData[groupKey] = [];
            if (idField) {
                groupedData[groupKey] = {};    
            }
        }
        
        if (idField) {
            const idKey = data[key][idField];
            groupedData[groupKey][idKey] = data[key];
        } else {
            groupedData[groupKey].push(data[key]);    
        }
    }
    return groupedData;
}

export default {

    delete(context, { apiUrl, endpoint, data }) {
        const api = new Api(apiUrl, endpoint);
        const apiCall = api.delete(data)
            .then((data) => {
                return data;
            });
        return apiCall;

    },

    insert(context, {apiUrl, endpoint, data}) 
    {
        const api = new Api(apiUrl, endpoint);
        const apiCall = api.insert(data)
            .then((data) => {
                if (data.headers.hasOwnProperty('location')) {
                    let result = data.headers.location.replace(apiUrl, '').replace(endpoint, '');
                    while (result.charAt(0) === '/') {
                        result = result.substr(1);
                    }
                    result = result.split('/');
                    return result;
                }

            });
        return apiCall;
    },

    load(context, {apiUrl, endpoint, groupBy, idField, id, storeOne, filters, callback} = {})
    {       
        const api = new Api(apiUrl, endpoint);
        context.commit('LOADING', true);
        const apiCall = api.load(filters)
            .then((data) => {
                if (groupBy) {
                    data = groupDataBy(groupBy, idField, data);
                } else if (idField) {
                    data = addIdFieldAsKey(data, idField);
                } else if (id) {
                    data = addIdAsKey(data, id);
                }
            
                if (storeOne === true && data !== null) {
                    let firstEntry = null;
                    for (const single in data) {
                        firstEntry = data[single];
                        break;
                    }
                    data = firstEntry;
                }
                console.log('this happens in the action!');
                console.log(data);
                context.commit('SUCCESS', data);
                return data;
            });

        return apiCall;
    },

    clear(context) {
        context.commit('CLEAR', true);
    },

    structure(context, { apiUrl, endpoint }) {
        const api = new Api(apiUrl, endpoint);
        const apiCall = api.structure()
            .then((data) => {
                console.log('Received STRUCTURE DATA for' + endpoint);
                console.log(data);
                context.commit('STRUCTURE_DATA', data);
                return data;
            });
        return apiCall;
    },

    update(context, { apiUrl, endpoint, data }) {
        const api = new Api(apiUrl, endpoint);
        const apiCall = api.update(data)
            .then((data) => {
                return data;
            });
        return apiCall;

    },

    addValidationErrors(context, {index, errors})
    {
        
        let validationErrors = context.state.validationErrors;
        console.log('ADDING VALIDATION ERRORS');
        console.log(validationErrors);
        console.log(index);
        console.log(errors);
        if (index == null) {
            validationErrors = errors;
        } else {
            if (typeof validationErrors != 'object' || validationErrors === null) {
                validationErrors = {};
            }
            validationErrors[index] = errors;
        }

        context.commit('UPDATE_VALIDATION_ERRORS', validationErrors);
    },

    removeValidationErrors(context, {index})
    {
        let validationErrors = context.state.validationErrors;
        if (index === null) {
            validationErrors = null;
        } else {
            if (validationErrors !== null && typeof validationErrors === 'object' && validationErrors.hasOwnProperty(index)) {
                delete validationErrors[index];
            }
        }
        context.commit('UPDATE_VALIDATION_ERRORS', validationErrors);
    },
};