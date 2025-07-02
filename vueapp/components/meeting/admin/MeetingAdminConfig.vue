<template>
    <div>
        <h1>{{ $gettext('Meetings konfigurieren') }}</h1>

        <MessageList />

        <MessageBox v-if="changes_made" type="warning">
            {{ $gettext('Ihre Änderungen sind noch nicht gespeichert!') }}
        </MessageBox>

        <form class="default" v-if="drivers" @submit.prevent>
            <fieldset>
                <legend>
                    {{ $gettext('Allgemeine Konfiguration') }}
                </legend>
                <label>
                    {{ $gettext('Feedback Support-Adresse') }}
                    <input type="text" v-model.trim="general_config['feedback_contact_address']">
                </label>
                <label>
                     {{ $gettext('Feedback Betreff') }}
                 <input type="text" v-model.trim="general_config['feedback_mail_subject']">
                </label>
                <label>
                    {{ $gettext('Feedback Absenderadresse') }}
                    <br>
                    <input type="radio" name="feedback-contact-address"
                        value="standard_mail"
                        v-model="general_config['feedback_sender_address']">
                    {{ $gettext('Standard-E-Mail') }}
                    <input type="radio" name="feedback-contact-address"
                        value="user_mail"
                        v-model="general_config['feedback_sender_address']">
                    {{ $gettext('Nutzer-E-Mail') }}
                </label>

                <label>
                    {{ $gettext('Müssen Teilnehmenden einer Datenschutzerklärung zustimmen, bevor sie an einer Besprechung mit Aufzeichnungsfunktion teilnehmen?') }}
                    <br>
                    <input type="radio" name="show-recording-privacy-text"
                        :value="true"
                        v-model="general_config['show_recording_privacy_text']">
                    {{ $gettext('Ja') }}
                    <input type="radio" name="show-recording-privacy-text"
                        :value="false"
                        v-model="general_config['show_recording_privacy_text']">
                    {{ $gettext('Nein') }}
                </label>

                <label>
                    {{ $gettext('Stud.IP für Standardfolien verwenden') }}
                    <br>
                    <input type="radio" name="read-default-slides-from"
                        value="studip"
                        v-model="general_config['read_default_slides_from']">
                    {{ $gettext('Ja') }}
                    <input type="radio" name="read-default-slides-from"
                        value="server"
                        v-model="general_config['read_default_slides_from']">
                    {{ $gettext('Nein') }}
                </label>
            </fieldset>

            <fieldset v-for="(driver, driver_name) in drivers" :key="driver_name">
                <legend>
                    {{ driver.title }}
                </legend>
                <label>
                        <input type="checkbox"
                        true-value="1"
                        false-value="0"
                        v-model="config[driver_name]['enable']">
                        {{ $gettext('Verwenden dieses Treibers zulassen') }}
                </label>

                <label v-if="Object.keys(config[driver_name]).includes('display_name')">
                    {{ $gettext('Anzeigename') }}
                    <input type="text" v-model.trim="config[driver_name]['display_name']">
                </label>

                <div v-if="Object.keys(driver).includes('recording')">
                    <label v-for="(rval, rkey) in driver['recording']" :key="rkey">
                        <input v-if="typeof rval['value'] == 'boolean'" type="checkbox"
                        true-value="1"
                        false-value="0"
                        @click="handleRecordings(driver_name, rval['name'])"
                        v-model="config[driver_name][rval['name']]">
                        <span>
                            {{ rval['display_name'] }}
                        </span>

                        <StudipTooltipIcon v-if="Object.keys(rval).includes('info')" :text="rval['info']">
                        </StudipTooltipIcon>
                    </label>
                </div>

                <div v-if="Object.keys(driver).includes('preupload')">
                    <label v-for="(rval, rkey) in driver['preupload']" :key="rkey">
                        <input v-if="typeof rval['value'] == 'boolean'" type="checkbox"
                        true-value="1"
                        false-value="0"
                        v-model="config[driver_name][rval['name']]">
                        <span :class="{'disabled': rval['name'] != 'preupload' && config[driver_name]['preupload'] != '1'}">
                            {{ rval['display_name'] }}
                        </span>

                        <StudipTooltipIcon v-if="Object.keys(rval).includes('info')" :text="rval['info']">
                        </StudipTooltipIcon>
                    </label>
                </div>

                <label v-if="Object.keys(config[driver_name]).includes('welcome')">
                    {{ $gettext('Begrüßungstext') }}
                    <textarea v-model="config[driver_name]['welcome']" cols="30" rows="5"></textarea>
                </label>

                <div v-if="config[driver_name].servers && Object.keys(config[driver_name].servers).length && server_object[driver_name]">
                    <h3>
                        {{ $gettext('Folgende Server werden verwendet') }}
                    </h3>
                    <table class="default collapsable tablesorter conference-meetings">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <template v-for="(value, key) in driver.config">
                                    <th scope="col" v-if="value.name != 'roomsize-presets' && value.name != 'description' && (!value.attr || value.attr != 'password')" :key="key"
                                    :class="{td_center:value.name == 'active'}"
                                    :title="value.display_name">
                                        {{ value.display_name }}
                                    </th>
                                </template>
                                <th scope="col">{{ $gettext('Aktionen') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(server, index) in config[driver_name].servers" :key="index"
                                :class="{'active nohover': (server_object[driver_name]['index'] == index)}">
                                <td>{{ index + 1 }}</td>
                                <template v-for="(value, key) in driver.config">
                                    <td :key="key" v-if="value.name && value.name != 'roomsize-presets' && value.name != 'description' && (!value.attr || value.attr != 'password')"
                                    :class="{td_center:value.name == 'active'}"
                                    :title="(value.name != 'active' && value.name != 'course_types' ? server[value.name] : '')"
                                    >
                                        <template v-if="value.name == 'maxParticipants'
                                                && (!(server[value.name]) || parseInt(server[value.name]) == 0)"
                                        >
                                            {{ $gettext('Ohne Grenze') }}
                                        </template>
                                        <template v-else-if="value.name == 'course_types'">
                                            {{ getCourseTypeName(server[value.name], driver_name) }}
                                        </template>
                                        <template v-else-if="value.name == 'active'">
                                            <StudipIcon :icon="(server[value.name]) ? 'checkbox-checked' : 'checkbox-unchecked'"
                                                :role="(server[value.name]) ? 'status-green' : 'status-red'" size="14"></StudipIcon>
                                        </template>
                                        <template v-else>
                                            {{ server[value.name] ? server[value.name] : '-' }}
                                        </template>
                                    </td>
                                </template>
                                <td>
                                    <a href="#" :title="$gettext('Server bearbeiten')"
                                        @click.prevent="prepareEditServer(driver_name, index)">
                                        <StudipIcon icon="edit" role="clickable" ></StudipIcon>
                                    </a>
                                    <a v-if="Object.keys(driver).includes('roomsize-presets')"
                                        href="#" :title="$gettext('Servervoreinstellungen')"
                                        @click.prevent="prepareEditServer(driver_name, index, true)">
                                        <StudipIcon icon="settings" role="clickable" ></StudipIcon>
                                    </a>
                                    <a href="#" :title="$gettext('Server löschen')"
                                        @click.prevent="deleteServer(driver_name, index)">
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
                    {{ $gettext('Server hinzufügen') }}
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

                <ServerRoomsizePresetsDialog
                    v-if="server_object[driver_name]"
                    :DialogVisible="presetDialogVisible == driver_name"
                    :server_object="server_object"
                    :driver_name="driver_name"
                    :driver="driver"
                    @close="presetDialogVisible = false"
                    @done="savePresets"
                />
            </fieldset>

            <MessageList />

            <MessageBox v-if="changes_made" type="warning">
                {{ $gettext('Ihre Änderungen sind noch nicht gespeichert!') }}
            </MessageBox>

            <footer>
                <StudipButton icon="accept"
                    :class="{
                        'disabled': !changes_made
                    }"
                    @click="storeConfig"
                >
                    {{ $gettext('Einstellungen speichern') }}
                </StudipButton>
            </footer>

        </form>
        <!-- Generic gialogs -->
        <studip-dialog
            v-if="showConfirmDialog"
            :title="showConfirmDialog.title"
            :question="showConfirmDialog.question"
            :alert="showConfirmDialog.alert"
            :message="showConfirmDialog.message"
            confirmClass="accept"
            closeClass="cancel"
            :height="showConfirmDialog.height !== undefined ? showConfirmDialog.height.toString() :  '180'"
            @confirm="performDialogConfirm(showConfirmDialog.confirm_callback, showConfirmDialog.confirm_callback_data)"
            @close="performDialogClose(showConfirmDialog.close_callback, showConfirmDialog.close_callback_data)"
        >
        </studip-dialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";
import { confirm_dialog } from '@/common/confirm_dialog.mixins'

import ServerDialog from "@meeting/admin/server/ServerDialog";
import ServerRoomsizePresetsDialog from "@meeting/admin/server/ServerRoomsizePresetsDialog";

import {
    CONFIG_LIST_READ,
    CONFIG_CREATE,
    MESSAGE_ADD,
    MESSAGES_CLEAR
} from "@/store/actions.type";

export default {
    name: "Admin",

    components: {
        ServerDialog,
        ServerRoomsizePresetsDialog
    },

    mixins: [confirm_dialog],

    data() {
        return {
            server_object: {},
            serverDialogVisible: false,
            presetDialogVisible: false,
            changes_made: false,
            showConfirmDialog: false,
        }
    },

    computed: {
        ...mapGetters(['config', 'drivers', 'general_config'])
    },

    methods: {
        storeConfig() {
            this.$store.dispatch(CONFIG_CREATE, {'config': this.config, 'general_config': this.general_config})
                .then(({ data }) => {
                    if (data?.message) {
                        this.$store.dispatch(MESSAGES_CLEAR);
                        this.$store.dispatch(MESSAGE_ADD, data.message);
                    }
                    this.$store.dispatch(CONFIG_LIST_READ)
                        .then(() => {
                            this.changes_made = false;
                            this.createServerObject();
                        });
                    this.changes_made = false;
                });
        },

        deleteServer(driver_name, index) {
            this.showConfirmDialog = {
                title: this.$gettext('Server löschen'),
                question: this.$gettext('Sind Sie sicher, dass Sie diesen Server löschen möchten?'),
                confirm_callback: 'performDeleteServer',
                confirm_callback_data: {driver_name, index},
            }
        },

        performDeleteServer({driver_name, index}) {
            this.$delete(this.config[driver_name]['servers'], index);
        },

        clearServer(driver_name) {
            for (var key in this.server_object[driver_name]) {
                //reset server_object values
                if (key != 'index') {
                    this.server_object[driver_name][key] = "";
                } else {
                    this.server_object[driver_name][key] = -1;
                }
                // Pre-define active param.
                this.$set(this.server_object[driver_name], 'active', true);
                // Pre-define roomsize-presets param.
                this.$set(this.server_object[driver_name], 'roomsize-presets', {});
            }
        },

        addServerDialog(driver_name) {
            this.clearServer(driver_name);
            this.serverDialogVisible = driver_name;
        },

        addEditServers(params) {
            let driver_name   = params.driver_name;
            let server_object = params.server;

            if (!this.config[driver_name]['servers']) {
                this.$set(this.config[driver_name], 'servers', []);
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

        prepareEditServer(driver_name, index, is_preset = false) {
            var current_obj = this.config[driver_name]['servers'][index];
            for (var key in current_obj) {
                this.server_object[driver_name][key] = current_obj[key];
            }
            this.server_object[driver_name]['index'] = index;

            this.serverDialogVisible = false;
            this.presetDialogVisible = false;
            if (!is_preset) {
                this.serverDialogVisible = driver_name;
            } else {
                this.presetDialogVisible = driver_name;
            }
        },

        savePresets(params) {
            if (this.config?.[params.driver_name]?.servers?.[params.server_index]) {
                this.$set(this.config[params.driver_name]['servers'][params.server_index], 'roomsize-presets' , params.server_presets);
            } else {
                this.$store.dispatch(MESSAGES_CLEAR);
                this.$store.dispatch(MESSAGE_ADD, {
                    type: 'error',
                    text: this.$gettext('Beim Speichern der Servervoreinstellungen ist ein Fehler aufgetreten.')
                });
            }
            this.presetDialogVisible = false;
        },

        createServerObject() {
            for (var driver_name in this.drivers) {
                var server_config = new Object();
                server_config.index = -1;
                this.$set(this.server_object, driver_name, server_config);
            }
        },

        handleRecordings(driver_name, recording_option) {
            // We want to allow only "opencast" or "record" as recording option to be enabled at the same time!
            setTimeout(() => {
                if (this.config[driver_name][recording_option] && this.config[driver_name][recording_option] == '1') {
                    if (recording_option == 'opencast' && this.config[driver_name]['record']) { // If opencast going to be enabled, record must be disabled.
                        this.$set(this.config[driver_name], 'record', '0');
                    } else if (recording_option == 'record' && this.config[driver_name]['opencast']) {// If record going to be enabled, opencast must be disabled.
                        this.$set(this.config[driver_name], 'opencast', '0');
                    }
                }
            }, 100);
        },

        getCourseTypeName(type_id, driver_name) {
            if (!type_id) {
                return 'Alle';
            }
            type_id = type_id + '';
            var class_id = type_id.split('_')[0];
            var config_course_types = this.drivers[driver_name]['config'].find( cf => { return cf.name == 'course_types'});
            if (config_course_types.value && config_course_types.value[class_id] && config_course_types.value[class_id]['subs'] && config_course_types.value[class_id]['subs'][type_id]) {
                return config_course_types.value[class_id]['subs'][type_id];
            } else {
                return this.$gettext('Unbekannt');
            }
        }
    },

    watch: {
        config: {
            handler: function() {
                this.changes_made = true;
            },
            deep: true
        },
        general_config: {
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
                this.createServerObject();
            });
    }
};
</script>
