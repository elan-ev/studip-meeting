<template>
    <div>
        <MessageBox v-if="course_config.introduction" type="info">
            <span v-html="course_config.introduction"></span>
        </MessageBox>

        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            {{ message.text }}
        </MessageBox>

        <MessageBox v-if="Object.keys(config_list).length === 0" type="error">
            {{ "Es ist bisher kein Meetingsserver konfiguriert. Bitte wenden Sie sich an eine/n Systemadministrator/in!" | i18n }}
        </MessageBox>

        <span v-else>
            <MessageBox v-if="rooms_checked && !rooms_list.length && config && course_config.display.addRoom" :type="'info'">
                {{ "Bisher existieren keine Meeting-Räume für diese Veranstaltung. Möchten Sie einen anlegen?" | i18n }}
                <br>
                <StudipButton type="button"  @click="createNewRoom">
                    {{ "Neuer Raum" | i18n}}
                </StudipButton>
            </MessageBox>

            <MessageBox v-if="!rooms_checked" type="warning">
                {{ "Raumliste wird geladen..." | i18n }}
            </MessageBox>

            <p>
                <StudipButton type="button" icon="add" v-if="rooms_list.length && config && course_config.display.addRoom"
                    @click="createEditRoom = true">
                    {{ 'Raum hinzufügen' | i18n }}
                </StudipButton>

                <label v-if="rooms_list.length">
                    <input type="text" :placeholder="`Räume filtern nach Name` | i18n" v-model="searchtext">
                </label>
            </p>

            <form class="default conference-meeting" v-if="rooms_list_filtered.length">
                    <MeetingComponent v-for="(room, index) in rooms_list_filtered"
                        :key="index"
                        :room="room"
                        :info="rooms_info !== undefined && rooms_info[room.id] ? rooms_info[room.id] : {}"
                        v-on:getRecording="showRecording"
                        v-on:renewRoomList="getRoomList"
                        v-on:getGuestInfo="showGuestDialog"
                        v-on:getFeatures="showEditFeatureDialog"
                        v-on:setMessage="showMessage"
                        v-on:getFeedback="showFeedbackDialog">
                    </MeetingComponent>
            </form>
        </span>

        <!-- dialogs -->
        <MeetingAdd v-if="createEditRoom"
            @done="roomEditDone"
            @cancel="createEditRoom = false"
        />

        <MeetingRecordings v-if="showRecordings"
            :room="showRecordings"
            @cancel="showRecordings = false"
        />

        <MeetingFeedback v-if="showFeedback"
            :room="showFeedback"
            @done="feedbackDone"
            @cancel="showFeedback = false"
        />

        <MeetingGuest v-if="showGuest"
            :room="showGuest"
            @done="showGuest = false"
            @cancel="showGuest = false"
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
import MeetingStatus from "@/components/MeetingStatus";
import MeetingComponent from "@/components/MeetingComponent";
import MeetingAdd from "@/components/MeetingAdd";
import MeetingRecordings from "@/components/MeetingRecordings";
import MeetingFeedback from "@/components/MeetingFeedback";
import MeetingGuest from "@/components/MeetingGuest";

import {
    CONFIG_COURSE_READ, FEEDBACK_SUBMIT,
    ROOM_LIST, ROOM_READ, ROOM_UPDATE, ROOM_CREATE,
    ROOM_JOIN, ROOM_JOIN_GUEST, ROOM_INFO,
    RECORDING_LIST, RECORDING_SHOW, RECORDING_DELETE,
} from "@/store/actions.type";

import {
    ROOM_CLEAR, RECORDING_LIST_SET,
    FEEDBACK_CLEAR, FEEDBACK_INIT
} from "@/store/mutations.type";

export default {
    name: "Course",

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
        MeetingStatus,
        MeetingComponent,
        MeetingAdd,
        MeetingRecordings,
        MeetingFeedback,
        MeetingGuest
    },

    computed: {
        ...mapGetters([
            'config', 'room', 'rooms_list', 'rooms_info', 'rooms_checked',
            'course_config', 'course_groups'
        ]),

        config_list: function() {
            return this.config;
        },

        rooms_list_filtered: function() {
            let view = this;

            if (this.searchtext != '') {
                return this.rooms_list.filter(function(entry) {
                    return (entry.name.toLowerCase().indexOf(view.searchtext.toLowerCase()) !== -1);
                });
            } else {
                return this.rooms_list;
            }
        }
    },

    data() {
        return {
            message: null,
            modal_message: {},
            searchtext: '',
            createEditRoom: false,
            showRecordings: false,
            showFeedback: false,
            showGuest: false
        }
    },

    methods: {
        getRoomList() {
            this.$store.dispatch(ROOM_LIST);
            this.$store.dispatch(ROOM_INFO);
        },

        showEditFeatureDialog(room) {
            this.$store.commit(ROOM_CLEAR);

            // check, if there are any features for this driver at all!
            if (this.config_list[room.driver]['features'] !== undefined) {
                if (Object.keys(this.config_list[room.driver]['features']).includes('record')
                    && !Object.keys(room.features).includes('giveAccessToRecordings')
                ) {
                    var default_feature_obj = this.config_list[room.driver]['features']['record']
                        .find(m => m.name == 'giveAccessToRecordings');

                    this.$set(room.features, 'giveAccessToRecordings', ((default_feature_obj)
                        ? default_feature_obj.value
                        : true)
                    );
                }
            }

            this.$set(this.room, 'driver_name', room.driver);
            this.$set(this.room, 'features', room.features);
            this.$set(this.room, 'join_as_moderator', room.join_as_moderator);
            this.$set(this.room, 'name', room.name);
            this.$set(this.room, 'server_index', room.server_index);
            this.$set(this.room, 'id', room.id);
            this.$set(this.room, "group_id" , ((Object.keys(room).includes('group_id') && room.group_id != undefined) ? room.group_id : ""));
            this.modal_message = {};

            this.createEditRoom = true;
        },

        showMessage(message) {
            this.message = message;
        },

        showRecording(room) {
            this.showRecordings = room;
        },

        showFeedbackDialog(room) {
            this.showFeedback = room;
        },

        showGuestDialog(room) {
            this.showGuest = room;
        },

        createNewRoom() {
            this.$store.commit(ROOM_CLEAR);
            this.createEditRoom = true;
        },

        roomEditDone(params) {
            this.createEditRoom = false;

            if (params != undefined && params.message != undefined) {
                this.showMessage(params.message);
            }

            this.getRoomList();
        },

        feedbackDone(params) {
            this.showFeedback = false;

            if (params != undefined && params.message != undefined) {
                this.showMessage(params.message);
            }
        }
    },

    mounted() {
        store.dispatch(CONFIG_COURSE_READ, CID);
        this.getRoomList();

        this.interval = setInterval(() => {
            this.getRoomList();
        }, 300000);
    },

    beforeDestroy () {
        clearInterval(this.interval)
    }
};
</script>
