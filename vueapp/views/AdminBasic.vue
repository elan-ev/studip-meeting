<template>
    <div>
        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            {{ message.text }}
        </MessageBox>
        <form class="default" v-if="drivers">
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
                    </label>
                </div>
                <div v-if="Object.keys(config[driver_name].servers).length">
                    <h3>
                        {{ "Folgende Server werden verwendet" | i18n }}
                    </h3>
                    <table class="default collapsable tablesorter conference-meetings">
                        <thead>
                            <tr>
                                <th>{{ "#" | i18n }}</th>
                                <th v-for="(value, key) in driver.config" :key="key">
                                    {{ value.display_name | i18n }}
                                </th>
                                <th>{{ "Aktionen" | i18n }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(server, index) in config[driver_name].servers" :key="index"
                                :class="{'active nohover': (server_object[driver_name]['index'] == index)}">
                                <td>{{ index + 1 }}</td>
                                <td v-for="(value, key) in driver.config" :key="key">
                                    {{server[value.name]}}
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
                <div v-show="server_object[driver_name]">
                    <fieldset>
                        <legend>
                            {{ "Serverkonfiguration" | i18n }} ({{ server_object[driver_name]['index'] == -1 ? "Neu" : "Bearbeiten" | i18n }})
                        </legend>
                        <div v-for="(value, key) in driver.config" :key="key">
                            <label v-if="value.name != 'enable'">
                                {{ value.display_name | i18n }}
                                <input type="text"
                                    v-model="server_object[driver_name][value.name]"
                                    :placeholder="value.value">
                            </label>
                        </div>
                        <StudipButton
                            :icon="server_object[driver_name]['index'] == -1 ? 'add' : 'accept'"
                            @click="addEditServers(driver_name)">
                            {{ server_object[driver_name]['index'] == -1 ? "Server hinzufügen" : "Server bearbeiten" | i18n }}
                        </StudipButton>
                        <StudipButton v-if="server_object[driver_name]['index'] != -1"
                            icon="cancel" @click="clearServer(driver_name)">
                            {{ "Abbrechen" | i18n }}
                        </StudipButton>
                    </fieldset>
                </div>
            </fieldset>
            <footer>
                <StudipButton icon="accept" @click="storeConfig">
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
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";

import {
    CONFIG_LIST_READ,
    CONFIG_CREATE, CONFIG_DELETE
} from "@/store/actions.type";

import {
    CONFIG_SET,
} from "@/store/mutations.type";

export default {
    name: "AdminBasic",
    components: {
        StudipButton,
        MessageBox,
        StudipIcon
    },
    data() {
        return {
            message: null,
            server_object: {},
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
                });
        },
        deleteServer(driver_name, index) {
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
        addEditServers(driver_name) {
            var index = 0;
            // manage the index of the array
            if (this.server_object[driver_name]['index'] == -1) { //new
                index = Object.keys(this.config[driver_name]['servers']).length;
            } else { //edit
                index = this.server_object[driver_name]['index'];
            }
            // reset the index
            this.server_object[driver_name]['index'] = -1;

            //create a new object out of server_object (without index)
            var new_server_object = new Object();
            for (var key in this.server_object[driver_name]) {
                if (key != 'index') {
                    new_server_object[key] = this.server_object[driver_name][key];
                    //reset server_object values
                    this.server_object[driver_name][key] = "";
                }
            }
            //push to the servers array
            this.$set(this.config[driver_name]['servers'], index , new_server_object);
        },
        prepareEditServer(driver_name, index) {
            var current_obj = this.config[driver_name]['servers'][index];
            for (var key in current_obj) {
                this.server_object[driver_name][key] = current_obj[key];
            }
            this.server_object[driver_name]['index'] = index;
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
    mounted() {
        store.dispatch(CONFIG_LIST_READ);
    },
    beforeMount() {
        this.createServerObject();
    }
};
</script>
