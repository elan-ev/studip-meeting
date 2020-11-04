<template>
    <div>
        <MessageBox v-if="course_config.introduction" type="info">
            <span v-html="course_config.introduction"></span>
        </MessageBox>

        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            {{ message.text }}
        </MessageBox>

        <MessageBox v-if="Object.keys(config_list).length === 0" type="error">
            {{ "Es ist bisher kein Meetingsserver konfiguriert. Bitte wenden Sie sich an eine/n Systemadministrator/in!" | i18n }}
        </MessageBox>

        <span v-else>
            <MessageBox v-if="rooms_checked && !rooms_list.length && config && course_config.display.addRoom" :type="'info'">
                {{ "Bisher existieren keine Meeting-Räume für diese Veranstaltung. Möchten Sie einen anlegen?" | i18n }}
                <br>
                <StudipButton type="button"  @click="showAddMeeting()">
                    {{ "Neuer Raum" | i18n}}
                </StudipButton>
            </MessageBox>

            <MessageBox v-if="!rooms_checked" type="warning">
                {{ "Raumliste wird geladen..." | i18n }}
            </MessageBox>

            <p>
                <StudipButton type="button" icon="add" v-if="rooms_list.length && config && course_config.display.addRoom"
                    @click="showAddMeeting()">
                    {{ 'Raum hinzufügen' | i18n }}
                </StudipButton>

                <label v-if="rooms_list.length">
                    <input type="text" :placeholder="`Räume filtern nach Name` | i18n" v-model="searchtext">
                </label>
            </p>

            <form class="default conference-meeting" v-if="rooms_list_filtered.length">
                    <MeetingComponent v-for="(room, index) in rooms_list_filtered"
                        :key="index"
                        :room="room"
                        :info="rooms_info !== undefined && rooms_info[room.id] ? rooms_info[room.id] : {}"
                        v-on:getRecording="showRecording"
                        v-on:renewRoomList="getRoomList"
                        v-on:getGuestInfo="showGuestDialog"
                        v-on:getFeatures="showEditFeatureDialog"
                        v-on:setMessage="showMessage"
                        v-on:getFeedback="showFeedbackDialog">
                    </MeetingComponent>
            </form>

            <div v-if="config_list" id="conference-meeting-create" style="display: none" >
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <MessageBox v-else-if="room['driver_name'] && !Object.keys(config_list[room['driver_name']]['servers']).length"
                     type="error">
                    {{ "Es gibt keine Server für dieses Konferenzsystem, bitte wählen Sie ein anderes Konferenzsystem" | i18n }}
                </MessageBox>

                <form class="default" @keyup="roomFormSubmit($event)" style="position: relative">
                    <fieldset>
                        <legend>
                            {{ 'Raumname' | i18n }}
                        </legend>
                        <label>
                            <input type="text" v-model.trim="room['name']" id="name">
                        </label>
                    </fieldset>
                    <fieldset v-if="(Object.keys(config_list).length > 1) || (room['driver_name']
                                && Object.keys(config_list[room['driver_name']]['servers']).length > 1)">
                        <legend>
                            {{ 'Konferenz Systemeinstellung' | i18n }}
                        </legend>
                        <label v-if="Object.keys(config_list).length > 1">
                            <span class="required">{{ "Konferenzsystem" | i18n }}</span>
                            <select id="driver_name" v-model="room['driver_name']" @change.prevent="handleServerDefaults" :disabled="Object.keys(config_list).length == 1">
                                <option value="" disabled> {{ "Bitte wählen Sie ein Konferenzsystem aus" | i18n }} </option>
                                <option v-for="(driver_config, driver_name) in config_list" :key="driver_name"
                                        :value="driver_name">
                                        {{ driver_config['display_name'] }}
                                </option>
                            </select>
                        </label>
                        <label v-if="room['driver_name']
                                && Object.keys(config_list[room['driver_name']]['servers']).length > 1"
                        >
                            <span class="required">
                                {{ "Verfügbare Server" | i18n }}
                            </span>

                            <select id="server_index" v-model="room['server_index']" @change.prevent="handleServerDefaults"
                                :disabled="Object.keys(config_list[room['driver_name']]['servers']).length == 1">
                                <option value="" disabled> {{ "Bitte wählen Sie einen Server aus" | i18n }} </option>
                                <option v-for="(server_config, server_index) in config_list[room['driver_name']]['servers']" :key="server_index"
                                        :value="'' + server_index">
                                        Server {{ (server_index + 1) }}
                                        <span v-if="config_list[room['driver_name']]['server_defaults'] && config_list[room['driver_name']]['server_defaults'][server_index] 
                                                    &&  config_list[room['driver_name']]['server_defaults'][server_index]['maxAllowedParticipants']">
                                            ({{ "max. " + config_list[room['driver_name']]['server_defaults'][server_index]['maxAllowedParticipants'] }} {{ "Teilnehmer" | i18n }})
                                        </span>
                                </option>
                            </select>
                        </label>
                    </fieldset>
                    <fieldset>
                        <legend>{{ "Zusätzliche Funktionen" | i18n }}</legend>
                        <!-- Moderationsrechte -->
                        <label>
                            <input type="checkbox"
                            id="join_as_moderator"
                            true-value="1"
                            false-value="0"
                            v-model="room['join_as_moderator']">
                            {{ "Alle Teilnehmenden haben Moderationsrechte" | i18n }}
                        </label>
                        <div v-if="room['driver_name'] && Object.keys(config_list[room['driver_name']]).includes('features')
                                && Object.keys(this.config_list[this.room['driver_name']]['features']).includes('create') &&
                                Object.keys(config_list[room['driver_name']]['features']['create']).length">
                            <div v-for="(feature, index) in config_list[room['driver_name']]['features']['create']" :key="index">
                                <label v-if="(feature['value'] === true || feature['value'] === false)">
                                    <input  type="checkbox"
                                        true-value="true"
                                        false-value="false"
                                        v-model="room['features'][feature['name']]">

                                        {{ feature['display_name'] | i18n }}
                                        <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info'] | i18n"></StudipTooltipIcon>
                                </label>

                                <label v-else-if="feature['value'] && typeof feature['value'] === 'object'">
                                    {{ feature['display_name'] | i18n }}
                                    <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info'] | i18n"></StudipTooltipIcon>

                                    <select :id="feature['name']" v-model.trim="room['features'][feature['name']]">
                                        <option v-for="(fvalue, findex) in feature['value']" :key="findex"
                                                :value="findex">
                                                {{ fvalue | i18n }}
                                        </option>
                                    </select>
                                </label>
                                <label v-else>
                                    {{ feature['display_name'] | i18n }} 
                                    <span v-if="feature['name'] == 'maxParticipants' 
                                            && Object.keys(config_list[room['driver_name']]).includes('server_defaults')
                                            && room['server_index']
                                            && Object.keys(config_list[room['driver_name']]['server_defaults'][room['server_index']]).includes('maxAllowedParticipants')">
                                        &nbsp; ({{"Max. Limit: " + config_list[room['driver_name']]['server_defaults'][room['server_index']]['maxAllowedParticipants']}})
                                    </span>
                                    <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info'] | i18n"></StudipTooltipIcon>

                                    <input :type="(feature['name'] == 'duration' || feature['name'] == 'maxParticipants') ? 'number' : 'text'" 
                                        :max="(
                                            (feature['name'] == 'maxParticipants') ? 
                                            (Object.keys(config_list[room['driver_name']]).includes('server_defaults') && room['server_index']
                                                && Object.keys(config_list[room['driver_name']]['server_defaults'][room['server_index']]).includes('maxAllowedParticipants')) ? 
                                                    config_list[room['driver_name']]['server_defaults'][room['server_index']]['maxAllowedParticipants']
                                                : ''
                                            : ''
                                        )"
                                        :min="(feature['name'] == 'maxParticipants') ? 0 : ''"
                                        @change="(feature['name'] == 'maxParticipants') ? checkPresets() : ''"
                                        v-model.trim="room['features'][feature['name']]" 
                                        :placeholder="feature['value'] ? feature['value'] : ''" 
                                        :id="feature['name']">
                                </label>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset v-if="room['driver_name'] && Object.keys(config_list[room['driver_name']]).includes('features')
                                && Object.keys(this.config_list[this.room['driver_name']]['features']).includes('record')
                                && Object.keys(config_list[room['driver_name']]['features']['record']).length
                                && Object.keys(config_list[room['driver_name']]).includes('record')">
                        <legend>{{ "Aufzeichnung" | i18n }}</legend>
                        <div v-for="(feature, index) in config_list[room['driver_name']]['features']['record']" :key="index">
                            <label v-if="(feature['value'] === true || feature['value'] === false)">
                                <input  type="checkbox"
                                    true-value="true"
                                    false-value="false"
                                    v-model="room['features'][feature['name']]">

                                    {{ feature['display_name'] | i18n }}
                                    <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info'] | i18n"
                                        :badge="(Object.keys(config_list[room['driver_name']]).includes('opencast') && config_list[room['driver_name']]['opencast'] == '1' && feature['info'].toLowerCase().includes('opencast')) ? true : false">{{'beta'}}</StudipTooltipIcon>
                            </label>

                            <label v-else-if="feature['value'] && typeof feature['value'] === 'object'">
                                {{ feature['display_name'] | i18n }}
                                <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info'] | i18n"></StudipTooltipIcon>

                                <select :id="feature['name']" v-model.trim="room['features'][feature['name']]">
                                    <option v-for="(fvalue, findex) in feature['value']" :key="findex"
                                            :value="findex">
                                            {{ fvalue | i18n }}
                                    </option>
                                </select>
                            </label>
                            <label v-else>
                                {{ feature['display_name'] | i18n }}
                                <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info'] | i18n"></StudipTooltipIcon>

                                <input type="text" v-model.trim="room['features'][feature['name']]" :placeholder="feature['value'] ? feature['value'] : ''" :id="feature['name']">
                            </label>
                        </div>
                    </fieldset>


                    <fieldset v-if="(Object.keys(course_groups).length > 1)">
                        <legend>{{ "Gruppenraum" | i18n }}</legend>
                        <label>
                            {{ 'Wählen sie eine zugehörige Gruppe aus' | i18n }}
                            <select id="gruppen" v-model.trim="room.group_id">
                                <option value=""> {{ "Keine Gruppe" | i18n }} </option>
                                <option v-for="(gname, gid) in course_groups" :key="gid"
                                        :value="gid">
                                        {{ gname | i18n }}
                                </option>
                            </select>
                        </label>
                    </fieldset>

                    <div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
                        <div class="ui-dialog-buttonset">
                            <StudipButton v-if="room['id']" icon="accept" type="button" v-on:click="editRoom($event)" class="ui-button ui-corner-all ui-widget">
                                {{ "Änderungen speichern" | i18n}}
                            </StudipButton>
                            <StudipButton v-else icon="accept" type="button" v-on:click="addRoom($event)" class="ui-button ui-corner-all ui-widget">
                                {{ "Raum erstellen" | i18n}}
                            </StudipButton>
                            <StudipButton icon="cancel" type="button" v-on:click="cancelAddRoom($event)" class="ui-button ui-corner-all ui-widget">
                                {{ "Abbrechen" | i18n}}
                            </StudipButton>
                        </div>
                    </div>
                </form>
            </div>
            <div id="recording-modal" style="display: none;">
                <form class="default" method="post" style="position: relative">
                    <fieldset v-if="Object.keys(recording_list).includes('opencast')">
                        <legend>{{ "Opencast" | i18n }}</legend>
                        <label>
                            <a class="meeting-recording-url" target="_blank"
                            :href="recording_list['opencast']">
                                {{ 'Die vorhandenen Aufzeichnungen auf Opencast' | i18n}}
                            </a>
                        </label>
                    </fieldset>
                    <fieldset v-if="Object.keys(recording_list).includes('default') && Object.keys(recording_list['default']).length">
                        <label>
                            <table  class="default collapsable">
                                <thead>
                                    <tr>
                                        <th>{{ "Datum" | i18n }}</th>
                                        <th>{{ "Aktionen" | i18n }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(recording, index) in recording_list.default" :key="index">
                                        <td style="width: 60%">{{ recording['startTime'] }}</td>
                                        <td style="width: 40%">
                                            <div style="display: inline-block;width:80%;">
                                                <div v-if="Array.isArray(recording['playback']['format'])" style="display: flex; flex-direction: column; ">
                                                    <a v-for="(format, index) in recording['playback']['format']" :key="index"
                                                    class="meeting-recording-url" target="_blank"
                                                    :href="format['url']">
                                                        {{ `Aufzeichnung ansehen` | i18n}} {{ `(${format['type']})` }}
                                                    </a>
                                                </div>
                                                <div v-else>
                                                    <a class="meeting-recording-url" target="_blank"
                                                    :href="recording['playback']['format']['url']">
                                                        {{ `Aufzeichnung ansehen`  | i18n}}
                                                    </a>
                                                </div>
                                            </div>
                                            <div v-if="course_config.display.deleteRecording" style="display: inline-block;width:15%; text-align: right;">
                                                <a style="cursor: pointer;" @click.prevent="deleteRecording(recording)">
                                                    <StudipIcon icon="trash" role="attention"></StudipIcon>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </label>
                    </fieldset>
                </form>
            </div>
            <div id="guest-invitation-modal" style="display: none;">
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>

                <form class="default" @submit.prevent="generateGuestJoin">
                    <fieldset>
                        <label>
                            <span class="required">{{ "Gastname" | i18n }}</span>
                            <StudipTooltipIcon :text="'Der Gast bekommt diesen Namen in der Besprechung zugewiesen.' | i18n"></StudipTooltipIcon>
                            <input type="text" v-model.trim="room['guest_name']" id="guestname" @change="generateGuestJoin($event)">
                        </label>

                        <label id="guest_link_label" v-if="guest_link">
                            <span>{{ "Link" | i18n }}</span>
                            <StudipTooltipIcon :text="'Bitte geben sie diesen Link dem Gast.' | i18n" :important="true"></StudipTooltipIcon>
                            <textarea ref="guestLinkArea" v-model="guest_link" cols="30" rows="5"></textarea>
                        </label>

                        <div>
                            <StudipButton type="button" v-on:click="copyGuestLinkClipboard($event)" v-if="guest_link">
                                {{ "In Zwischenablage kopieren" | i18n}}
                            </StudipButton>
                            <StudipButton id="generate_link_btn" icon="accept" type="button" v-on:click="generateGuestJoin($event)" v-else>
                                {{ "Einladungslink erstellen" | i18n }}
                            </StudipButton>

                            <StudipButton icon="cancel" type="button" v-on:click="cancelGuest($event)">
                                {{ "Dialog schließen" | i18n}}
                            </StudipButton>
                        </div>
                    </fieldset>
                </form>
            </div>
             <div id="feedback-modal" style="display: none;">
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>

                <form class="default" @submit.prevent="feedbackFormSubmit">
                    <fieldset>
                        <legend>
                            {{ 'Beschreibung' | i18n }}
                        </legend>
                        <label class="col-6">
                            <textarea ref="feedbackDescription" v-model="feedback['description']" cols="30" rows="5"></textarea>
                        </label>
                    </fieldset>
                    <fieldset>
                        <legend>
                            {{ 'Feedback Informationen' | i18n }}
                        </legend>
                        <label class="col-3">
                            <span >{{ "Browser-Name" | i18n }}</span>
                            <input type="text" v-model.trim="feedback['browser_name']">
                        </label>
                        <label class="col-3">
                            <span >{{ "Browser-Version" | i18n }}</span>
                            <input type="text" v-model.trim="feedback['browser_version']">
                        </label>
                        <label class="col-3">
                            <span >{{ "Download-Geschw. (Mbps)" | i18n }}</span>
                            <input type="number" min="1" v-model.trim="feedback['download_speed']">
                        </label>
                        <label class="col-3">
                            <span >{{ "Upload-Geschw. (Mbps)" | i18n }}</span>
                            <input type="number" min="1" v-model.trim="feedback['upload_speed']">
                        </label>
                        <label class="col-3">
                            <span >{{ "Netzwerk-Typ" | i18n }}</span>
                            <select id="network-type" v-model="feedback['network_type']">
                                <option v-for="(nt_value, nt_name) in network_types" :key="nt_name"
                                        :value="nt_name">
                                        {{ nt_value }}
                                </option>
                            </select>
                        </label>
                        <label class="col-3">
                            <span >{{ "Betriebssystem (OS)" | i18n }}</span>
                            <input type="text" v-model.trim="feedback['os_name']">
                        </label>
                        <label class="col-3">
                            <span >{{ "Prozessortyp" | i18n }}</span>
                            <input type="text" v-model.trim="feedback['cpu_type']">
                        </label>
                        <label class="col-3">
                            <span >{{ "Alter des Rechners" | i18n }}</span>
                            <input type="text" v-model.number="feedback['cpu_old']">
                        </label>
                        <label class="col-3">
                            <span >{{ "Anzahl der CPU-Kerne" | i18n }}</span>
                            <input type="number" min="1" max="1000" v-model.number="feedback['cpu_num']">
                        </label>
                        <label class="col-3">
                            <span >{{ "RAM (Hauptspeicher) GB" | i18n }}</span>
                            <input type="number"  min="1" max="1000" v-model.number="feedback['ram']">
                        </label>
                    </fieldset>
                    <div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
                        <div class="ui-dialog-buttonset">
                            <StudipButton icon="accept" type="button" v-on:click="sumbitFeedback($event)" class="ui-button ui-corner-all ui-widget">
                                {{ "Einsenden" | i18n}}
                            </StudipButton>
                            <StudipButton icon="cancel" type="button" v-on:click="cancelFeedback($event)" class="ui-button ui-corner-all ui-widget">
                                {{ "Abbrechen" | i18n}}
                            </StudipButton>
                        </div>
                    </div>
                </form>
            </div>
        </span>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";
import MeetingStatus from "@/components/MeetingStatus";
import MeetingComponent from "@/components/MeetingComponent";

import {
    CONFIG_COURSE_READ,
    ROOM_LIST,
    ROOM_READ,
    ROOM_UPDATE,
    ROOM_CREATE,
    ROOM_JOIN,
    RECORDING_LIST,
    RECORDING_SHOW,
    RECORDING_DELETE,
    ROOM_JOIN_GUEST,
    ROOM_INFO,
    FEEDBACK_SUBMIT,
} from "@/store/actions.type";

import {
    ROOM_CLEAR,
    RECORDING_LIST_SET,
    FEEDBACK_CLEAR,
    FEEDBACK_INIT
} from "@/store/mutations.type";

export default {
    name: "Course",

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
        MeetingStatus,
        MeetingComponent
    },

    computed: {
        ...mapGetters([
            'config', 'room', 'rooms_list', 'rooms_info', 'rooms_checked',
            'course_config', 'recording_list', 'recording', 'feedback', 'network_types', 'course_groups'
        ]),

        config_list: function() {
            let config_list = {};

            for (var driver in this.config) {
                if (this.config[driver].enable == 1) {
                    config_list[driver] = this.config[driver];
                }
            }

            return config_list;
        },

        rooms_list_filtered: function() {
            let view = this;

            if (this.searchtext != '') {
                return this.rooms_list.filter(function(entry) {
                    return (entry.name.toLowerCase().indexOf(view.searchtext.toLowerCase()) !== -1);
                });
            } else {
                return this.rooms_list;
            }
        }
    },

    data() {
        return {
            message: null,
            modal_message: {},
            guest_link: '',
            searchtext: ''
        }
    },

    methods: {
        showFeedbackDialog(room) {
            this.modal_message = {};
            let options;

            // handle mobile devices
            if (window.innerWidth < 600) {
                options = {
                    width: '100%',
                    modal: true,
                    position: { my: "top", at: "top", of: window },
                    title: 'Feedback'.toLocaleString()
                }
            } else {
                options = {
                    minWidth: 500,
                    modal: true,
                    position: { my: "top", at: "top", of: window },
                    title: 'Feedback'.toLocaleString()
                }
            }
            this.$store.commit(FEEDBACK_INIT, room.id);
            $('#feedback-modal').dialog(options);
        },
        feedbackFormSubmit(event) {
            if (event.key == 'Enter' && $(event.target).is('input')) {
                 this.sumbitFeedback(event);
            }
        },
        sumbitFeedback(event) {
            if (event) {
                event.preventDefault();
            }
            if ( this.feedback.description ) {
                this.modal_message = {};
                this.$store.dispatch(FEEDBACK_SUBMIT, this.feedback)
                .then(({ data }) => {
                    this.message = data.message;
                    this.$store.commit(FEEDBACK_CLEAR);
                    if (this.message.type == 'error') {
                        this.$set(this.modal_message, "type" , "error");
                        this.$set(this.modal_message, "text" , this.message.text);
                    } else {
                        $('button.ui-dialog-titlebar-close').trigger('click');
                    }
                }).catch (({error}) => {
                    $('button.ui-dialog-titlebar-close').trigger('click');
                });
            } else {
                this.$set(this.modal_message, "type" , "error");
                this.$set(this.modal_message, "text" , `Beschreibung darf nicht leer sein`.toLocaleString());
            }

        },
        cancelFeedback() {
            this.$store.commit(FEEDBACK_CLEAR);
            $('#feedback-modal').dialog('close');
        },

        showAddMeeting() {
            this.modal_message = {};
            this.$store.commit(ROOM_CLEAR);
            
            let options;

            // handle mobile devices
            if (window.innerWidth < 600) {
                options = {
                    width: '100%',
                    modal: true,
                    position: { my: "top", at: "top", of: window },
                    title: 'Raum hinzufügen'.toLocaleString()
                }
            } else {
                options = {
                    minWidth: 500,
                    modal: true,
                    position: { my: "top", at: "top", of: window },
                    title: 'Raum hinzufügen'.toLocaleString()
                }
            }

            options.maxHeight = $(window).height();

            $('#conference-meeting-create').dialog(options);

            this.setDriver();
        },

        setDriver() {
            if (Object.keys(this.config_list).length == 1) {
                this.$set(this.room, "driver_name" , Object.keys(this.config_list)[0]);
                this.handleServerDefaults();
            }
        },

        handleServerDefaults() {
            //mandatory server selection when there is only one server
            if (this.room['driver_name'] && Object.keys(this.config_list[this.room['driver_name']]['servers']).length == 1) {
                this.$set(this.room, "server_index" , "0");
            }
            //set default features
            this.$set(this.room, "features" , {});

            if (Object.keys(this.config_list[this.room['driver_name']]).includes('features')) {
                //set default value of features
                if (Object.keys(this.config_list[this.room['driver_name']]['features']).includes('create') &&
                    Object.keys(this.config_list[this.room['driver_name']]['features']['create']).length) {
                    //applying first level of defaults for create features - important
                    this.config_list[this.room['driver_name']]['features']['create'].forEach(feature => { //apply all values for room feature!
                        this.$set(this.room['features'], feature.name , feature.value);
                    });
                    // set all selects to first entry 
                    for (let index in this.config_list[this.room['driver_name']]['features']['create']) {
                        let feature = this.config_list[this.room['driver_name']]['features']['create'][index];

                        if (typeof feature.value === 'object' && !Array.isArray(feature.value)) {
                            this.room['features'][feature['name']] = Object.keys(feature['value'])[0];
                        }
                    }

                    //Applying Second level of defaults from server defaults - if there is any but highly important!
                    if (this.room['server_index'] && Object.keys(this.config_list[this.room['driver_name']]).includes('server_defaults') &&
                        Object.keys(this.config_list[this.room['driver_name']]['server_defaults']).length && 
                        Object.keys(this.config_list[this.room['driver_name']]['server_defaults']).includes(this.room['server_index'])) {
                        for (const [feature_name, feature_value] of Object.entries(this.config_list[this.room['driver_name']]['server_defaults'][this.room['server_index']])) {
                            if (feature_name != 'maxAllowedParticipants') {
                                this.$set(this.room['features'], ((feature_name == 'totalMembers') ? 'maxParticipants' : feature_name ), feature_value);
                            }
                        }
                    }
                }
                if (Object.keys(this.config_list[this.room['driver_name']]['features']).includes('record') &&
                    Object.keys(this.config_list[this.room['driver_name']]['features']['record']).length) {
                    this.config_list[this.room['driver_name']]['features']['record'].forEach(feature => { //apply all values for room feature!
                        this.$set(this.room['features'], feature.name , feature.value);
                    });
                    // set all selects to first entry
                    for (let index in this.config_list[this.room['driver_name']]['features']['record']) {
                        let feature = this.config_list[this.room['driver_name']]['features']['record'][index];

                        if (typeof feature.value === 'object' && !Array.isArray(feature.value)) {
                            this.room['features'][feature['name']] = Object.keys(feature['value'])[0];
                        }
                    }
                }

            }
        },

        roomFormSubmit(event) {
            if (event.key == 'Enter' && $(event.target).is('input')) {
                if (Object.keys(this.room).includes('id')) {
                    this.editRoom(event);
                } else {
                    this.addRoom(event);
                }
            }
        },

        addRoom(event) {
            if (event) {
                event.preventDefault();
            }

            if (!this.validateMaxParticipants()) {
                return;
            }

            var empty_fields_arr = [];
            for (var key in this.room) {
                if (key != 'join_as_moderator' && key != 'features' && this.room[key] === '' ) {
                    $(`#${key}`).prev().hasClass('required') ? empty_fields_arr.push($(`#${key}`).prev().text()) : '';
                }
            }
            if ( !empty_fields_arr.length ) {
                this.modal_message = {};
                this.$store.dispatch(ROOM_CREATE, this.room)
                .then(({ data }) => {
                    this.message = data.message;
                    if (this.message.type == 'error') {
                        $('#conference-meeting-create').animate({ scrollTop: 0}, 'slow');
                        this.$set(this.modal_message, "type" , "error");
                        this.$set(this.modal_message, "text" , this.message.text);
                    } else {
                        $('button.ui-dialog-titlebar-close').trigger('click');
                        store.dispatch(ROOM_LIST);
                        setTimeout(function() {
                            this.message = null;
                        }, 3000);
                    }
                }).catch (({error}) => {
                    $('#conference-meeting-create').dialog('close');
                });
            } else {
                $('#conference-meeting-create').animate({ scrollTop: 0}, 'slow');
                var empty_fields_str = empty_fields_arr.join('), (');
                this.$set(this.modal_message, "type" , "error");
                this.$set(this.modal_message, "text" , `Bitte füllen Sie folgende Felder aus: (${empty_fields_str})`.toLocaleString());
            }
        },

        cancelAddRoom(event) {
            if (event) {
                event.preventDefault();
            }
            $('button.ui-dialog-titlebar-close').trigger('click');
            this.$store.commit(ROOM_CLEAR);
        },

        showRecording(room) {
            this.$store.dispatch(RECORDING_LIST, room.id).then(({ data }) => {
                if ((data.default && data.default.length) || data.opencast) {
                    this.$store.commit(RECORDING_LIST_SET, data);
                    $('#recording-modal')
                    .dialog({
                        width: '70%',
                        modal: true,
                        title: `Aufzeichnungen für Raum "${room.name}"`.toLocaleString()
                    });
                } else {
                    this.message = {
                        type: 'info',
                        text: `Keine Aufzeichnungen für Raum "${room.name}"`.toLocaleString()
                    };
                }
            });
        },

        deleteRecording(recording) {
            this.$store.dispatch(RECORDING_DELETE, recording);
            this.$store.dispatch(RECORDING_LIST, recording.room_id).then(({ data }) => {
                this.$store.commit(RECORDING_LIST_SET, data);
                if (!data.length) {
                    $('button.ui-dialog-titlebar-close').trigger('click');
                }
                var room = this.rooms_list.find(m => m.meeting_id == recording.room_id);
                if (room) {
                    room.recordings_count = data.length;
                }
            });
        },

        getRoomList() {
            this.$store.dispatch(ROOM_LIST);
            this.$store.dispatch(ROOM_INFO);
        },

        showGuestDialog(room) {
            this.$store.commit(ROOM_CLEAR);
            this.guest_link = '';
            this.modal_message.text = '';

            $('#guest-invitation-modal').data('room', room)
            .dialog({
                width: '50%',
                modal: true,
                title: 'Gast einladen'.toLocaleString()
            });
        },

        generateGuestJoin(event) {
            if (event) {
                event.preventDefault();
            }
            var room = $('#guest-invitation-modal').data('room');

            let view = this;

            if (room && this.room['guest_name']) {
                room.guest_name = this.room['guest_name'];
                this.$store.dispatch(ROOM_JOIN_GUEST, room)
                .then(({ data }) => {
                    if (data.join_url != '') {
                        view.guest_link = data.join_url;
                    }
                    if (data.message) {
                        this.modal_message = data.message;
                    }
                }).catch (({error}) => {
                    $('#guest-invitation-modal').dialog('close');
                });
            }
        },

        cancelGuest(event) {
            if (event) {
                event.preventDefault();
            }
            this.$store.commit(ROOM_CLEAR);
            this.guest_link = '';
            $('#guest-invitation-modal').dialog('close');
        },

        copyGuestLinkClipboard(event) {
            if (event) {
                event.preventDefault();
            }

            let guest_link_element = this.$refs.guestLinkArea;

            if (this.guest_link.trim()) {
                try {
                    guest_link_element.select();
                    document.execCommand("copy");
                    document.getSelection().removeAllRanges();
                    this.modal_message = {
                        type: 'success',
                        text: 'Der Link wurde in die Zwischenablage kopiert.'.toLocaleString()
                    }
                } catch(e) {
                    console.log(e);
                }
            }
        },

        /* setRoomSize(values) {
            setTimeout(() => {
                values.forEach(profile => { //remove all previuos size features
                    profile.value.forEach(profile_content => {
                        if (Object.keys(this.room['features']).includes(profile_content['name'])) {
                            this.$delete(this.room['features'], profile_content['name']);
                        }
                    });
                });
                values.forEach(profile => { //add selected size features
                    if (this.room['features']['roomSizeProfiles'] == profile['name']) {
                        profile.value.forEach(profile_content => {
                            this.$set(this.room['features'], profile_content['name'] , profile_content['value']);
                        });
                    }
                });
            }, 100);
        }, */

        showEditFeatureDialog(room) {
            this.$store.commit(ROOM_CLEAR);
            if (Object.keys(this.config_list[room.driver]['features']).includes('record') && !Object.keys(room.features).includes('giveAccessToRecordings')) {
                var default_feature_obj = this.config_list[room.driver]['features']['record'].find(m => m.name == 'giveAccessToRecordings');
                this.$set(room.features, 'giveAccessToRecordings', ((default_feature_obj) ? default_feature_obj.value : true));
            }
            this.$set(this.room, 'driver_name', room.driver);
            this.$set(this.room, 'features', room.features);
            this.$set(this.room, 'join_as_moderator', room.join_as_moderator);
            this.$set(this.room, 'name', room.name);
            this.$set(this.room, 'server_index', room.server_index);
            this.$set(this.room, 'id', room.id);
            this.$set(this.room, "group_id" , ((Object.keys(room).includes('group_id') && room.group_id != undefined) ? room.group_id : ""));
            this.modal_message = {};


            let options;

            // handle mobile devices
            if (window.innerWidth < 600) {
                options = {
                    width: '100%',
                    modal: true,
                    position: { my: "top", at: "top", of: window },
                    title: 'Raumeinstellung'.toLocaleString()
                }
            } else {
                options = {
                    minWidth: 500,
                    modal: true,
                    position: { my: "top", at: "top", of: window },
                    title: 'Raumeinstellung'.toLocaleString()
                }
            }

            options.maxHeight = $(window).height();

            $('#conference-meeting-create').dialog(options);
        },

        editRoom(event) {
            if (event) {
                event.preventDefault();
            }

            if (!this.validateMaxParticipants()) {
                return;
            }

            this.$store.dispatch(ROOM_UPDATE, this.room)
            .then(({ data }) => {
                this.message = data.message;
                if (data.message.type == 'success') {
                    $('#conference-meeting-create').dialog('close');
                    this.getRoomList();
                } else {
                    $('#conference-meeting-create').animate({ scrollTop: 0}, 'slow');
                    this.modal_message = data.message;
                }
            }).catch (({error}) => {
                $('#conference-meeting-create').dialog('close');
            });
        },

        showMessage(message) {
            this.message = message;
        },

        checkPresets() {
            if (this.room['driver_name'] && this.room['server_index'] 
                && Object.keys(this.config_list[this.room['driver_name']]).includes('server_presets')
                && Object.keys(this.config_list[this.room['driver_name']]['server_presets']).includes(this.room['server_index'])) {
                for (const [size, featues] of  Object.entries(this.config_list[this.room['driver_name']]['server_presets'][this.room['server_index']])) {
                    if (this.room['features'] && this.room['features']['maxParticipants'] && parseInt(this.room['features']['maxParticipants']) >= parseInt(featues['minParticipants'])) {
                        for (const [feature_name, featues_value] of Object.entries(featues)) {
                            if (feature_name != 'minParticipants') {
                                this.$set(this.room['features'], feature_name, featues_value);
                            }
                        }
                    }
                }
            }
        },

        validateMaxParticipants() {
            var isValid = true;
            if (this.room['driver_name'] && this.room['server_index'] && this.room['features'] && this.room['features']['maxParticipants']
             && Object.keys(this.config_list[this.room['driver_name']]).includes('server_defaults')
             && Object.keys(this.config_list[this.room['driver_name']]['server_defaults'][this.room['server_index']]).includes('maxAllowedParticipants')
             && parseInt(this.room['features']['maxParticipants']) > parseInt(this.config_list[this.room['driver_name']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants'])) {

                this.$set(this.room['features'], 'maxParticipants', this.config_list[this.room['driver_name']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants']);
                var maxAllowedParticipants = this.config_list[this.room['driver_name']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants']; 
                this.modal_message.type = 'error';
                this.modal_message.text = `Teilnehmerzahl darf ${maxAllowedParticipants} nicht überschreiten`.toLocaleString();
                $('#conference-meeting-create').animate({ scrollTop: 0}, 'slow');
                isValid = false;
                
            }
            return isValid;
        }
    },

    mounted() {
        store.dispatch(CONFIG_COURSE_READ, CID);
        this.getRoomList();

        this.interval = setInterval(() => {
            this.getRoomList();
        }, 300000);
    },

    beforeDestroy () {
        clearInterval(this.interval)
    }
};
</script>
