<template>
    <div>
        <MessageBox v-if="message" :type="message.type" @hide="message = ''" :timer="3000">
            {{ message.text }}
        </MessageBox>

        <form class="default conference-meeting">
            <fieldset>
                <legend>
                    {{ (course_config.title ? course_config.title : "Meetings") | i18n }}
                    <a v-if="config && course_config.display.addRoom" style="cursor: pointer;" :title=" 'Raum hinzufügen' | i18n "
                        @click.prevent="showAddMeeting()">
                        <StudipIcon icon="add" role="clickable" ></StudipIcon>
                    </a>
                </legend>

                <MessageBox v-if="!rooms_list.length" :type="'info'">
                    {{ "Bisher existieren keine Meeting-Räume für diese Veranstaltung. Möchten Sie einen anlegen?" | i18n }}
                    <br>
                    <StudipButton type="button"  @click="showAddMeeting()">
                        {{ "Neuer Raum" | i18n}}
                    </StudipButton>
                </MessageBox>
                <MeetingComponent v-for="(room, index) in rooms_list" :key="index" :room="room" v-on:getRecording="showRecording"
                     v-on:renewRoomList="getRoomList" v-on:getGuestInfo="showGuestDialog"></MeetingComponent>
            </fieldset>
        </form>

        <div v-if="config_list" id="conference-meeting-create" style="display: none">
            <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                {{ modal_message.text }}
            </MessageBox>
            <MessageBox v-else-if="room['driver_name'] && !Object.keys(config_list[room['driver_name']]['servers']).length"
                 type="error">
                {{ "Es gibt keine Server für dieses Konferenzsystem, bitte wählen Sie ein anderes Konferenzsystem" | i18n }}
            </MessageBox>

            <form class="default" >
                <fieldset>
                    <label>
                        <span class="required">{{ "Name der Raums" | i18n }}</span>
                        <input type="text" v-model.trim="room['name']" id="name">
                    </label>
                    <label>
                        <input type="checkbox"
                        id="join_as_moderator"
                        true-value="1"
                        false-value="0"
                        v-model="room['join_as_moderator']">
                        {{ "Teilnehmende haben Administrationsrechte" | i18n }}
                    </label>
                    <label>
                        <span class="required">{{ "Konferenzsystem" | i18n }}</span>
                        <select id="driver_name" size="1" v-model="room['driver_name']" @change.prevent="setServer()" :disabled="Object.keys(config_list).length == 1">
                            <option value="" disabled> {{ "Bitte wählen Sie ein Konferenzsystem aus" | i18n }} </option>
                            <option v-for="(driver_config, driver_name) in config_list" :key="driver_name"
                                    :value="driver_name">
                                    {{ driver_config['display_name'] }}
                            </option>
                        </select>
                    </label>

                    <label v-if="room['driver_name']
                                && Object.keys(config_list[room['driver_name']]['servers']).length > 1">
                        <span class="required">{{ "Verfügbare Server" | i18n }}</span>
                        <select id="server_index" size="1" v-model="room['server_index']"
                            :disabled="Object.keys(config_list[room['driver_name']]['servers']).length == 1">
                            <option value="" disabled> {{ "Bitte wählen Sie einen Server aus" | i18n }} </option>
                            <option v-for="(server_config, server_index) in config_list[room['driver_name']]['servers']" :key="server_index"
                                    :value="'' + server_index">
                                    {{ (server_index + 1) + '-' + server_config['url'] }}
                            </option>
                        </select>
                    </label>

                    <label v-if="room['driver_name'] && Object.keys(config_list[room['driver_name']]).includes('features')
                                && Object.keys(config_list[room['driver_name']]['features']['create']).length">
                        <strong>{{ "Zusätzliche Funktionen" | i18n }}</strong>
                        <div v-for="(feature, index) in config_list[room['driver_name']]['features']['create']" :key="index">
                            <div class="">
                                {{ feature['display_name'] | i18n }}
                            </div>
                            <div class="" v-if="Array.isArray(feature['value'])">
                                <select :id="feature['name']" size="1" v-model.trim="room['features'][feature['name']]">
                                    <option v-for="(fvalue, findex) in feature['value']" :key="findex" :selected="findex = 0"
                                            :value="fvalue">
                                            {{ fvalue }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-6" v-else>
                                <input type="text" v-model.trim="room['features'][feature['name']]" id="name">
                            </div>
                        </div>
                    </label>
                    <div>
                        <StudipButton icon="accept" type="button" v-on:click="addRoom($event)">
                            {{ "Raum erstellen" | i18n}}
                        </StudipButton>
                        <StudipButton icon="cancel" type="button" v-on:click="cancelAddRoom($event)">
                            {{ "Abbrechen" | i18n}}
                        </StudipButton>
                    </div>
                </fieldset>
            </form>
        </div>
        <div id="recording-modal" style="display: none;">
            <table v-if="Object.keys(recording_list).length" class="default collapsable">
                <thead>
                    <tr>
                        <th>{{ "Datum" | i18n }}</th>
                        <th>{{ "Aktionen" | i18n }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(recording, index) in recording_list" :key="index">
                        <td style="width: 60%">{{ recording['startTime'] }}</td>
                        <td style="width: 40%">
                            <div style="display: inline-block;width:80%;">
                                <div v-if="Array.isArray(recording['playback']['format'])" style="display: flex; flex-direction: column; ">
                                    <a v-for="(format, index) in recording['playback']['format']" :key="index"
                                    class="meeting-recording-url" target="_blank"
                                    :href="format['url']">
                                        {{ `Aufzeichnung ansehen (${format['type']})`  | i18n}}
                                    </a>
                                </div>
                                <div v-else>
                                    <a class="meeting-recording-url" target="_blank"
                                    :href="recording['playback']['format']['url']">
                                        {{ `Aufzeichnung ansehen`  | i18n}}
                                    </a>
                                </div>
                            </div>
                            <div style="display: inline-block;width:15%; text-align: right;">
                                <a style="cursor: pointer;" @click.prevent="deleteRecording(recording)">
                                    <StudipIcon icon="trash" role="attention"></StudipIcon>
                                </a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div id="guest-invitation-modal" style="display: none;">
            <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                {{ modal_message.text }}
            </MessageBox>
            <form class="default" >
                <fieldset>
                    <label>
                        <span class="required">{{ "Gastname" | i18n }}</span>
                        <input type="text" v-model.trim="room['guest_name']" id="name">
                    </label>
                    <label id="guest_link_label" style="display: none;">
                        <span>{{ "Link" | i18n }}</span>
                        <textarea id="guest_link" cols="30" rows="5"></textarea>
                    </label>
                    <div>
                        <StudipButton icon="accept" type="button" v-on:click="generateGuestJoin($event)">
                            {{ "Einladungslink erstellen" | i18n}}
                        </StudipButton>
                        <StudipButton icon="cancel" type="button" v-on:click="cancelGuest($event)">
                            {{ "Abbrechen" | i18n}}
                        </StudipButton>
                    </div>
                </fieldset>
            </form>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";
import MeetingStatus from "@/components/MeetingStatus";
import MeetingComponent from "@/components/MeetingComponent";

import {
    CONFIG_LIST_READ,
    ROOM_LIST,
    ROOM_READ,
    ROOM_UPDATE,
    ROOM_CREATE,
    ROOM_DELETE,
    ROOM_JOIN,
    RECORDING_LIST,
    RECORDING_SHOW,
    RECORDING_DELETE,
    ROOM_JOIN_GUEST
} from "@/store/actions.type";

import {
    ROOM_CLEAR,
    RECORDING_LIST_SET
} from "@/store/mutations.type";

export default {
    name: "Course",
    components: {
        StudipButton,
        StudipIcon,
        MessageBox,
        MeetingStatus,
        MeetingComponent
    },
    computed: {
        ...mapGetters(['config', 'room', 'rooms_list', 'course_config', 'recording_list', 'recording']),
        config_list: function() {
            let config_list = {};

            for (var driver in this.config) {
                if (this.config[driver].enable == 1) {
                    config_list[driver] = this.config[driver];
                }
            }

            return config_list;
        }
    },
    data() {
        return {
            message: null,
            modal_message: {}
        }
    },
    methods: {
        showAddMeeting() {
            this.modal_message = {};
            this.$store.commit(ROOM_CLEAR);
            $('#conference-meeting-create')
            .dialog({
                width: '50%',
                modal: true,
                title: 'Raum hinzufügen'.toLocaleString()
            });

            this.setDriver();
        },

        setDriver() {
            if (Object.keys(this.config_list).length == 1) {
                this.$set(this.room, "driver_name" , Object.keys(this.config_list)[0]);
            }
        },

        setServer() {
            //mandatory server selection when there is only one server
            if (this.room['driver_name'] && Object.keys(this.config_list[this.room['driver_name']]['servers']).length == 1) {
                this.$set(this.room, "server_index" , "0");
            }
        },
        setServer() {
            //mandatory server selection when there is only one server
            if (this.room['driver_name'] && Object.keys(this.config[this.room['driver_name']]['servers']).length == 1) {
                this.$set(this.room, "server_index" , "0");
            }
        },
        addRoom(event) {
            if (event) {
                event.preventDefault();
            }
            var empty_fields_arr = [];
            for (var key in this.room) {
                if (key != 'join_as_moderator' && this.room[key] == '' ) {
                    $(`#${key}`).prev().is(':visible') ? empty_fields_arr.push($(`#${key}`).prev().text()) : '';
                }
            }
            if ( !empty_fields_arr.length ) {
                this.modal_message = {};
                this.$store.dispatch(ROOM_CREATE, this.room)
                .then(({ data }) => {
                    this.message = data.message;
                    if (this.message.type == 'error') {
                        this.$set(this.modal_message, "type" , "error");
                        this.$set(this.modal_message, "text" , this.message.text);
                    } else {
                        $('button.ui-dialog-titlebar-close').trigger('click');
                        store.dispatch(ROOM_LIST);
                        setTimeout(function() {
                            this.message = null;
                        }, 3000);
                    }
                }).catch (error => {
                    this.$set(this.modal_message, "type" , "error");
                    this.$set(this.modal_message, "text" , 'System Error: please contact system administrator!');
                });
            } else {
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
                if (data.length) {
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
        },
        showGuestDialog(room) {
            this.$store.commit(ROOM_CLEAR);
            $('#guest_link').text('');
            $('#guest_link_label').hide();
            $('#guest-invitation-modal').data('room', room)
            .dialog({
                width: '50%',
                modal: true,
                title: 'Gast einladen'.toLocaleString()
            });
        },
        generateGuestJoin(event) {
            var room = $('#guest-invitation-modal').data('room');
            if (room && this.room['guest_name']) {
                room.guest_name = this.room['guest_name'];
                this.$store.dispatch(ROOM_JOIN_GUEST, room)
                .then(({ data }) => {
                    if (data.join_url != '') {
                        $('#guest_link').text(data.join_url);
                        $('#guest_link_label').show();
                    }
                });
            }
        },
        cancelGuest(event) {
            if (event) {
                event.preventDefault();
            }
            this.$store.commit(ROOM_CLEAR);
            $('#guest_link').text('');
            $('#guest-invitation-modal').dialog('close');
        }
    },
    mounted() {
        store.dispatch(CONFIG_LIST_READ, true);
        this.getRoomList();
    }
};
</script>
