<template>
    <div>
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">

                    <header class="modal-header">
                        <slot name="header">
                            {{ `Aufzeichnungen für Raum ${room.name}` | i18n }}
                            <span class="modal-close-button" @click="$emit('cancel')"></span>
                        </slot>
                    </header>

                    <section class="modal-body">
                        <MessageBox type="info"
                            v-if="this.recording_list == null"
                        >
                            {{ `Keine Aufzeichnungen für Raum "${room.name}" vorhanden` | i18n }}
                        </MessageBox>

                        <form class="default" method="post" style="position: relative">
                            <fieldset v-if="Object.keys(recording_list).includes('opencast')">
                                <legend>{{ "Opencast" | i18n }}</legend>
                                <label>
                                    <a class="meeting-recording-url" target="_blank"
                                    :href="recording_list['opencast']">
                                        {{ 'Die vorhandenen Aufzeichnungen auf Opencast' | i18n}}
                                    </a>
                                </label>
                            </fieldset>
                            <fieldset v-if="Object.keys(recording_list).includes('default') && Object.keys(recording_list['default']).length">
                                <label>
                                    <table  class="default collapsable">
                                        <thead>
                                            <tr>
                                                <th>{{ "Datum" | i18n }}</th>
                                                <th>{{ "Aktionen" | i18n }}</th>
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
                                                                {{ `Aufzeichnung ansehen` | i18n}} {{ `(${format['type']})` }}
                                                            </a>
                                                        </div>
                                                        <div v-else>
                                                            <a class="meeting-recording-url" target="_blank"
                                                            :href="recording['playback']['format']['url']">
                                                                {{ `Aufzeichnung ansehen`  | i18n}}
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
                    </section>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";

import {
    RECORDING_LIST, RECORDING_SHOW, RECORDING_DELETE,
} from "@/store/actions.type";

import {
    RECORDING_LIST_SET,
} from "@/store/mutations.type";

export default {
    name: "MeetingRecordings",

    props: ['room'],

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
