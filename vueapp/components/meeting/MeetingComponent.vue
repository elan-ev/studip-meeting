<template>
    <section class="meetingcomponent contentbox">
        <header class="meeting-item-header"
            :class="{
                'meeting-disabled' : !room.enabled
            }"
        >
            <h1>
                {{room.name}}
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
        <section class="contents">
            <article v-if="room.description" class="description">
                <p v-html="nl2Br(room.description)"></p>
            </article>
            <article class="details">
                <div v-if="showParticipantCount">
                    <StudipIcon class="info-icon" icon="group4"
                            role="info" size="24"></StudipIcon>
                    <span class="all-sizes" v-text="showParticipantCount"></span>
                </div>
                <div v-if="course_config.display.editRoom && room.is_default == 1">
                    <StudipIcon class="info-icon" icon="star"
                            role="info" size="24"></StudipIcon>
                    <span v-text="$gettext('Dies ist der Standardraum')"></span>
                    <span class="size-tiny" v-text="$gettext('Standardraum')"></span>
                </div>

                <div v-if="course_config.display.editRoom">
                    <button tabindex="0" role="button" class="as-link" :aria-pressed="room.join_as_moderator == 1"
                        :title=" room.join_as_moderator == 1 ?
                            $gettext('Teilnehmenden nur eingeschränkte Rechte geben')
                            : $gettext('Teilnehmenden Administrationsrechte geben')"
                            @click.prevent="editRights()">
                        <StudipIcon class="info-icon" :icon="room.join_as_moderator == 1 ? 'lock-unlocked' : 'lock-locked'" role="clickable" size="24"></StudipIcon>
                    </button>
                    <span aria-live="polite" :class="'rights-info-text-' + room.id">
                        {{ room.join_as_moderator == 1 ?
                                $gettext('Teilnehmende haben Moderationsrechte')
                                : $gettext('Teilnehmende haben eingeschränkte Rechte') }}
                    </span>
                    <span aria-live="polite" :class="'rights-info-text-' + room.id" class="size-tiny">
                        {{ room.join_as_moderator == 1 ?
                                $gettext('Moderationsrechte')
                                : $gettext('Eingeschränkte Rechte') }}
                    </span>
                </div>

                <div v-if="course_config.display.editRoom">
                    <button tabindex="0" role="button" class="as-link" :aria-pressed="room.active == 1"
                        :title="room.active == 1 ?
                            $gettext('Meeting für Teilnehmende unsichtbar schalten')
                            : $gettext('Meeting für Teilnehmende sichtbar schalten') "
                        @click.prevent="editVisibility()">
                        <StudipIcon class="info-icon" :icon="room.active == 1 ? 'visibility-visible' : 'visibility-invisible'"
                            role="clickable" size="24"></StudipIcon>
                    </button>
                    <span aria-live="polite" :class="'active-info-text-' + room.id">
                        {{ room.active == 1 ?
                        $gettext('Das Meeting ist für die Teilnehmenden sichtbar')
                        : $gettext('Das Meeting ist für die Teilnehmenden unsichtbar') }}
                    </span>
                    <span aria-live="polite" :class="'active-info-text-' + room.id" class="size-tiny">
                        {{ room.active == 1 ?
                        $gettext('Sichtbar')
                        : $gettext('Unsichtbar') }}
                    </span>
                </div>

                <div v-if="course_config.display.editRoom && room.group_id">
                    <StudipIcon class="info-icon" icon="group2"
                            role="info" size="24"></StudipIcon>
                    <span>{{ $gettext('Das Meeting gehört der Gruppe') }}</span>
                    <span v-if="group_name" v-text="group_name"></span>
                    <span class="size-tiny" v-text="$gettext('Gruppe:') + ' ' + group_name"></span>
                </div>
                <div v-if="course_config.display.editRoom && room.folder_id !== null && room.details && room.details.folder">
                    <template v-if="room.preupload_not_allowed">
                        <StudipIcon class="info-icon" icon="exclaim-circle-full"
                            role="status-red" size="24"></StudipIcon>
                        <span class="all-sizes" v-text="room.preupload_not_allowed"></span>
                    </template>
                    <template v-else>
                        <StudipIcon class="info-icon" icon="folder-empty"
                            role="info" size="24">
                        </StudipIcon>
                        <span>
                            {{ $gettext('Ordner für automatische Uploads:') }}
                        </span>
                        <span class="size-tiny">
                            {{ $gettext('Ordner:') }}
                        </span>
                        <a :href="room.details.folder.link"
                            tabindex="0"
                            :aria-label="$gettext('Weiterleitung zum ausgewählten Ordner auf einer neuen Seite')"
                            :title="$gettext('Ausgewählter Ordner')"
                            target="_blank">
                            {{ room.details.folder.name }}
                        </a>
                    </template>
                </div>

                <div v-if="num_drivers > 1">
                    <StudipIcon class="info-icon" icon="video2"
                        role="info" size="24"></StudipIcon>
                    <span class="all-sizes">
                        {{ this.config[room.driver].display_name
                            ? this.config[room.driver].display_name
                            : room.driver }}
                    </span>
                </div>

                <div v-if="display_room_recording_warning">
                    <StudipIcon class="info-icon" icon="exclaim-circle"
                        role="status-yellow" size="24"></StudipIcon>
                    <span class="all-sizes" v-text="$gettext('Aufzeichnung kann früher beginnen')"></span>
                    <StudipTooltipIcon :text="$gettext('Es ist bei Aufzeichnungen dringend empfohlen die Veranstaltung und somit die Aufzeichnungen erst zu beginnen,' +
                        ' wenn Lehrende die Videokonferenz betreten.')">
                    </StudipTooltipIcon>
                </div>

                <div v-if="course_config.display.editRoom && room.features && room.features.record && room.features.record == 'true' && room.record_not_allowed">
                    <StudipIcon class="info-icon" icon="exclaim-circle-full"
                        role="status-red" size="24"></StudipIcon>
                    <span class="all-sizes" v-text="room.record_not_allowed"></span>
                </div>

                <div v-if="!room.enabled">
                    <StudipIcon class="info-icon" icon="exclaim-circle-full"
                        role="status-red" size="24"></StudipIcon>
                    <span class="all-sizes">
                        Dieser Raum ist deaktiviert, da der Treiber {{ room.driver }}
                        nicht aktiviert oder falsch konfiguriert ist.
                    </span>
                </div>
            </article>
        </section>
        <footer>
            <button class="button join" :disabled="!room.enabled" @click="checkPreJoin">
                <span v-show="room.enabled"><translate>Teilnehmen</translate></span>
                <span  v-show="!room.enabled"><translate>Teilnehmen nicht möglich</translate></span>
            </button>
            <template v-if="course_config.display.editRoom && room.features">
                <StudipButton v-if="room.features['invite_moderator'] && room.features['invite_moderator'] == 'true'"
                    type="button" v-on:click="getModeratorGuestInfo()"
                    icon="add"
                >
                    <span v-text="$gettext('Moderierende einladen')"></span>
                </StudipButton>
                <StudipButton v-if="room.features['guestPolicy-ALWAYS_ACCEPT'] && room.features['guestPolicy-ALWAYS_ACCEPT'] == 'true'"
                    type="button" v-on:click="getGuestInfo()"
                    icon="add"
                >
                    <span v-text="$gettext('Teilnehmende einladen')"></span>
                </StudipButton>
            </template>
        </footer>

        <!-- Dialogs -->
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
    </section>
</template>

<script>
import StudipActionMenu from '@studip/StudipActionMenu.vue';
import { mapGetters } from "vuex";
import { confirm_dialog } from '@/common/confirm_dialog.mixins'

import {
    ROOM_UPDATE,
    ROOM_DELETE
} from "@/store/actions.type";

export default {
    name: "MeetingComponent",
    components: {
        StudipActionMenu,
    },

    mixins: [confirm_dialog],

    computed: {
        ...mapGetters(['course_config', 'config', 'course_groups', 'course_general_config']),

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
                return `${ participantCount }/${ maxParticipants } ` + this.$gettext('Teilnehmende aktiv');
            } else if (!maxParticipants && participantCount) {
                return `${ participantCount } ` + this.$gettext('Teilnehmende aktiv');
            } else if (maxParticipants && !participantCount) {
                return this.$gettext('Maximale Teilnehmerzahl') + `: ${ maxParticipants }`;
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
        },

        display_room_recording_warning() {
            return this.course_config?.display?.editRoom && this.room?.early_recording;
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
            $(`.rights-info-text-${this.room.id}`).removeClass('has-changed');
            this.room.join_as_moderator = this.room.join_as_moderator == 1 ? 0 : 1;
            this.$store.dispatch(ROOM_UPDATE, this.room)
            .then(({ data }) => {
                if (data.message.type == 'error') {
                    this.room.join_as_moderator = !this.room.join_as_moderator;
                    this.$emit('setMessage', data.message);
                } else {
                    $(`.rights-info-text-${this.room.id}`).addClass('has-changed');
                }
            }).catch (({error}) => {
                this.room.join_as_moderator = !this.room.join_as_moderator;
            });
        },

        editVisibility() {
            $(`.active-info-text-${this.room.id}`).removeClass('has-changed');
            this.room.active = this.room.active == 1 ? 0 : 1;
            this.$store.dispatch(ROOM_UPDATE, this.room)
            .then(({ data }) => {
                if (data.message.type == 'error') {
                    this.room.active = !this.room.active;
                    this.$emit('setMessage', data.message);
                } else {
                    $(`.active-info-text-${this.room.id}`).addClass('has-changed');
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
                title: this.$gettext('Raum löschen'),
                question: this.$gettext('Sind Sie sicher, dass Sie diesen Raum löschen möchten?'),
                confirm_callback: 'performDeleteRoom',
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
            if (!this.room.enabled) {
                return;
            }
            if (this.room.features && this.room.features.maxParticipants && this.info && this.info.participantCount &&
                this.room.features.maxParticipants <= this.info.participantCount) {
                this.showConfirmDialog = {
                    title: 'Information',
                    question: this.$gettext("Ihr Zugang kann eingeschränkt sein, da die Teilnehmerzahl für diese Sitzung überschritten wird. Möchten Sie es versuchen?"),
                    confirm_callback: 'performJoin',
                    height: 215
                }
            } else if (this.room?.features?.record == 'true' && this.course_general_config?.show_recording_privacy_text) {
                this.showConfirmDialog = {
                    title: this.$gettext('Datenschutzerklärung'),
                    question: this.$gettext('Ich bin damit einverstanden, dass diese Sitzung aufgezeichnet wird. Die Aufzeichnung kann Sprach- und Videoaufnahmen von mir beinhalten.' +
                        ' Bitte beachten Sie, dass die Aufnahme im Anschluss geteilt werden kann.' +
                        ' Möchten Sie trotzdem teilnehmen?'),
                    confirm_callback: 'performJoin',
                    height: 255
                }
            } else {
                window.open(this.join_url, '_blank');
            }
        },

        performJoin() {
            window.open(this.join_url, '_blank');
        },

        showQRCode() {
            this.$emit('displayQRCode', this.getNonReactiveRoom());
        },

        nl2Br(pureText) {
            return pureText.replace(/(?:\r\n|\r|\n)/g, '<br>');
        }
    }
}
</script>
