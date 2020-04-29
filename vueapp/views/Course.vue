<template>
    <div>
        <MessageBox v-if="message" :type="message.type" @hide="message = ''" :timer="3000">
            {{ message.text }}
        </MessageBox>

        <MessageBox v-if="!rooms_list.length && config && course_config.display.addRoom" :type="'info'">
            {{ "Bisher existieren keine Meeting-Räume für diese Veranstaltung. Möchten Sie einen anlegen?" | i18n }}
            <br>
            <StudipButton type="button"  @click="showAddMeeting()">
                {{ "Neuer Raum" | i18n}}
            </StudipButton>
        </MessageBox>

        <StudipButton type="button" v-if="rooms_list.length && config && course_config.display.addRoom"
            @click="showAddMeeting()">
            {{ 'Raum hinzufügen' | i18n }}
        </StudipButton>

        <form class="default conference-meeting" v-if="rooms_list.length">
                <MeetingComponent v-for="(room, index) in rooms_list" :key="index" :room="room" v-on:getRecording="showRecording"
                     v-on:renewRoomList="getRoomList" v-on:getGuestInfo="showGuestDialog" v-on:getFeatures="showEditFeatureDialog"></MeetingComponent>
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
                        <span class="required">{{ "Name des Raums" | i18n }}</span>
                        <input type="text" v-model.trim="room['name']" id="name">
                    </label>
                    <label>
                        <input type="checkbox"
                        id="join_as_moderator"
                        true-value="1"
                        false-value="0"
                        v-model="room['join_as_moderator']">
                        {{ "Sollen Teilnehmende Administrationsrechte erhalten?" | i18n }}
                    </label>
                    <label>
                        <span class="required">{{ "Konferenzsystem" | i18n }}</span>
                        <select id="driver_name" size="1" v-model="room['driver_name']" @change.prevent="handleDriverDefaults()" :disabled="Object.keys(config_list).length == 1">
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
                        <div style="margin: 15px 0;" v-for="(feature, index) in config_list[room['driver_name']]['features']['create']" :key="index">
                            <span class="">
                                {{ feature['display_name'] | i18n }}
                            </span>
                            <div class="" v-if="feature['value'] && typeof feature['value'] === 'object' && feature['name'] != 'roomSizeProfiles'">
                                <select :id="feature['name']" size="1" v-model.trim="room['features'][feature['name']]">
                                    <option v-for="(fvalue, findex) in feature['value']" :key="findex"
                                            :value="findex">
                                            {{ fvalue | i18n }}
                                    </option>
                                </select>
                            </div>
                            <div id="meeting-create-feature-size" class="col-6" v-else-if="feature['name'] == 'roomSizeProfiles'">
                                <select :id="feature['name']" size="1" @change="setRoomSize(feature['value'])" v-model.trim="room['features'][feature['name']]">
                                    <option v-for="(fvalue, findex) in feature['value']" :key="findex"
                                            :value="fvalue['name']">
                                            {{ fvalue['display_name'] | i18n }}
                                    </option>
                                </select>
                                <div style="margin-top: 15px;" v-for="(fvalue, findex) in feature['value']" :key="findex">
                                    <label v-for="(fsvalue, fsindex) in fvalue['value']" :key="fsindex" v-show="room['features'][feature['name']] == fvalue['name']">
                                        <div v-if="typeof fsvalue['value'] != 'boolean'">
                                            <span class="">{{ fsvalue['display_name'] | i18n }}</span>
                                            <input type="text" v-model.trim="room['features'][fsvalue['name']]"
                                                :placeholder="fsvalue['value'] ? fsvalue['value'] : ''" :id="fsvalue['name']">
                                        </div>

                                        <div v-else>
                                            <input  type="checkbox"
                                                true-value="true"
                                                false-value="false"
                                                v-model="room['features'][fsvalue['name'] ]">
                                                {{ fsvalue['display_name'] | i18n }}
                                        </div>

                                    </label>
                                </div>
                            </div>
                            <div class="" v-else>
                                <input type="text" v-model.trim="room['features'][feature['name']]" :placeholder="feature['value'] ? feature['value'] : ''" :id="feature['name']">
                            </div>
                        </div>
                    </label>
                    <div>
                        <StudipButton v-if="room['id']" icon="accept" type="button" v-on:click="editRoom($event)">
                            {{ "Änderungen speichern" | i18n}}
                        </StudipButton>
                        <StudipButton v-else icon="accept" type="button" v-on:click="addRoom($event)">
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
            <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''" :timer="2000">
                {{ modal_message.text }}
            </MessageBox>
            <form class="default" >
                <fieldset>
                    <label>
                        <span class="required">{{ "Gastname" | i18n }}</span>
                        <input type="text" v-model.trim="room['guest_name']" id="guestname">
                    </label>
                    <label id="guest_link_label" style="display: none;">
                        <span>{{ "Link" | i18n }}</span>
                        <StudipTooltipIcon :text="'Bitte versuchen Sie, dem Gast den Link zu geben.' | i18n" :important="true"></StudipTooltipIcon>
                        <textarea id="guest_link" cols="30" rows="5"></textarea>
                        <StudipButton icon="add" type="button" v-on:click="copyGuestLinkClipboard($event)">
                            {{ "In Clipboard kopieren" | i18n}}
                        </StudipButton>
                    </label>
                    <div>
                        <StudipButton id="generate_link_btn" icon="accept" type="button" v-on:click="generateGuestJoin($event)">
                            {{ "Einladungslink erstellen" | i18n }}
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
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
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
        StudipTooltipIcon,
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
                height: ($(window).height() * 0.8),
                width: '70%',
                modal: true,
                title: 'Raum hinzufügen'.toLocaleString()
            });

            this.setDriver();
        },

        setDriver() {
            if (Object.keys(this.config_list).length == 1) {
                this.$set(this.room, "driver_name" , Object.keys(this.config_list)[0]);
                this.handleDriverDefaults();
            }
        },

        handleDriverDefaults() {
            //set default features
            this.$set(this.room, "features" , {});
            if (Object.keys(this.config_list[this.room['driver_name']]).includes('features')) {
                //set size feature
                if (Object.keys(this.config_list[this.room['driver_name']]['features']['create']).length) {
                    var roomSizeProfiles = this.config_list[this.room['driver_name']]['features']['create'].find(f => f.name == 'roomSizeProfiles');
                    if (roomSizeProfiles) {
                        var smallProfile = roomSizeProfiles.value.find(s => s.name == 'small');
                        if (smallProfile) {
                            this.$set(this.room['features'], "roomSizeProfiles" , "small");
                            smallProfile.value.forEach(content => {
                                this.$set(this.room['features'], content.name , content.value);
                            });
                        }
                    }

                    // set all selects to first entry
                    for (let index in this.config_list[this.room['driver_name']]['features']['create']) {
                        let feature = this.config_list[this.room['driver_name']]['features']['create'][index];
                        console.log(typeof feature.value, feature);

                        if (typeof feature.value === 'object' && !Array.isArray(feature.value)) {
                            this.room['features'][feature['name']] = Object.keys(feature['value'])[0];
                        }
                    }
                }


            }
            //mandatory server selection when there is only one server
            if (this.room['driver_name'] && Object.keys(this.config_list[this.room['driver_name']]['servers']).length == 1) {
                this.$set(this.room, "server_index" , "0");
            }
        },
        addRoom(event) {
            if (event) {
                event.preventDefault();
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
                }).catch (error => {
                    $('#conference-meeting-create').animate({ scrollTop: 0}, 'slow');
                    this.$set(this.modal_message, "type" , "error");
                    this.$set(this.modal_message, "text" , 'System Error: please contact system administrator!');
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
            if (typeof room.recordings_count == 'string') { //opencast url
                window.open(room.recordings_count, '_blank');
            } else { //default
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
            }
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
            if (event) {
                event.preventDefault();
            }
            var room = $('#guest-invitation-modal').data('room');
            if (room && this.room['guest_name']) {
                if ($('#guest_link').text().trim()) {
                    this.$set(this.room, 'guest_name', '');
                    $('#guest_link').text('');
                    $('#generate_link_btn').text('Einladungslink erstellen'.toLocaleString());
                } else {
                    room.guest_name = this.room['guest_name'];
                    this.$store.dispatch(ROOM_JOIN_GUEST, room)
                    .then(({ data }) => {
                        if (data.join_url != '') {
                            $('#guest_link').text(data.join_url);
                            $('#guest_link_label').show();
                            $('#generate_link_btn').text('Neuen Gast einladen'.toLocaleString());
                        }
                    });
                }
            }
        },
        cancelGuest(event) {
            if (event) {
                event.preventDefault();
            }
            this.$store.commit(ROOM_CLEAR);
            $('#guest_link').text('');
            $('#guest-invitation-modal').dialog('close');
        },
        copyGuestLinkClipboard(event) {
            if (event) {
                event.preventDefault();
            }
            let guest_link_element = document.getElementById('guest_link');
            var link = guest_link_element.textContent;
            if (link.trim()) {
                try {
                    guest_link_element.select();
                    document.execCommand("copy");
                    document.getSelection().removeAllRanges();
                    this.modal_message = {
                        type: 'success',
                        text: 'Der Link wurde in Clipboard kopiert'.toLocaleString()
                    }
                } catch(e) {
                    console.log(e);
                }
                $('#guest_link').blur();
            }
        },
        setRoomSize(values) {
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
        },
        showEditFeatureDialog(room) {
            this.$store.commit(ROOM_CLEAR);
            this.$set(this.room, 'driver_name', room.driver);
            this.$set(this.room, 'features', room.features);
            this.$set(this.room, 'join_as_moderator', room.join_as_moderator);
            this.$set(this.room, 'name', room.name);
            this.$set(this.room, 'server_index', room.server_index);
            this.$set(this.room, 'id', room.id);
            this.modal_message = {};
            $('#conference-meeting-create')
            .dialog({
                height: ($(window).height() * 0.8),
                width: '70%',
                modal: true,
                title: 'Raumeinstellung'.toLocaleString()
            });
        },
        editRoom() {
            this.$store.dispatch(ROOM_UPDATE, this.room)
            .then(({ data }) => {
                console.log(data.message);
                this.message = data.message;
                if (data.message.type == 'success') {
                    $('#conference-meeting-create').dialog('close');
                    this.getRoomList();
                } else {
                    $('#conference-meeting-create').animate({ scrollTop: 0}, 'slow');
                    this.modal_message = data.message;
                }
            });
        },
    },
    mounted() {
        store.dispatch(CONFIG_LIST_READ, true);
        this.getRoomList();
    }
};
</script>
