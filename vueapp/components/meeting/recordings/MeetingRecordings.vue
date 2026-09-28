<template>
    <div>
        <studip-dialog
            :title="$gettext('Aufzeichnungen für Raum') + ' ' +  room.name"
            :closeText="$gettext('Schließen')"
            closeClass="cancel"
            class="meeting-dialog"
            height="400"
            width="1000"
            @close="$emit('cancel')"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <MessageBox type="info"
                    v-if="Object.keys(recording_list).length == 0"
                >
                    {{ $gettext($gettext('Keine Aufzeichnungen für Raum %{ name } vorhanden'), {name: room.name}) }}
                </MessageBox>

                <form class="default" method="post">
                    <fieldset v-if="Object.keys(recording_list).includes('opencast')">
                        <legend>Opencast</legend>
                        <label>
                            <a class="meeting-recording-url" target="_blank"
                            :href="recording_list['opencast']">
                                {{ $gettext('Die vorhandenen Aufzeichnungen auf Opencast') }}
                            </a>
                        </label>
                    </fieldset>
                    <fieldset v-if="Object.keys(recording_list).includes('default') && Object.keys(recording_list['default']).length">
                        <div>
                            <table class="default">
                                <thead>
                                    <tr>
                                        <th v-if="canManageVisibility" class="recording-selection" scope="col">
                                            <input
                                                type="checkbox"
                                                :checked="allRecordingsSelected"
                                                :aria-label="$gettext('Alle auswählen')"
                                                @change="toggleAllRecordings($event.target.checked)"
                                            >
                                        </th>
                                        <th class="recording-link" scope="col">{{ $gettext('Aufzeichnungen') }}</th>
                                        <th class="recording-date" scope="col">{{ $gettext('Datum') }}</th>
                                        <th v-if="room.driver === 'BigBlueButton'" class="recording-visibility" scope="col">{{ $gettext('Sichtbarkeit') }}</th>
                                        <th v-if="course_config.display.deleteRecording" scope="col">{{ $gettext('Aktionen') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(recording, index) in recording_list.default" :key="index">
                                        <td v-if="canManageVisibility" class="recording-selection">
                                            <input
                                                type="checkbox"
                                                :value="String(recording.recordID)"
                                                v-model="selectedRecordings"
                                                :aria-label="$gettext('Aufzeichnung auswählen')"
                                            >
                                        </td>
                                        <td class="recording-link">
                                            <ul style="list-style: none; padding: 0;">
                                                <template v-if="Array.isArray(recording['playback']['format'])">
                                                    <li v-for="(format, index) in recording['playback']['format']" :key="index">
                                                        <a class="meeting-recording-url" target="_blank"
                                                            :href="format['url']">
                                                            {{ $gettext('Aufzeichnung ansehen') }}
                                                            {{ `(${format['type']})` }}
                                                        </a>
                                                    </li>
                                                </template>
                                                <li v-else>
                                                    <a class="meeting-recording-url" target="_blank"
                                                        :href="recording['playback']['format']['url']">
                                                            {{ $gettext('Aufzeichnung ansehen') }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </td>
                                        <td class="recording-date">{{ recording['startTime'] }}</td>
                                        <td v-if="room.driver === 'BigBlueButton'" class="recording-visibility">
                                            <span class="visibility-status">
                                                <StudipIcon
                                                    :shape="visibilityIcon(recording.visibility)"
                                                    role="info"
                                                    size="20"
                                                />
                                                <select
                                                    class="size-l"
                                                    v-if="course_config.display.deleteRecording"
                                                    :value="recording.visibility"
                                                    :aria-label="$gettext('Sichtbarkeit der Aufzeichnung')"
                                                    @change="updateVisibility(recording, $event.target.value)"
                                                >
                                                    <option value="teachers">{{ $gettext('Nur Lehrende') }}</option>
                                                    <option value="participants">{{ $gettext('Lehrende und Teilnehmende') }}</option>
                                                    <option value="public">{{ $gettext('Öffentlich') }}</option>
                                                </select>
                                                <span v-else>{{ visibilityLabel(recording.visibility) }}</span>
                                            </span>
                                        </td>
                                        <td  style="width: 5%">
                                            <div style="text-align: right;">
                                                <a v-if="course_config.display.deleteRecording" 
                                                    href="#" :title="$gettext('Aufzeichnung löschen')" 
                                                    style="cursor: pointer;"
                                                    @click.prevent="deleteRecording(recording)"
                                                >
                                                    <StudipIcon shape="trash" role="clickable"></StudipIcon>
                                                </a>

                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot v-if="canManageVisibility">
                                    <tr>
                                        <td colspan="5">
                                            <div>
                                                <select class="size-s" v-model="bulkVisibility">
                                                    <option value="teachers">{{ $gettext('Nur Lehrende') }}</option>
                                                    <option value="participants">{{ $gettext('Lehrende und Teilnehmende') }}</option>
                                                    <option value="public">{{ $gettext('Öffentlich') }}</option>
                                                </select>
                                                <button
                                                    type="button"
                                                    class="button"
                                                    :disabled="selectedRecordings.length === 0 || bulkUpdatePending"
                                                    @click="updateSelectedVisibility"
                                                >
                                                    {{ $gettext('Übernehmen') }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </fieldset>
                </form>
            </template>
        </studip-dialog>

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
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import { confirm_dialog } from '@/common/confirm_dialog.mixins'

import {
    RECORDING_LIST, RECORDING_DELETE, RECORDING_VISIBILITY_UPDATE,
} from "@/store/actions.type";

export default {
    name: "MeetingRecordings",

    props: ['room'],

    mixins: [confirm_dialog],

    data() {
        return {
            modal_message: {},
            message: '',
            selectedRecordings: [],
            bulkVisibility: 'teachers',
            bulkUpdatePending: false,
        }
    },

    computed: {
        ...mapGetters([
            'course_config', 'recording_list', 'recording'
        ]),
        canManageVisibility() {
            return this.room.driver === 'BigBlueButton' && this.course_config.display.deleteRecording;
        },
        allRecordingsSelected() {
            const recordings = this.recording_list.default || [];
            return recordings.length > 0 && this.selectedRecordings.length === recordings.length;
        },
    },

    mounted() {
        this.$store.dispatch(RECORDING_LIST, this.room.id);
    },

    methods: {
        visibilityIcon(visibility) {
            return {
                teachers: 'lock-locked',
                participants: 'group2',
                public: 'globe',
            }[visibility] || 'lock-locked';
        },
        visibilityLabel(visibility) {
            return {
                teachers: this.$gettext('Nur Lehrende'),
                participants: this.$gettext('Lehrende und Teilnehmende'),
                public: this.$gettext('Öffentlich'),
            }[visibility] || this.$gettext('Nur Lehrende');
        },
        updateVisibility(recording, visibility) {
            this.$store.dispatch(RECORDING_VISIBILITY_UPDATE, {recording, visibility})
            .then(({data}) => {
                if (data.message) {
                    this.modal_message = data.message;
                }
                this.$store.dispatch(RECORDING_LIST, recording.room_id);
            });
        },
        toggleAllRecordings(checked) {
            this.selectedRecordings = checked
                ? (this.recording_list.default || []).map(recording => String(recording.recordID))
                : [];
        },
        updateSelectedVisibility() {
            const recordings = (this.recording_list.default || []).filter(recording =>
                this.selectedRecordings.includes(String(recording.recordID))
            );
            if (!recordings.length) {
                return;
            }

            this.bulkUpdatePending = true;
            this.$store.dispatch(RECORDING_VISIBILITY_UPDATE, {
                recordings,
                visibility: this.bulkVisibility,
            }).then(({data}) => {
                const failed = data?.message?.type === 'error';
                this.modal_message = {
                    type: failed ? 'error' : 'success',
                    text: failed
                        ? this.$gettext('Die Sichtbarkeit konnte nicht für alle ausgewählten Aufzeichnungen geändert werden.')
                        : this.$gettext('Die Sichtbarkeit der ausgewählten Aufzeichnungen wurde geändert.'),
                };
                this.selectedRecordings = [];
                return this.$store.dispatch(RECORDING_LIST, this.room.id);
            }).catch(() => {
                this.modal_message = {
                    type: 'error',
                    text: this.$gettext('Die Sichtbarkeit konnte nicht für alle ausgewählten Aufzeichnungen geändert werden.'),
                };
            }).finally(() => {
                this.bulkUpdatePending = false;
            });
        },
        deleteRecording(recording) {
            this.showConfirmDialog = false;
            this.showConfirmDialog = {
                title: this.$gettext('Aufzeichnung löschen'),
                question: this.$gettext('Sind Sie sicher, dass Sie diese Aufzeichnung löschen möchten?'),
                height: '200',
                confirm_callback: 'performDeleteRecording',
                confirm_callback_data: {recording},
            }
        },
        performDeleteRecording({recording}) {
            if (!recording) {
                return;
            }
            this.$store.dispatch(RECORDING_DELETE, recording)
            .then(({data}) => {
                if (data.message) {
                    this.modal_message["type"] = data.message.type;
                    this.modal_message["text"] = data.message.text;
                    if (data.message.type == 'success') {
                        this.$store.dispatch(RECORDING_LIST, recording.room_id);
                    }
                }
            });
        },
    }
}
</script>

<!-- <style scoped>
.recording-selection {
    width: 3rem;
    text-align: center;
}

.recording-link {
    width: 32%;
}

.recording-date {
    width: 20%;
    white-space: nowrap;
}

.recording-visibility {
    width: 36%;
    min-width: 19rem;
}

.visibility-status,
.recording-bulk-action {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.visibility-status select {
    width: 100%;
}

.recording-bulk-action {
    justify-content: flex-end;
    flex-wrap: wrap;
}
</style> -->
