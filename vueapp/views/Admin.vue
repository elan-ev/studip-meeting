<template>
    <div>
        <h1>{{ "Meetings konfigurieren" | i18n }}</h1>

        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            <span v-if="typeof message.text == 'string'">{{ message.text }}</span>
            <ul v-else>
                <li v-for="(text, i) in message.text" :key="i">
                    {{text}}
                </li>
            </ul>
        </MessageBox>

        <MessageBox v-if="changes_made" type="warning">
            {{ 'Ihre Änderungen sind noch nicht gespeichert!' | i18n }}
        </MessageBox>

        <form class="default" v-if="drivers" @submit.prevent>
            <fieldset v-for="(driver, driver_name) in drivers" :key="driver_name">
                <legend>
                    {{ driver.title | i18n }}
                </legend>

                <label v-if="Object.keys(config[driver_name]).includes('enable')">
                        <input type="checkbox"
                        true-value="1"
                        false-value="0"
                        v-model="config[driver_name]['enable']">
                        {{ "Verwenden dieses Treibers zulassen" | i18n }}
                </label>

                <label v-if="Object.keys(config[driver_name]).includes('display_name')">
                    {{ "Display Name" | i18n }}
                    <input type="text" v-model.trim="config[driver_name]['display_name']">
                </label>

                <div v-if="Object.keys(driver).includes('recording')">
                    <label v-for="(rval, rkey) in driver['recording']" :key="rkey">
                        <input v-if="typeof rval['value'] == 'boolean'" type="checkbox"
                        :disabled="rval['name'] != 'record' && config[driver_name]['record'] != '1'"
                        :class="{'disabled': rval['name'] != 'record' && config[driver_name]['record'] != '1'}"
                        true-value="1"
                        false-value="0"
                        @click="handleRecordings(driver_name)"
                        v-model="config[driver_name][rval['name']]">
                        <span :class="{'disabled': rval['name'] != 'record' && config[driver_name]['record'] != '1'}">
                            {{ rval['display_name'] | i18n }}
                        </span>
                        <StudipTooltipIcon v-if="Object.keys(rval).includes('info')" :text="rval['info'] | i18n"></StudipTooltipIcon>
                    </label>
                </div>

                <div v-if="config[driver_name].servers && Object.keys(config[driver_name].servers).length">
                    <h3>
                        {{ "Folgende Server werden verwendet" | i18n }}
                    </h3>
                    <table class="default collapsable tablesorter conference-meetings">
                        <thead>
                            <tr>
                                <th>{{ "#" | i18n }}</th>
                                <th v-for="(value, key) in driver.config" :key="key">
                                    <template v-if="value.name != 'roomsize-presets'">
                                        {{ value.display_name | i18n }}
                                    </template>
                                </th>
                                <th>{{ "Aktionen" | i18n }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(server, index) in config[driver_name].servers" :key="index"
                                :class="{'active nohover': (server_object[driver_name]['index'] == index)}">
                                <td>{{ index + 1 }}</td>
                                <td v-for="(value, key) in driver.config" :key="key">
                                    <template v-if="value.name != 'roomsize-presets'">
                                        <template v-if="value.name == 'maxParticipants' && (!(server[value.name]) || parseInt(server[value.name]) == 0)">
                                            {{ 'Ohne Grenze' | i18n }}
                                        </template>
                                        <template v-else>
                                            {{server[value.name]}}
                                        </template>
                                    </template>
                                </td>
                                <td>
                                    <a style="cursor: pointer;" @click.prevent="prepareEditServer(driver_name, index)">
                                        <StudipIcon icon="edit" role="clickable" ></StudipIcon>
                                    </a>
                                    <a style="cursor: pointer;" @click.prevent="deleteServer(driver_name, index)">
                                        <StudipIcon icon="trash" role="clickable"></StudipIcon>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <StudipButton
                    icon="add"
                    @click="addServerDialog(driver_name)">
                    {{ "Server hinzufügen" | i18n }}
                </StudipButton>

                <ServerDialog
                    v-if="server_object[driver_name]"
                    :DialogVisible="serverDialogVisible == driver_name"
                    :server_object="server_object"
                    :driver_name="driver_name"
                    :driver="driver"
                    @close="serverDialogVisible = false"
                    @edit="addEditServers"
                />

            </fieldset>
            <footer>
                <StudipButton icon="accept"
                    :class="{
                        'disabled': !changes_made
                    }"
                    @click="storeConfig"
                >
                    {{ "Einstellungen speichern" | i18n}}
                </StudipButton>
            </footer>
        </form>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";
import ServerDialog from "@/components/ServerDialog";

import {
    CONFIG_LIST_READ,
    CONFIG_CREATE, CONFIG_DELETE
} from "@/store/actions.type";

import {
    CONFIG_SET,
} from "@/store/mutations.type";

export default {
    name: "Admin",

    components: {
        StudipButton,
        StudipTooltipIcon,
        MessageBox,
        StudipIcon,
        ServerDialog
    },

    data() {
        return {
            message: null,
            server_object: {},
            serverDialogVisible: false,
            changes_made: false
        }
    },

    computed: {
        ...mapGetters(['config', 'drivers'])
    },

    methods: {
        storeConfig() {
            this.$store.dispatch(CONFIG_CREATE, this.config)
                .then(({ data }) => {
                    this.message = data.message;
                    this.$store.commit(CONFIG_SET, data.config);
                    if (data.message.type == 'error') {
                       this.$store.dispatch(CONFIG_LIST_READ)
                            .then(() => {
                                this.changes_made = false;
                            });
                    }
                    this.changes_made = false;
                });
        },

        deleteServer(driver_name, index) {
            //this.changes_made = true;
            this.config[driver_name]['servers'].splice(index, 1);
        },

        clearServer(driver_name) {
            for (var key in this.server_object[driver_name]) {
                //reset server_object values
                if (key != 'index') {
                    this.server_object[driver_name][key] = "";
                } else {
                    this.server_object[driver_name][key] = -1;
                }
            }
        },

        addServerDialog(driver_name) {
            this.clearServer(driver_name);
            this.serverDialogVisible = driver_name;
        },

        addEditServers(params) {
            //this.changes_made = true;

            let driver_name   = params.driver_name;
            let server_object = params.server;

            if (!this.config[driver_name]['servers']) {
                this.config[driver_name]['servers'] = {}
            }

            var index = 0;
            // manage the index of the array
            if (server_object[driver_name]['index'] == -1) { //new
                index = Object.keys(this.config[driver_name]['servers']).length;
            } else { //edit
                index = server_object[driver_name]['index'];
            }
            // reset the index
            server_object[driver_name]['index'] = -1;

            //create a new object out of server_object (without index)
            var new_server_object = new Object();
            for (var key in server_object[driver_name]) {
                if (key != 'index') {
                    new_server_object[key] = server_object[driver_name][key];
                    //reset server_object values
                    server_object[driver_name][key] = "";
                }
            }
            //push to the servers array
            this.$set(this.config[driver_name]['servers'], index , new_server_object);
            this.serverDialogVisible = false;
        },

        prepareEditServer(driver_name, index) {
            var current_obj = this.config[driver_name]['servers'][index];
            for (var key in current_obj) {
                this.server_object[driver_name][key] = current_obj[key];
            }
            this.server_object[driver_name]['index'] = index;

            this.serverDialogVisible = driver_name;
        },

        createServerObject() {
            for (var driver_name in this.drivers) {
                var server_config = new Object();
                server_config.index = -1;
                this.$set(this.server_object, driver_name, server_config);
            }
        },

        handleRecordings(driver_name) {
            // it is used to disable every other parameters when the recording is off
            setTimeout(() => {
                if (this.config[driver_name]['record'] == '0') {
                    for (var rindex in this.drivers[driver_name]['recording']) {
                        if (this.drivers[driver_name]['recording'][rindex]['name'] != 'record') {
                            this.$set(this.config[driver_name], this.drivers[driver_name]['recording'][rindex]['name'], '0');
                        }
                    }
                }
            }, 100);
        }
    },

    watch: {
        config: {
            handler: function() {
                this.changes_made = true;
            },
            deep: true
        }
    },

    mounted() {
        store.dispatch(CONFIG_LIST_READ)
            .then(() => {
                this.changes_made = false;
            });
    },

    beforeMount() {
        this.createServerObject();
    }
};
</script>
