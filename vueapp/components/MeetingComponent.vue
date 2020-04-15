<template>
    <div>
        <fieldset>
            <legend>
                <div class="meeting-item-header">
                    <div class="left">
                        {{room.name}}  
                        <span v-if="info.participantCount > 0">{{ info.participantCount }} {{ 'Teilnehmende aktiv' | i18n }}</span>
                    </div>
                    <div class="right">
                        <a v-if="info.recording == 'true'" :title=" 'Dieser Raum kann aufgezeichnet werden!' | i18n " >
                            <StudipIcon icon="exclaim-circle" role="status-yellow" size="20"></StudipIcon>
                        </a>
                        <a v-if="course_config.display.editRoom" style="cursor: pointer;" 
                            :title="room.active == 1 ? 'Meeting für Teilnehmende unsichtbar schalten' 
                                        : 'Meeting für Teilnehmende sichtbar schalten' | i18n " 
                            @click.prevent="editVisibility()">
                            <StudipIcon :icon="room.active == 1 ? 'visibility-visible' : 'visibility-invisible'"
                                role="clickable" size="20"></StudipIcon>
                        </a>
                        <a style="cursor: pointer;" :title=" 'Die vorhandenen Aufzeichnungen' | i18n " 
                                :data-badge="room.recordings_count" 
                                @click.prevent="getRecording()">
                            <StudipIcon icon="video2" role="clickable" size="20"></StudipIcon>
                        </a>
                        <a :title=" room.join_as_moderator == 1 ? 
                            'Teilnehmende haben Administrations-Rechte' : 'Teilnehmende haben eingeschränkte Rechte' | i18n " >
                            <StudipIcon :icon="room.join_as_moderator == 1 ? 'lock-unlocked' : 'lock-locked'" role="clickable" size="20"></StudipIcon>
                        </a>
                    </div>
                </div>
            </legend>
            <label id="details">
                <div>
                    <span>{{ room.join_as_moderator == 1 ? 
                                'Teilnehmende haben Administrations-Rechte' : 
                                'Teilnehmende haben eingeschränkte Rechte' | i18n  }}
                    </span>
                </div>
                <div v-if="info.returncode == 'FAILED'">
                    <StudipIcon icon="pause" role="status-yellow" size=28></StudipIcon> 
                    <span>{{ "Dieser Raum läuft derzeit nicht!" | i18n }}</span>
                </div>
                <div v-if="info.running == 'true'">
                    <StudipIcon icon="play" role="accept" size=28></StudipIcon> 
                    <span>{{ "Dieser Raum läuft gerade!" | i18n }}</span>
                </div>
                <span v-if="room.details" class="creator-date">
                    {{ `Erstellt von: ${room.details['creator']}, ${room.details['date']}` | i18n }}
                </span>
                <br>
            </label>
            <div class="meeting-item-btns">
                <StudipButton v-if="course_config.display.deleteRoom" icon="" class="delete" type="button" v-on:click="deleteRoom($event)">
                    {{ "Raum löschen" | i18n}}
                </StudipButton>
                <StudipButton icon="" 
                 class="join"
                 type="button" 
                 v-on:click="joinRoom($event)">
                    {{ "Teilnehmen" | i18n}}
                </StudipButton>
            </div>
        </fieldset>
    </div>
</template>

<script>
import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";
import { mapGetters } from "vuex";
import store from "@/store";

import {
    ROOM_INFO,
    ROOM_UPDATE,
    ROOM_DELETE,
    ROOM_JOIN
} from "@/store/actions.type";

export default {
    name: "MeetingComponent",
    components: {
        StudipButton, 
        StudipIcon,
        MessageBox,
    },
    computed: {
        ...mapGetters(['course_config'])
    },
    props: {
        room: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            interval: null,
            info: {},
        }
    },
    methods: {
        editVisibility() {
            // if (this.info.returncode == 'FAILED') {
            //     return false;
            // }
            this.room.active = this.room.active == 1 ? 0 : 1;
            this.$store.dispatch(ROOM_UPDATE, this.room)
            .then(({ data }) => {
                if (data.message.type == 'error') {
                    this.room.active = !this.room.active;
                    this.message = data.message;
                }
            });
        },
        getRecording() {
            this.$emit('getRecording', this.room);
        },
        deleteRoom(event) {
            if (event) {
                event.preventDefault();
            }
            this.$store.dispatch(ROOM_DELETE, this.room.id)
        },
        joinRoom(event) {
            if (event) {
                event.preventDefault();
            }
            this.$store.dispatch(ROOM_JOIN, this.room.id)
            .then(({ data }) => {
                if (data.join_url != '') {
                    window.open(data.join_url, '_blank');
                    this.room.joins++;
                }
            });
        },
        getInfo() {
            this.$store.dispatch(ROOM_INFO, this.room.id)
            .then(({ data }) => {
                this.info = data.info;
            });
        }
    },
    mounted() {
        this.getInfo();
        this.interval = setInterval(() => {
            this.getInfo();
        }, 60000);
    },
    beforeDestroy () {
       clearInterval(this.interval)
    }
}
</script>

<style>

</style>