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
                <StudipButton type="button"  @click="createNewRoom">
                    {{ "Neuer Raum" | i18n}}
                </StudipButton>
            </MessageBox>

            <MessageBox v-if="!rooms_checked" type="warning">
                {{ "Raumliste wird geladen..." | i18n }}
            </MessageBox>

            <p>
                <StudipButton type="button" icon="add" v-if="rooms_list.length && config && course_config.display.addRoom"
                    @click="createEditRoom = true">
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

        <!-- dialogs -->
        <MeetingAdd v-if="createEditRoom"
            @done="roomEditDone"
            @cancel="createEditRoom = false"
        />

        <MeetingRecordings v-if="showRecordings"
            :room="showRecordings"
            @cancel="showRecordings = false"
        />
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
import MeetingAdd from "@/components/MeetingAdd";
import MeetingRecordings from "@/components/MeetingRecordings";

import {
    CONFIG_COURSE_READ, FEEDBACK_SUBMIT,
    ROOM_LIST, ROOM_READ, ROOM_UPDATE, ROOM_CREATE,
    ROOM_JOIN, ROOM_JOIN_GUEST, ROOM_INFO,
    RECORDING_LIST, RECORDING_SHOW, RECORDING_DELETE,
} from "@/store/actions.type";

import {
    ROOM_CLEAR, RECORDING_LIST_SET,
    FEEDBACK_CLEAR, FEEDBACK_INIT
} from "@/store/mutations.type";

export default {
    name: "Course",

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
        MeetingStatus,
        MeetingComponent,
        MeetingAdd,
        MeetingRecordings
    },

    computed: {
        ...mapGetters([
            'config', 'room', 'rooms_list', 'rooms_info', 'rooms_checked',
            'course_config', 'feedback', 'network_types', 'course_groups'
        ]),

        config_list: function() {
            return this.config;
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
            searchtext: '',
            createEditRoom: false,
            showRecordings: false
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

        showEditFeatureDialog(room) {
            this.$store.commit(ROOM_CLEAR);

            // check, if there are any features for this driver at all!
            if (this.config_list[room.driver]['features'] !== undefined) {
                if (Object.keys(this.config_list[room.driver]['features']).includes('record')
                    && !Object.keys(room.features).includes('giveAccessToRecordings')
                ) {
                    var default_feature_obj = this.config_list[room.driver]['features']['record']
                        .find(m => m.name == 'giveAccessToRecordings');

                    this.$set(room.features, 'giveAccessToRecordings', ((default_feature_obj)
                        ? default_feature_obj.value
                        : true)
                    );
                }
            }

            this.$set(this.room, 'driver_name', room.driver);
            this.$set(this.room, 'features', room.features);
            this.$set(this.room, 'join_as_moderator', room.join_as_moderator);
            this.$set(this.room, 'name', room.name);
            this.$set(this.room, 'server_index', room.server_index);
            this.$set(this.room, 'id', room.id);
            this.$set(this.room, "group_id" , ((Object.keys(room).includes('group_id') && room.group_id != undefined) ? room.group_id : ""));
            this.modal_message = {};

            this.createEditRoom = true;
        },

        showMessage(message) {
            this.message = message;
        },

        showRecording(room) {
            this.showRecordings = room;
        },

        createNewRoom() {
            this.$store.commit(ROOM_CLEAR);
            this.createEditRoom = true;
        },

        roomEditDone() {
            this.createEditRoom = false;
            this.getRoomList();
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
