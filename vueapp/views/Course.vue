<template>
    <div>
        <MessageBox v-if="course_config.introduction" type="info">
            <span v-html="course_config.introduction"></span>
        </MessageBox>

        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            {{ message.text }}
        </MessageBox>

        <MessageBox v-if="Object.keys(config).length === 0" type="error">
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
                    @click="createNewRoom">
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
                    v-on:getFeatures="showEditRoom"
                    v-on:setMessage="showMessage"
                    v-on:getFeedback="showFeedbackDialog">
                </MeetingComponent>
            </form>
        </span>

        <!-- dialogs -->
        <MeetingAdd v-if="createEditRoom"
            :room="createEditRoom"
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
import MessageBox from "@/components/MessageBox";
import MeetingStatus from "@/components/MeetingStatus";
import MeetingComponent from "@/components/MeetingComponent";
import MeetingAdd from "@/components/MeetingAdd";
import MeetingRecordings from "@/components/MeetingRecordings";
import MeetingFeedback from "@/components/MeetingFeedback";
import MeetingGuest from "@/components/MeetingGuest";

import {
    CONFIG_COURSE_READ, ROOM_LIST, ROOM_INFO,
} from "@/store/actions.type";

import {
    ROOM_CLEAR
} from "@/store/mutations.type";

export default {
    name: "Course",

    components: {
        StudipButton,
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
            'config', 'course_config', 'room',
            'rooms_list', 'rooms_info', 'rooms_checked'
        ]),

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

        showEditRoom(room) {
            // check, if there are any features for this driver at all!
            if (this.config[room.driver]['features'] !== undefined) {
                if (Object.keys(this.config[room.driver]['features']).includes('record')
                    && !Object.keys(room.features).includes('giveAccessToRecordings')
                ) {
                    var default_feature_obj = this.config[room.driver]['features']['record']
                        .find(m => m.name == 'giveAccessToRecordings');

                    this.$set(room.features, 'giveAccessToRecordings', ((default_feature_obj)
                        ? default_feature_obj.value
                        : true)
                    );
                }
            }

            this.createEditRoom = room;
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
            this.createEditRoom = this.room;
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
