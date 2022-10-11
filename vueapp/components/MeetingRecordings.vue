<template>
    <div>
         <MeetingDialog :title="$gettext('Aufzeichnungen für Raum') + ' ' +  room.name" @close="$emit('cancel')">
            <template v-slot:content>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <MessageBox type="info"
                    v-if="Object.keys(recording_list).length == 0"
                >
                    <translate>Keine Aufzeichnungen für Raum "{{ room.name }}" vorhanden</translate>
                </MessageBox>

                <form class="default" method="post">
                    <fieldset v-if="Object.keys(recording_list).includes('opencast')">
                        <legend>Opencast</legend>
                        <label>
                            <a class="meeting-recording-url" target="_blank"
                            :href="recording_list['opencast']" v-translate>
                                Die vorhandenen Aufzeichnungen auf Opencast
                            </a>
                        </label>
                    </fieldset>
                    <fieldset v-if="Object.keys(recording_list).includes('default') && Object.keys(recording_list['default']).length">
                        <label>
                            <table class="default">
                                <thead>
                                    <tr>
                                        <th v-translate>Aufzeichnungen</th>
                                        <th v-translate>Datum</th>
                                        <th v-translate>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(recording, index) in recording_list.default" :key="index">
                                        <td style="width: 60%">
                                            <ul style="list-style: none; padding: 0;">
                                                <template v-if="Array.isArray(recording['playback']['format'])">
                                                    <li v-for="(format, index) in recording['playback']['format']" :key="index">
                                                        <a class="meeting-recording-url" target="_blank"
                                                            :href="format['url']">
                                                            <translate>Aufzeichnung ansehen</translate>
                                                            {{ `(${format['type']})` }}
                                                        </a>
                                                    </li>
                                                </template>
                                                <li v-else>
                                                    <a class="meeting-recording-url" target="_blank"
                                                        :href="recording['playback']['format']['url']" v-translate>
                                                        Aufzeichnung ansehen
                                                    </a>
                                                </li>
                                            </ul>
                                        </td>
                                        <td style="width: 35%">{{ recording['startTime'] }}</td>
                                        <td style="width: 5%">
                                            <div v-if="course_config.display.deleteRecording" style="text-align: right;">
                                                <a href="#" :title="$gettext('Aufzeichnung löschen')" style="cursor: pointer;"
                                                    @click.prevent="deleteRecording(recording)">
                                                    <StudipIcon icon="trash" role="clickable"></StudipIcon>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </label>
                    </fieldset>
                </form>
            </template>
            <template v-slot:buttons>
                <StudipButton icon="cancel" type="button"  @click="$emit('cancel');" v-translate>
                    Schließen
                </StudipButton>
            </template>
         </MeetingDialog>

         <!-- dialogs -->
        <MeetingMessageDialog v-if="showConfirmDialog"
            :message="showConfirmDialog"
            @accept="handleConfirmCallbacks"
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
import MeetingMessageDialog from "@/components/MeetingMessageDialog";
import { dialog } from '@/common/dialog.mixins'


import {
    RECORDING_LIST, RECORDING_DELETE,
} from "@/store/actions.type";

export default {
    name: "MeetingRecordings",

    props: ['room'],

    mixins: [dialog],

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
        MeetingMessageDialog
    },

    data() {
        return {
            modal_message: {},
            message: '',
            showConfirmDialog: false
        }
    },

    computed: {
        ...mapGetters([
            'course_config', 'recording_list', 'recording'
        ])
    },

    mounted() {
        this.$store.dispatch(RECORDING_LIST, this.room.id);
    },

    methods: {
        deleteRecording(recording) {
            this.showConfirmDialog = false;
            this.showConfirmDialog = {
                title: this.$gettext('Aufzeichnung löschen'),
                text: this.$gettext('Sind Sie sicher, dass Sie diese Aufzeichnung löschen möchten?'),
                type: 'question', //info, warning, question
                isConfirm: true,
                callback: 'performDeleteRecording',
                callback_data: {recording},
            }
        },
        performDeleteRecording({recording}) {
            if (!recording) {
                return;
            }
            this.$store.dispatch(RECORDING_DELETE, recording)
            .then(({data}) => {
                if (data.message) {
                    this.$set(this.modal_message, "type" , data.message.type);
                    this.$set(this.modal_message, "text" , data.message.text);
                    if (data.message.type == 'success') {
                        this.$store.dispatch(RECORDING_LIST, recording.room_id);
                    }
                }
            });
        },
        handleConfirmCallbacks(callback, data = null) {
            this.showConfirmDialog = false;
            if (callback && this[callback] != undefined) {
                this[callback](data);
            }
        },
    }
}
</script>
