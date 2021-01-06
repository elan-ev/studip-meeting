<template>
    <div>
         <MeetingDialog :title="$gettext('Aufzeichnungen für Raum') + ' ' +  room.name" @close="$emit('cancel')">
            <template v-slot:content>
                <MessageBox type="info"
                    v-if="this.recording_list == null"
                >
                    <translate>Keine Aufzeichnungen für Raum "{{ room.name }}" vorhanden</translate>
                </MessageBox>

                <form class="default" method="post" style="position: relative">
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
                            <table  class="default collapsable">
                                <thead>
                                    <tr>
                                        <th v-translate>Datum</th>
                                        <th v-translate>Aktionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(recording, index) in recording_list.default" :key="index">
                                        <td style="width: 60%">{{ recording['startTime'] }}</td>
                                        <td style="width: 40%">
                                            <div style="display: inline-block;width:80%;">
                                                <div v-if="Array.isArray(recording['playback']['format'])" style="display: flex; flex-direction: column; ">
                                                    <a v-for="(format, index) in recording['playback']['format']" :key="index"
                                                    class="meeting-recording-url" target="_blank"
                                                    :href="format['url']">
                                                        <translate>Aufzeichnung ansehen</translate>
                                                        {{ `(${format['type']})` }}
                                                    </a>
                                                </div>
                                                <div v-else>
                                                    <a class="meeting-recording-url" target="_blank"
                                                    :href="recording['playback']['format']['url']" v-translate>
                                                        Aufzeichnung ansehen
                                                    </a>
                                                </div>
                                            </div>
                                            <div v-if="course_config.display.deleteRecording" style="display: inline-block;width:15%; text-align: right;">
                                                <a style="cursor: pointer;" @click.prevent="deleteRecording(recording)">
                                                    <StudipIcon icon="trash" role="attention"></StudipIcon>
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
            </template>
         </MeetingDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";
import { dialog } from '@/common/dialog.mixins'


import {
    RECORDING_LIST, RECORDING_SHOW, RECORDING_DELETE,
} from "@/store/actions.type";

import {
    RECORDING_LIST_SET,
} from "@/store/mutations.type";

export default {
    name: "MeetingRecordings",

    props: ['room'],

    mixins: [dialog],

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox
    },

    data() {
        return {
            modal_message: {},
            message: ''
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
        }
    }
}
</script>
