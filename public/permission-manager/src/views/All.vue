<template>
    <div>
        <table class="table">
            <thead>
                <tr>
                    <th>
                        Rol
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(role, index) in roles" :key="index">
                    <td><router-link :to="/role/+role">{{role}}</router-link></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import RolesModel from '../models/Roles';

export default {
    data: () => ({
        rolesModel: new RolesModel,
        roles: null,
    }),
    methods: {
        async getRoles() {
            const roles = await this.rolesModel.all();
            this.roles = roles;
            return roles;
        }
    },
    mounted() {
        this.getRoles();
    }
}
</script>
