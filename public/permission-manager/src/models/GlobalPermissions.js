import Model from './Model';

import actions from './actions.js';
import getters from './getters.js';
import mutations from './mutations.js';

export default class GlobalPermissions extends Model {
    constructor() {
        const name = 'GlobalPermissions';

        super(name);
        this.endpoint = 'acl-global-permissions';
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