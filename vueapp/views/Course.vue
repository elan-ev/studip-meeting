<template>
    <div>
        <MessageBox v-if="message" :type="message.type" @hide="message = ''" :timer="3000">
            {{ message.text }}
        </MessageBox>
        <form class="default conference-meeting">
            <fieldset>
                <legend>
                    {{ (course_config.title ? course_config.title : "Meetings") | i18n }}
                    <a v-if="config" style="cursor: pointer;" :title=" 'Raum hinzufügen' | i18n " 
                        @click.prevent="showAddMetting()">
                        <StudipIcon icon="add" role="clickable" ></StudipIcon>
                    </a>
                </legend>
                <fieldset v-for="(room, index) in rooms_list" :key="index">
                    <legend>
                        <div class="meeting-item-header">
                            <div class="left">
                                {{room.name}}  
                                <span v-if="room.joins">{{ room.joins }} {{ 'Teilnehmende aktiv' | i18n }}</span>
                            </div>
                            <div class="right">
                                <a style="cursor: pointer;" 
                                 :title="room.active == 1 ? 'Meeting für Teilnehmende sichtbar schalten' 
                                                : 'Meeting für Teilnehmende unsichtbar schalten' | i18n " 
                                 @click.prevent="editVisibility(room)">
                                    <StudipIcon :icon="room.active == 1 ? 'visibility-visible' : 'visibility-invisible'"
                                     role="clickable" size="20"></StudipIcon>
                                </a>
                                <a style="cursor: pointer;" :title=" 'Die vorhandenen Aufzeichnungen' | i18n " 
                                        :data-badge="Object.keys(room.aufzeichnungen).length" 
                                        @click.prevent="showRecording()">
                                    <StudipIcon icon="video2" role="clickable" size="20"></StudipIcon>
                                </a>
                                <a style="cursor: pointer;" 
                                 :title=" room.join_as_moderator == 1 ? 
                                   'Teilnehmende haben Administrations-Rechte' : 'Teilnehmende haben eingeschränkte Rechte' | i18n " 
                                 @click.prevent="editRights(room)">
                                    <StudipIcon :icon="room.join_as_moderator == 1 ? 'key+accept' : 'key+decline'" role="clickable" size="20"></StudipIcon>
                                </a>
                            </div>
                        </div>
                    </legend>
                    <label id="details">
                        <span>{{ room.join_as_moderator == 1 ? 
                                   'Teilnehmende haben Administrations-Rechte' : 
                                   'Teilnehmende haben eingeschränkte Rechte' | i18n  }}
                        </span>
                        <!-- <br>
                        <StudipIcon icon="video2" role="attention" size=36></StudipIcon> 
                        <span class="red">{{ "Dieser Raum wird momentan aufgezeichnet! ( Q:Where to get data for live recording? )" | i18n }}</span> -->
                        <br>
                        <span v-if="room.details" class="creator-date">
                            {{ `Erstellt von: ${room.details['creator']}, ${room.details['date']}` | i18n }}
                        </span>
                        <br>
                    </label>
                    <div class="meeting-item-btns">
                        <StudipButton icon="" class="delete" type="button" v-on:click="deleteRoom($event, room)">
                            {{ "Raum löschen" | i18n}}
                        </StudipButton>
                        <StudipButton icon="" class="join" type="button" v-on:click="joinRoom($event, room)">
                            {{ "Teilnehmen" | i18n}}
                        </StudipButton>
                    </div>
                </fieldset>
            </fieldset>
        </form>
        <div v-if="config" id="conference-meeting-create" style="display: none">
            <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                {{ modal_message.text }}
            </MessageBox>
            <MessageBox v-else-if="room['driver_name'] && !Object.keys(config[room['driver_name']]['servers']).length"
                 type="error">
                {{ "Es gibt keine Server an diese Konferenzsystem, bitte wählen Sie andere Konferenzsystem" | i18n }}
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
                        <select id="driver_name" size="1" v-model="room['driver_name']">
                            <option value="" disabled> {{ "Bitte wählen Sie eine Konferenzsystem aus" | i18n }} </option>
                            <option v-for="(driver_config, driver_name) in config" :key="driver_name" 
                                    :value="driver_name">
                                    {{ driver_config['display_name'] }}
                            </option>
                        </select>
                    </label>
                    <label v-if="room['driver_name'] 
                                && Object.keys(config[room['driver_name']]['servers']).length">
                        <span class="required">{{ "Verfügbare Server" | i18n }}</span>
                        <select id="server_index" size="1" v-model="room['server_index']">
                            <option value="" disabled> {{ "Bitte wählen Sie eine Server aus" | i18n }} </option>
                            <option v-for="(server_config, server_index) in config[room['driver_name']]['servers']" :key="server_index" 
                                    :value="'' + server_index">
                                    {{ (server_index + 1) + '-' + server_config['url'] }}
                            </option>
                        </select>
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
    ROOM_LIST,
    ROOM_READ,
    ROOM_UPDATE,
    ROOM_CREATE,
    ROOM_DELETE,
    ROOM_JOIN
} from "@/store/actions.type";

import {
    ROOM_CLEAR,
} from "@/store/mutations.type";

export default {
    name: "Course",
    components: {
        StudipButton, 
        StudipIcon,
        MessageBox,
    },
    computed: {
        ...mapGetters(['config', 'room', 'rooms_list', 'course_config'])
    },
    data() {
        return {
            message: null,
            modal_message: {}
        }
    },
    methods: {
        showAddMetting() {
            this.modal_message = {};
            this.$store.commit(ROOM_CLEAR);
            $('#conference-meeting-create')
            .dialog({
                width: '50%',
                modal: true,
                title: 'Raum hinzufügen'.toLocaleString()
            });
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
        editVisibility(room) {
            room.active = room.active == 1 ? 0 : 1;
            this.$store.dispatch(ROOM_UPDATE, room)
            .then(({ data }) => {
                if (data.message.type == 'error') {
                    room.active = !room.active;
                    this.message = data.message;
                }
            });
        },
        showRecording() {
            alert('Aufzeichnungen must be shown here!');
        },
        editRights(room) {
            room.join_as_moderator = room.join_as_moderator == 1 ? 0 : 1;
            this.$store.dispatch(ROOM_UPDATE, room)
            .then(({ data }) => {
                if (data.message.type == 'error') {
                    room.join_as_moderator = !room.join_as_moderator;
                    this.message = data.message;
                }
            });
        },
        deleteRoom(event, room) {
            if (event) {
                event.preventDefault();
            }
            this.$store.dispatch(ROOM_DELETE, room.id)
        },
        joinRoom(event, room) {
            if (event) {
                event.preventDefault();
            }
            this.$store.dispatch(ROOM_JOIN, room.id)
            .then(({ data }) => {
                if (data.join_url != '') {
                    window.open(data.join_url, '_blank');
                    room.joins++;
                }
            });
        }
    },
    mounted() {
        store.dispatch(CONFIG_LIST_READ, true);
        store.dispatch(ROOM_LIST);
    }
};
</script>
