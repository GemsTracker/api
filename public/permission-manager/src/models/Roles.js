import Model from './Model';

import actions from './actions.js';
import getters from './getters.js';
import mutations from './mutations.js';

export default class Roles extends Model {
    constructor() {
        const name = 'Roles';

        super(name);
        this.endpoint = 'acl-roles';
        ///this.idField = 'gor_id_organization';

        this.register({
            state: {
                loading: false,
                data: {},
            },
            mutations,
            actions,
            getters
        });

        this.filters['per_page'] = 100;
    }
}