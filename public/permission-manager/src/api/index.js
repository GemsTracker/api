import axios from 'axios'

//import pickBy from 'lodash/pickBy';

export default class Api {

    constructor(apiUrl, endpoint)
    {
        this.url = apiUrl;
        this.endpoint = endpoint;

        this.client = axios.create({
            baseURL: this.url,
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        this.emptyResponse = {
            data: null,
            status: 200,
            statusText: 'OK',
            headers: {},
            config: {},
            request: {},
        };

        this.mockEndpoints = [
            //'tokens',
        ];

        this.latency = 1000;
    }

    delete(data) {
        //console.log('DELETING: ' + this.endpoint);
        //console.log(data);

        return this.client.delete('/' + this.endpoint, {data: data})
            .then(response => {
                //console.log('RESPONSE!');
                //console.log(response.data);
                return response.data;
            }).catch(error => {
                console.log(error);
                console.log(error.response.data);
            });
    }

    insert(data) {
        console.log('INSERTING: ' + this.endpoint);
        const endpoint = this.endpoint;
        console.log(data);
        return this.client.post('/' + this.endpoint, data)
            .then(response => {
                console.log('INSERT RESPONSE ON ' + endpoint);
                console.log(response.data);
                return response;
            }).catch(error => {
                console.log(error);
                console.log(error.response.data);
            });
    }

    load(filters)
    {
        //console.log('LOADING: '+this.endpoint);

        const endpoint = this.endpoint.split('/');

        //console.log(this.mockEndpoints);
        //console.log(endpoint);
        //console.log(this.mockEndpoints.indexOf(endpoint[0]));

        if (this.mockEndpoints.indexOf(endpoint[0]) === -1) {
            console.log('API CALL: ' + this.url + '/' + this.endpoint + '?' + this.getTextFilters(filters));
            return this.client.get('/' + this.endpoint, {
                params: filters
            }).then(response => {
                console.log('RESPONSE  on '+ endpoint);
                console.log(response.status);
                console.log(response.data);
                return response.data;
            }).catch(e => {
                console.log(e.response);
                console.log(e);
            });
        } else {
            //console.log('MOCK CALL for ' + this.endpoint);
            return new Promise((resolve, reject) => {
                const response = this.getResponse(filters);
                setTimeout(() => {
                    resolve(response);
                }, this.latency)
            }).then(response => {
                return response.data;
            }).catch(e => {
                console.log(e);
            })
        }
    }

    structure() {
        //console.log(this.url + '/' + this.endpoint + '/structure');
        return this.client.get('/' + this.endpoint + '/structure')
            .then(response => {
                return response.data;
            }).catch(e => {
                console.log(e);
            });
    }
    
    update(data)
    {
        /* console.log('UPDATING: '+this.endpoint);
        console.log(data); */

        return this.client.patch('/' + this.endpoint, data)
            .then(response => {
                //console.log('UPDATE RESPONSE!');
                //console.log(response);
                return response;
            }).catch(error => {
                console.log(error);
                console.log(error.response.data);
            });
    }

    getResponse(filters)
    {        
        let response = this.emptyResponse;

        const endpoint = this.endpoint.split('/');

        /*if (endpoint[0] == 'tokens') {
            response.data = this.filter(tokens, filters);
            return response;
        }*/
    
        return response;
    }

    getTextFilters(filters)
    {
        let textFilter = '';
        for (const field in filters) {
            textFilter += field + '=' + filters[field] + '&';
        }
        return textFilter;
    }

    filter(data, filters)
    {
        return pickBy(data, function(value, key) {
            let filteredData = true;
            for (const i in filters) {
                if (value[i] != filters[i]) {
                    filteredData = false;
                }
            }
            return filteredData;
        });
    }

    /*
     * Example api call
    loadTracks()
    {
        return axios.get(this.url+'/tracks', {
            params: {
                active: 1
            }
        }).then(response => {
                return response.data;
            }).catch(e => {
                console.log(e);
            });
    }*/

}