<template>
    <section class="meetingcomponent contentbox">
        <header class="meeting-item-header"
            :class="{
                'meeting-disabled' : !room.enabled
            }"
        >
            <h1>
                {{room.name}}
                <StudipTooltipIcon v-if="room.details"
                    :text="`${room.details['creator']}, ${room.details['date']}`">
                </StudipTooltipIcon>
            </h1>
            <template v-if="show_recording_badge">
                <StudipTooltipIcon v-show="opencast_webcam_record_enabled"
                    :text="$gettext('Bitte beachten Sie, dass dieser Raum aufgezeichnet wird! Die Webcams der Teilnehmenden könnten auch aufgezeichnet werden!')"
                    :badge="true"
                >
                    <StudipIcon icon="span-full" role="attention" size="11"></StudipIcon> <span v-text="'Rec + Webcam'"></span>
                </StudipTooltipIcon>
                <StudipTooltipIcon v-if="!opencast_webcam_record_enabled"
                    :text="$gettext('Bitte beachten Sie, dass dieser Raum aufgezeichnet wird!')"
                    :badge="true"
                >
                    <StudipIcon icon="span-full" role="attention" size="11"></StudipIcon> <span v-text="'Rec'"></span>
                </StudipTooltipIcon>
            </template>
            <StudipActionMenu v-if="generate_menu_items.length"
                :items="generate_menu_items"
                @getRecording="getRecording"
                @editFeatures="editFeatures"
                @showQRCode="showQRCode"
                @writeFeedback="writeFeedback"
                @deleteRoom="deleteRoom"
            />
        </header>
        <section>
            <article id="details">
                <span v-if="showParticipantCount" class="participants" v-text="showParticipantCount"></span>
                <div v-if="course_config.display.editRoom && room.is_default == 1">
                    <StudipIcon class="info-icon" icon="star"
                            role="info" size="24"></StudipIcon>
                    <span v-text="$gettext('Dies ist der Standardraum')"></span>
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
                        $gettext('Das Meeting ist für die Teilnehmenden sichtbar')
                        : $gettext('Das Meeting ist für die Teilnehmenden unsichtbar') }}
                    </span>
                </div>

                <div v-if="course_config.display.editRoom && room.group_id">
                    <StudipIcon class="info-icon" icon="group2"
                            role="info" size="24"></StudipIcon>
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
                            role="info" size="24">
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

                <label v-if="show_recording_badge" class="accept-records">
                    <input type="checkbox" v-model="recordsAccepted">
                    <translate>
                        Ich bin damit einverstanden, dass diese Sitzung aufgezeichnet wird. Die Aufzeichnung kann Sprach- und Videoaufnahmen von mir beinhalten. Bitte beachten Sie, dass die Aufnahme im Anschluss geteilt werden kann.
                    </translate>
                </label>
            </article>
        </section>
        <footer>
            <button v-if="room.enabled" class="button join"
                @click="checkPreJoin"
                v-translate
                    :disabled="!recordsAccepted && show_recording_badge">
                Teilnehmen
            </button>

            <button v-else class="button join"
                disabled="disabled" v-translate
            >
                Teilnehmen nicht möglich
            </button>
            <template v-if="course_config.display.editRoom && room.features">
                <StudipButton v-if="room.features['invite_moderator'] && room.features['invite_moderator'] == 'true'"
                    type="button" v-on:click="getModeratorGuestInfo()"
                    icon="add"
                >
                    <span v-text="$gettext('Moderator einladen')"></span>
                </StudipButton>
                <StudipButton v-if="room.features['guestPolicy-ALWAYS_ACCEPT'] && room.features['guestPolicy-ALWAYS_ACCEPT'] == 'true'"
                    type="button" v-on:click="getGuestInfo()"
                    icon="add"
                >
                    <span v-text="$gettext('Einladungslink erstellen')"></span>
                </StudipButton>
            </template>
        </footer>

        <!-- dialogs -->
        <MeetingMessageDialog v-if="showConfirmDialog"
            :message="showConfirmDialog"
            @accept="performConfirm"
            @cancel="showConfirmDialog = false"
        />
    </section>
</template>

<script>
import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";
import MeetingMessageDialog from "@/components/MeetingMessageDialog";
import StudipActionMenu from '@/components/StudipActionMenu.vue';
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
        MeetingMessageDialog,
        StudipActionMenu,
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
        },
        show_recording_badge() {
            return this.room?.driver && this.config &&
                (parseInt(this.config[this.room.driver]?.record) || parseInt(this.config[this.room.driver]?.opencast)) &&
                this.room?.features?.record == 'true' && !this.room?.record_not_allowed;
        },

        opencast_webcam_record_enabled() {
            return this.room?.driver && this.config && parseInt(this.config[this.room.driver]?.opencast) && this.room?.features?.opencast_webcam_record == 'true';
        },

        showParticipantCount() {
            var maxParticipants = 0;
            if (this.room.features && this.room.features.maxParticipants > 0) {
                maxParticipants = this.room.features.maxParticipants;
            }

            var participantCount = 0;
            if (this.info && this.info.participantCount > 0) {
                participantCount = this.info.participantCount;
            }

            if (maxParticipants && participantCount) {
                return `${ participantCount }/${ maxParticipants } ` + 'Teilnehmende aktiv'.toLocaleString();
            } else if (!maxParticipants && participantCount) {
                return `${ participantCount } ` + 'Teilnehmende aktiv'.toLocaleString();
            } else if (maxParticipants && !participantCount) {
                return `Maximale Teilnehmerzahl: ${ maxParticipants }`.toLocaleString();
            } else {
                return false;
            }
        },

        generate_menu_items() {
            let menuItems = [];
            let id = 1;
            if (this.course_config?.display?.editRoom) {
                menuItems.push({id: id, label: this.$gettext('Raumeinstellungen'), icon: 'admin', emit: 'editFeatures'});
                id++;
            }
            if (this.room?.has_recordings) {
                menuItems.push({id: id, label: this.$gettext('Die vorhandenen Aufzeichnungen'), icon: 'video2', emit: 'getRecording'});
                id++;
            }
            menuItems.push({id: id, label: this.$gettext('Persönlichen QR-Code anzeigen'), icon: 'code-qr', emit: 'showQRCode'});
            id++;

            menuItems.push({id: id, label: this.$gettext('Melden Sie ein Problem'), icon: 'support', emit: 'writeFeedback'});
            id++;

            if (this.course_config?.display?.deleteRoom) {
                menuItems.push({id: id, label: this.$gettext('Raum löschen'), icon: 'trash', emit: 'deleteRoom'});
                id++;
            }

            return menuItems;
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
            showConfirmDialog: false,
            recordsAccepted: false
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

            this.showConfirmDialog = {
                title: 'Raum löschen'.toLocaleString(),
                text: 'Sind Sie sicher, dass Sie diesen Raum löschen möchten?'.toLocaleString(),
                type: 'question', //info, warning, question
                isConfirm: true,
                callback: 'performDeleteRoom',
            }
        },

        performDeleteRoom() {
            this.$store.dispatch(ROOM_DELETE, this.room.id)
            .then(({data}) => {
                this.$emit('setMessage', data.message);
                if (data.message.type == 'success') {
                    this.$emit('renewRoomList');
                }
            });
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
                this.showConfirmDialog = {
                    title: 'Information',
                    text: "Ihr Zugang kann eingeschränkt sein, da die Teilnehmerzahl für diese Sitzung überschritten wird. Möchten Sie es versuchen?".toLocaleString(),
                    type: 'question', //info, warning, question
                    isConfirm: true,
                    callback: 'performJoin',
                }
            } else {
                window.open(this.join_url, '_blank');
            }
        },

        performConfirm(callback) {
            this.showConfirmDialog = false;
            if (callback && this[callback] != undefined) {
                this[callback]();
            }
        },

        performJoin() {
            window.open(this.join_url, '_blank');
        },

        showQRCode() {
            this.$emit('displayQRCode', this.getNonReactiveRoom());
        }
    }
}
</script>

<style>

</style>
