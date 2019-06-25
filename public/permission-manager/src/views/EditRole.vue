<template>
    <div class="edit-role container-fluid">

        <router-link to="/">Alle rollen</router-link>

        <h3>
            Rol {{role}} aanpassen
        </h3>

        <div class="edit-buttons">
            <button @click="savePermissions" :disabled="!changedPermissions || updating" class="btn btn-xs btn-success" title="Sla de nieuwe waarden op">
                Opslaan
                <div v-if="updating" class="loader" />
            </button>
            <button @click="restorePermissions" :disabled="!changedPermissions  || updating" class="btn btn-xs btn-danger" title="Herstel naar originele waarden">Originele waarden</button>
        </div>

        <transition name="fade">
            <div v-if="alert.visible" class="alert" :class="'alert-'+alert.type">
                <button @click="alert.visible = false" type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                {{alert.messages[alert.type]}}
            </div>
        </transition>


        <h4>Voorgedefineerde groepen</h4>
        <table class="table table-condensed table-striped acl-groups">
            <thead>
                <tr>
                    <th>Groep</th>
                    <th>Omschrijving</th>
                    <th>Helemaal toegevoegd</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(active, aclGroup, index) in aclGroupsActive" :key="index">
                    <th>{{aclGroup}} <i v-if="active === false" :title="aclGroupsMissingText[aclGroup]" class="acl-missing-help-text fa fa-question-circle" aria-hidden="true"></i></th>
                    <td class="description">{{aclGroups[aclGroup].description}}</td>
                    <td><toggle-button @change="applyGroupToPermissions(aclGroup, active)" :value="active" :sync="true" :color="{checked: '#23d160', unchecked: '#f7adb3'}" /></td>
                </tr>
            </tbody>
        </table>

        <h4>Rollen</h4>
        <table class="table table-condensed table-striped">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>GET</th>
                    <th>POST</th>
                    <th>PATCH</th>
                    <th>PUT</th>
                    <th>DELETE</th>
                    <th>OPTIONS</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(methods, permission, index) in currentPermissions" :key="index">
                    <th>{{permission}}</th>
                    <td><toggle-button v-if="methods.hasOwnProperty('GET')" v-model="methods.GET" :sync="true" :color="{checked: '#23d160', unchecked: '#f7adb3'}" /></td>
                    <td><toggle-button v-if="methods.hasOwnProperty('POST')" v-model="methods.POST" :sync="true" :color="{checked: '#23d160', unchecked: '#f7adb3'}" /></td>
                    <td><toggle-button v-if="methods.hasOwnProperty('PATCH')" v-model="methods.PATCH" :sync="true" :color="{checked: '#23d160', unchecked: '#f7adb3'}" /></td>
                    <td><toggle-button v-if="methods.hasOwnProperty('PUT')" v-model="methods.PUT" :sync="true" :color="{checked: '#23d160', unchecked: '#f7adb3'}" /></td>
                    <td><toggle-button v-if="methods.hasOwnProperty('DELETE')" v-model="methods.DELETE" :sync="true" :color="{checked: '#23d160', unchecked: '#f7adb3'}" /></td>
                    <td><toggle-button v-if="methods.hasOwnProperty('OPTIONS')" v-model="methods.OPTIONS" :sync="true" :color="{checked: '#23d160', unchecked: '#f7adb3'}" /></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import { ToggleButton } from 'vue-js-toggle-button';

import AclGroupsModel from '../models/AclGroups';
import GlobalPermissionsModel from '../models/GlobalPermissions';
import RolePermissionsModel from '../models/RolePermissions';
export default {
    data: () => ({
        aclGroups: null,
        aclGroupsModel: new AclGroupsModel,
        allPermissions: null,
        basePermissions: null,
        currentPermissions: null,
        globalPermissionsModel: new GlobalPermissionsModel,
        rolePermissions: null,
        rolePermissionsModel: new RolePermissionsModel,
        updating: false,
        alert: {
            visible: false,
            type: 'success',
            messages: {
                success: 'Rol opgeslagen',
                danger: 'Rol kon niet worden opgeslagen',
            },
        }
    }),
    components: { ToggleButton },
    computed: {
        changedPermissions() {
            return JSON.stringify(this.currentPermissions) != JSON.stringify(this.basePermissions);
        },
        aclGroupsMissing() {
            if (this.aclGroups && this.currentPermissions) {
                let groupsActive = {};
                for(const groupName in this.aclGroups) {
                    const permissions = this.aclGroups[groupName].permissions;
                    groupsActive[groupName] = null;
                    for (const permission in permissions) {
                        const methods = permissions[permission];
                        for (const key in methods) {
                            const method = methods[key];
                            
                            if (this.currentPermissions.hasOwnProperty(permission) && this.currentPermissions[permission].hasOwnProperty(method) && this.currentPermissions[permission][method] === false) {
                                if (groupsActive[groupName] === null) {
                                    groupsActive[groupName] = {};
                                }
                                if (!groupsActive[groupName].hasOwnProperty(permission)) {
                                    groupsActive[groupName][permission] = {};
                                }
                                groupsActive[groupName][permission][method] = false;
                            }
                        }
                    }
                }
                return groupsActive;
            }
            return null;
        },
        aclGroupsMissingText() {
            if (this.aclGroupsMissing && this.aclGroups && this.currentPermissions) {
                let groupsMissing = {};
                for(const groupName in this.aclGroupsMissing) {
                    const permissions = this.aclGroupsMissing[groupName];
                    groupsMissing[groupName] = "MISSING: \n";
                    for (const permission in permissions) {
                        const methods = Object.keys(permissions[permission]);
                        groupsMissing[groupName] += permission + ': ' + methods.join(', ') + "\n";
                    }
                }
                return groupsMissing;
            }
            return null;
        },
        aclGroupsActive() {
            if (this.aclGroupsMissing && this.aclGroups && this.currentPermissions) {
                let groupsActive = {};
                for (const groupName in this.aclGroupsMissing) {
                    groupsActive[groupName] = false;
                    if (this.aclGroupsMissing[groupName] === null) {
                        groupsActive[groupName] = true;
                    }
                }
                return groupsActive;
            }
            return null;
        },
        role() {
            return this.$route.params.roleId
        }
    },
    methods: {
        applyGroupToPermissions(aclGroup, unToggledValue)
        {
            const toggledValue = !unToggledValue;

            const currentActive = { ...this.aclGroupsActive };

            const changeValues = this.aclGroups[aclGroup].permissions;
            for (const permission in changeValues) {
                for(const key in changeValues[permission]) {
                    const method = changeValues[permission][key];
                    if (this.currentPermissions.hasOwnProperty(permission) && this.currentPermissions[permission].hasOwnProperty(method)) {
                        if (toggledValue === true && this.currentPermissions[permission][method] === false) {
                            this.currentPermissions[permission][method] = true;
                        } else if (toggledValue === false && this.currentPermissions[permission][method] === true) {
                            this.currentPermissions[permission][method] = false;
                        }
                    }
                }
            }

            if (toggledValue === false) {
                for(const groupName in currentActive) {
                    if (groupName === aclGroup) {
                        continue;
                    }
                    if (currentActive[groupName] === true && this.aclGroups[groupName].hasOwnProperty('permissions')) {
                        for(const permission in this.aclGroups[groupName].permissions) {
                            for(const key in changeValues[permission]) {
                                const method = changeValues[permission][key];
                                if (this.currentPermissions.hasOwnProperty(permission) && this.currentPermissions[permission].hasOwnProperty(method) && this.currentPermissions[permission][method] === false) {
                                    console.log('RESWITCH!');
                                    console.log(permission);
                                    console.log(method);
                                    console.log('---');
                                    this.currentPermissions[permission][method] = true;
                                }
                            }
                        }
                    }
                }
            }
        },
        async getAclGroups()
        {
            const groups = await this.aclGroupsModel.all();
            this.aclGroups = groups;
        },
        async getCurrentPermissions(role, refresh=false)
        {
            const allPermissions = await this.globalPermissionsModel.all(refresh);
            this.allPermissions = allPermissions;

            let basePermissions = {};
            for(const permission in allPermissions) {
                const methods = allPermissions[permission];
                basePermissions[permission] = {};
                for(const key in methods) {
                    const method = methods[key];
                    basePermissions[permission][method] = false;
                }
            }

            const permissions = await this.rolePermissionsModel.findById(role, refresh);
            this.rolePermissions = permissions;

            for(const permission in permissions) {
                const methods = permissions[permission];
                if (!basePermissions.hasOwnProperty(permission)) {
                    continue;
                }
                
                for(const key in methods) {
                    const method = methods[key];
                    basePermissions[permission][method] = true;
                }
            }
            
            this.basePermissions = JSON.parse(JSON.stringify(basePermissions));
            this.currentPermissions = basePermissions;
        },
        restorePermissions()
        {
            this.currentPermissions = JSON.parse(JSON.stringify(this.basePermissions));
        },
        async savePermissions()
        {
            this.updating = true;
            await this.rolePermissionsModel.updateById(this.role, this.currentPermissions);
            await this.getCurrentPermissions(this.role, true);


            this.updating = false;
            this.alert.type = 'success';
            this.alert.visible = true;
        },
    },
    mounted() {
        this.getAclGroups();
        this.getCurrentPermissions(this.role);
    },
}
</script>
<style lang="scss" scoped>
    .acl-missing-help-text {
        color: #d9534f;
        cursor: pointer;
        &:hover {
            color: #ac2925;
        }
    }
    .edit-buttons {
        .btn {
            margin-right: 5rem;
        }
    }

    @keyframes spinner {
        to {
            transform: rotate(360deg);
        }
    }

    button .loader {
        display: inline-block;
        margin: 0 1em;
        position: relative;
        height: 1em;
        &:before {
            content: '';
            box-sizing: border-box;
            position: absolute;
            top: 50%;
            left: 50%;
            width: 1em;
            height: 1em;
            margin-top: -5px;
            margin-left: -5px;
            border-radius: 50%;
            border-top: 2px solid #07d;
            border-right: 2px solid transparent;
            animation: spinner .6s linear infinite;
        }
    }

    .fade-enter-active, .fade-leave-active {
        transition: opacity .3s;
    }
    .fade-enter, .fade-leave-to {
        opacity: 0;
    }

    .acl-groups .description {
        font-size: .9rem;
        font-style: italic;
    }

</style>



