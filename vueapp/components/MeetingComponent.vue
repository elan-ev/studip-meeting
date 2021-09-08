<template>
    <div class="meetingcomponent">
        <fieldset>
            <legend>
                <div class="meeting-item-header"
                    :class="{
                        'meeting-disabled' : !room.enabled
                    }"
                >
                    <div class="left">
                        {{room.name}}
                        <StudipTooltipIcon v-if="room.details"
                                            :text="`${room.details['creator']}, ${room.details['date']}`">
                        </StudipTooltipIcon>

                        <template v-if="room.features && room.features.maxParticipants">
                            <span v-if="info && info.participantCount > 0" class="participants"
                                v-translate="{
                                    count: info.participantCount,
                                    max: room.features.maxParticipants
                                }"
                            >
                                %{ count }/%{ max } Teilnehmende aktiv
                            </span>
                            <span v-else class="participants"
                                v-translate="{
                                    max: room.features.maxParticipants
                                }"
                            >
                                Maximale Teilnehmerzahl: %{ max }
                            </span>
                        </template>
                    </div>
                    <div class="right">
                        <StudipTooltipIcon v-if="room.features && room.features.record && room.features.record == 'true' && !room.record_not_allowed"
                                    :text="$gettext('Bitte beachten Sie, dass dieser Raum aufgezeichnet wird!')"
                                    :badge="true"
                                    >
                            <StudipIcon icon="span-full" role="attention" size="11"></StudipIcon> {{'Rec'}}
                        </StudipTooltipIcon>
                        <a v-if="room.has_recordings" style="cursor: pointer;"
                                :title="$gettext('Die vorhandenen Aufzeichnungen')"
                                @click.prevent="getRecording()">
                            <StudipIcon icon="video2" role="clickable" size="20"></StudipIcon>
                        </a>
                        <a v-if="course_config.display.editRoom" style="cursor: pointer;"
                            :title="$gettext('Raumeinstellungen')"
                            @click.prevent="editFeatures()">
                            <StudipIcon icon="admin" role="clickable" size="20"></StudipIcon>
                        </a>
                        <a style="cursor: pointer;"
                            :title="$gettext('Schreiben Sie ein Feedback')"
                            @click.prevent="writeFeedback()">
                            <StudipIcon icon="support" role="clickable" size="22"></StudipIcon>
                        </a>
                        <a v-if="course_config.display.deleteRoom" style="cursor: pointer;"
                            :title="$gettext('Raum löschen')"
                            @click.prevent="deleteRoom($event)">
                            <StudipIcon icon="trash" role="clickable" size="20"></StudipIcon>
                        </a>
                    </div>
                </div>
            </legend>
            <label id="details">

                <div v-if="course_config.display.editRoom && room.is_default == 1">
                    <StudipIcon class="info-icon" icon="star"
                            role="clickable" size="24"></StudipIcon>
                    <span v-text="$gettext('Dieser Raum is Default')"></span>
                </div>

                <div v-if="course_config.display.editRoom">
                    <a style="cursor: pointer;" :title=" room.join_as_moderator == 1 ?
                        $gettext('Teilnehmenden nur eingeschränkte Rechte geben')
                        : $gettext('Teilnehmenden Administrationsrechte geben')"
                        @click.prevent="editRights()">
                        <StudipIcon class="info-icon" :icon="room.join_as_moderator == 1 ? 'lock-unlocked' : 'lock-locked'" role="clickable" size="24"></StudipIcon>
                    </a>
                    <span :id="'rights-info-text-' + room.id" class="">{{ room.join_as_moderator == 1 ?
                                $gettext('Teilnehmende haben Moderationsrechte')
                                : $gettext('Teilnehmende haben eingeschränkte Rechte') }}
                    </span>
                </div>

                <div v-if="course_config.display.editRoom">
                    <a  style="cursor: pointer;"
                        :title="room.active == 1 ?
                            $gettext('Meeting für Teilnehmende unsichtbar schalten')
                            : $gettext('Meeting für Teilnehmende sichtbar schalten') "
                        @click.prevent="editVisibility()">
                        <StudipIcon class="info-icon" :icon="room.active == 1 ? 'visibility-visible' : 'visibility-invisible'"
                            role="clickable" size="24"></StudipIcon>
                    </a>
                    <span :id="'active-info-text-' + room.id" class="">{{ room.active == 1 ?
                        $gettext('Das Meeting ist für die Teilnehmer sichtbar')
                        : $gettext('Das Meeting ist für die Teilnehmer unsichtbar') }}
                    </span>
                </div>

                <div v-if="course_config.display.editRoom && room.group_id">
                    <StudipIcon class="info-icon" icon="group2"
                            role="status-yellow" size="24"></StudipIcon>
                    <span v-translate>
                        Das Meeting gehört der Gruppe
                    </span>
                    <span v-if="group_name" v-text="group_name"></span>
                </div>

                <div v-if="course_config.display.editRoom && room.folder_id !== null && room.details && room.details.folder">
                    <template v-if="room.preupload_not_allowed">
                        <div>
                            <a>
                                <StudipIcon class="info-icon" icon="exclaim-circle-full"
                                    role="status-red" size="24"></StudipIcon>
                            </a>
                            <span v-translate v-text="room.preupload_not_allowed"></span>
                        </div>
                    </template>
                    <template v-else>
                        <StudipIcon class="info-icon" icon="folder-empty"
                            role="inactive" size="24">
                        </StudipIcon>
                        <translate>
                            Ordner für automatische Uploads:
                        </translate>
                         <a :href="room.details.folder.link" target="_blank">
                            {{ room.details.folder.name }}
                        </a>
                    </template>
                   
                </div>


                <div v-if="num_drivers > 1">
                    <StudipIcon class="info-icon" icon="video2"
                        role="info" size="24"></StudipIcon>

                    {{ this.config[room.driver].display_name
                        ? this.config[room.driver].display_name
                        : room.driver }}
                </div>

                <div v-if="course_config.display.editRoom && room.driver &&
                    ((Object.keys(config[room.driver]).includes('record') && JSON.parse(config[room.driver].record) == true) ||
                    (Object.keys(config[room.driver]).includes('opencast') && JSON.parse(config[room.driver].opencast) == true)) &&
                    room.features && room.features.record && room.features.room_anyone_can_start &&
                    JSON.parse(room.features.record) == true && JSON.parse(room.features.room_anyone_can_start) == true">
                    <a>
                        <StudipIcon class="info-icon" icon="exclaim-circle"
                            role="status-yellow" size="24"></StudipIcon>
                    </a>
                    <span v-translate v-text="$gettext('Aufzeichnung kann früher beginnen')"></span>
                    <StudipTooltipIcon :text="$gettext('Es ist bei Aufzeichnungen dringend empfohlen die Veranstaltung und somit die Aufzeichnungen erst zu beginnen,' +
                        ' wenn Lehrende die Videokonferenz betreten.')">
                    </StudipTooltipIcon>
                </div>

                <div v-if="course_config.display.editRoom && room.features && room.features.record && room.features.record == 'true' && room.record_not_allowed">
                    <a>
                        <StudipIcon class="info-icon" icon="exclaim-circle-full"
                            role="status-red" size="24"></StudipIcon>
                    </a>
                    <span v-translate v-text="room.record_not_allowed"></span>
                </div>

                <div v-if="!room.enabled">
                    <a>
                        <StudipIcon class="info-icon" icon="exclaim-circle-full"
                            role="status-red" size="24"></StudipIcon>
                    </a>
                    <span v-translate>
                        Dieser Raum ist deaktiviert, da der Treiber {{ room.driver }}
                        nicht aktiviert oder falsch konfiguriert ist.
                    </span>
                </div>
            </label>
            <div class="meeting-item-btns">
                <StudipButton v-if="course_config.display.editRoom && room.features && room.features['invite_moderator'] && room.features['invite_moderator'] == 'true'"
                    type="button" v-on:click="getModeratorGuestInfo()"
                    icon="add" v-translate
                >
                    Moderator einladen
                </StudipButton>
                <StudipButton v-if="course_config.display.editRoom && room.features && room.features['guestPolicy-ALWAYS_ACCEPT'] && room.features['guestPolicy-ALWAYS_ACCEPT'] == 'true'"
                    type="button" v-on:click="getGuestInfo()"
                    icon="add" v-translate
                >
                    Einladungslink erstellen
                </StudipButton>
                <!-- <a v-if="room.enabled" class="button join"
                    :href="join_url" target="_blank"
                    v-translate
                >
                    Teilnehmen
                </a> -->
                <a v-if="room.enabled" class="button join"
                    @click="checkPreJoin"
                    v-translate
                >
                    Teilnehmen
                </a>

                <button v-else class="button join"
                    disabled="disabled" v-translate
                >
                    Teilnehmen nicht möglich
                </button>
            </div>
        </fieldset>
        <MeetingMessageDialog v-if="showConfirmDialog"
            :dialog="showConfirmDialog"
            @accept="performConfirm"
            @cancel="showConfirmDialog = false"
        />
    </div>
</template>

<script>
import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";
import MeetingMessageDialog from "@/components/MeetingMessageDialog";
import { mapGetters } from "vuex";
import store from "@/store";

import {
    ROOM_UPDATE,
    ROOM_DELETE
} from "@/store/actions.type";

export default {
    name: "MeetingComponent",
    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
        MeetingMessageDialog
    },

    computed: {
        ...mapGetters(['course_config', 'config', 'course_groups']),

        join_url() {
            return API_URL + '/rooms/join/' + this.room.course_id + '/' + this.room.id;
        },

        num_drivers() {
            let num_drivers = 0;

            for (let driver in this.config) {
                if (this.config[driver].enable === '1') {
                    num_drivers++;
                }
            }

            return num_drivers;
        },

        group_name() {
            let group_name = '';
            if (this.room.group_id != undefined) {
                group_name = this.course_groups[this.room.group_id];
            }
            return group_name;
        }
    },

    props: {
        room: {
            type: Object,
            required: true
        },
        info: {
            type: Object,
        }
    },

    data() {
        return {
            interval: null,
            showConfirmDialog: false
        }
    },

    methods: {
        getNonReactiveRoom() {
            return JSON.parse(JSON.stringify(this.room));
        },

        writeFeedback() {
            this.$emit('getFeedback', this.getNonReactiveRoom());
        },

        editFeatures() {
            this.$emit('getFeatures', this.getNonReactiveRoom());
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
            this.$emit('getRecording', this.getNonReactiveRoom());
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
            this.$emit('getGuestInfo', this.getNonReactiveRoom());
        },

        getModeratorGuestInfo() {
            this.$emit('getModeratorGuestInfo', this.getNonReactiveRoom());
        },

        checkPreJoin() {
            if (this.room.features && this.room.features.maxParticipants && this.info && this.info.participantCount &&
                this.room.features.maxParticipants <= this.info.participantCount) {
                    alert('AHOOOOIII!');
            } else {
                // window.open(this.join_url, '_blank');
                this.showConfirmDialog = {
                    title: 'Information',
                    text: 'The number of participants are exceed please be aware!',
                    type: 'info',
                    isConfirm: true,
                    callback: 'performJoin'
                }
            }
        },

        performConfirm(callback) {
            if (callback && this[callback] != undefined) {
                this[callback]();
            }
        },

        performJoin() {
            window.open(this.join_url, '_blank');
        }
    }
}
</script>

<style>

</style>
