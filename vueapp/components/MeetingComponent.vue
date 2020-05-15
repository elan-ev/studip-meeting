<template>
    <div class="meetingcomponent">
        <fieldset>
            <legend>
                <div class="meeting-item-header">
                    <div class="left">
                        {{room.name}}

                        <StudipTooltipIcon v-if="room.details"
                            :text="`Erstellt von: ${room.details['creator']}, ${room.details['date']}` | i18n">
                        </StudipTooltipIcon>

                        <span v-if="info && info.participantCount > 0" class="participants">
                            {{ info.participantCount }} {{ 'Teilnehmende/r aktiv' | i18n }}
                        </span>
                    </div>
                    <div class="right">
                        <a v-if="room.features.record && room.features.record == 'true'" :title=" 'Dieser Raum kann aufgezeichnet werden!' | i18n " >
                            <StudipIcon icon="exclaim-circle" role="status-yellow" size="20"></StudipIcon>
                        </a>
                        <a v-if="course_config.display.editRoom" style="cursor: pointer;"
                            :title=" 'Raumeinstellungen' | i18n "
                            @click.prevent="editFeatures()">
                            <StudipIcon icon="admin" role="clickable" size="20"></StudipIcon>
                        </a>
                        <a v-if="room.recordings_count" style="cursor: pointer;"
                                :title=" typeof room.recordings_count == 'string' ? 'Die vorhandenen Aufzeichnungen auf Opencast' : 'Die vorhandenen Aufzeichnungen' | i18n "
                                :data-badge="typeof room.recordings_count == 'number' ? room.recordings_count : 0"
                                @click.prevent="getRecording()">
                            <StudipIcon :icon="typeof room.recordings_count == 'string' ? 'video2+new' : 'video2'" role="clickable" size="20"></StudipIcon>
                        </a>
                    </div>
                </div>
            </legend>
            <label id="details">
                <div v-if="course_config.display.editRoom">
                    <a style="cursor: pointer;" :title=" room.join_as_moderator == 1 ?
                        'Teilnehmenden nur eingeschränkte Rechte geben' : 'Teilnehmenden Administrationsrechte geben' | i18n "
                        @click.prevent="editRights()">
                        <StudipIcon class="info-icon" :icon="room.join_as_moderator == 1 ? 'lock-unlocked' : 'lock-locked'" role="clickable" size="24"></StudipIcon>
                    </a>
                    <span :id="'rights-info-text-' + room.id" class="">{{ room.join_as_moderator == 1 ?
                                'Teilnehmende haben Administrations-Rechte' :
                                'Teilnehmende haben eingeschränkte Rechte' | i18n  }}
                    </span>
                </div>
                <div v-if="course_config.display.editRoom">
                    <a  style="cursor: pointer;"
                        :title="room.active == 1 ? 'Meeting für Teilnehmende unsichtbar schalten'
                                    : 'Meeting für Teilnehmende sichtbar schalten' | i18n "
                        @click.prevent="editVisibility()">
                        <StudipIcon class="info-icon" :icon="room.active == 1 ? 'visibility-visible' : 'visibility-invisible'"
                            role="clickable" size="24"></StudipIcon>
                    </a>
                    <span :id="'active-info-text-' + room.id" class="">{{ room.active == 1 ? 'Das Meeting ist für die Teilnehmer sichtbar'
                                        : 'Das Meeting ist für die Teilnehmer unsichtbar' | i18n  }}
                    </span>
                </div>
            </label>
            <div class="meeting-item-btns">
                <StudipButton v-if="course_config.display.deleteRoom" icon="" class="delete" type="button" v-on:click="deleteRoom($event)">
                    {{ "Raum löschen" | i18n}}
                </StudipButton>
                <StudipButton v-if="course_config.display.editRoom && room.features && room.features.guestPolicy && room.features.guestPolicy != 'ALWAYS_DENY'"
                    type="button" v-on:click="getGuestInfo()"
                    icon="add"
                >
                    {{ "Einladungslink erstellen" | i18n }}
                </StudipButton>
                <a class="button join" :href="join_url" target="_blank">
                    {{ "Teilnehmen" | i18n}}
                </a>
            </div>
        </fieldset>
    </div>
</template>

<script>
import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";
import { mapGetters } from "vuex";
import store from "@/store";

import {
    ROOM_UPDATE,
    ROOM_DELETE,
} from "@/store/actions.type";

export default {
    name: "MeetingComponent",
    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
    },

    computed: {
        ...mapGetters(['course_config']),

        join_url() {
            return API_URL + '/rooms/join/' + this.room.course_id + '/' + this.room.id;
        }
    },

    props: {
        room: {
            type: Object,
            required: true
        },
        info: {
            type: Object,
            required: false
        }
    },

    data() {
        return {
            interval: null
        }
    },

    methods: {
        editFeatures() {
            this.$emit('getFeatures', this.room);
        },

        editRights() {
            $(`#rights-info-text-${this.room.id}`).removeClass('has-changed');
            this.room.join_as_moderator = this.room.join_as_moderator == 1 ? 0 : 1;
            this.$store.dispatch(ROOM_UPDATE, this.room)
            .then(({ data }) => {
                if (data.message.type == 'error') {
                    this.room.join_as_moderator = !this.room.join_as_moderator;
                    this.$emit('setMessage', data.message);
                } else {
                    $(`#rights-info-text-${this.room.id}`).addClass('has-changed');
                }
            }).catch (({error}) => {
                this.room.join_as_moderator = !this.room.join_as_moderator;
            });
        },

        editVisibility() {
            $(`#active-info-text-${this.room.id}`).removeClass('has-changed');
            this.room.active = this.room.active == 1 ? 0 : 1;
            this.$store.dispatch(ROOM_UPDATE, this.room)
            .then(({ data }) => {
                if (data.message.type == 'error') {
                    this.room.active = !this.room.active;
                    this.$emit('setMessage', data.message);
                } else {
                    $(`#active-info-text-${this.room.id}`).addClass('has-changed');
                }
            }).catch (({error}) => {
                this.room.active = !this.room.active;
            });
        },

        getRecording() {
            this.$emit('getRecording', this.room);
        },

        deleteRoom(event) {
            if (event) {
                event.preventDefault();
            }

            if (confirm('Sind sie sicher, dass sie diesen Raum löschen möchten?')) {
                this.$store.dispatch(ROOM_DELETE, this.room.id)
                .then(({data}) => {
                    this.$emit('setMessage', data.message);
                    if (data.message.type == 'success') {
                        this.$emit('renewRoomList');
                    }
                });
            }
        },

        getGuestInfo() {
            this.$emit('getGuestInfo', this.room);
        },
    }
}
</script>

<style>

</style>
