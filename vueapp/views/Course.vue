<template>
    <div>
        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            {{ message.text }}
        </MessageBox>

        <MessageBox v-if="config && Object.keys(config).length === 0" type="error">
            <translate>
                Es ist bisher kein Meetingsserver konfiguriert. Bitte wenden
                Sie sich an eine/n Systemadministrator/in!
            </translate>
        </MessageBox>

        <template v-else>
            <MessageBox v-if="rooms_checked && !rooms_list.length && config && course_config.display.addRoom" type="info">
                <translate>
                    Bisher existieren keine Meeting-Räume für diese Veranstaltung.
                    Bitte fügen Sie einen neuen Raum hinzu.
                </translate>
            </MessageBox>

            <MessageBox v-if="!rooms_checked" type="warning">
                <span v-text="$gettext('Raumliste wird geladen...')"></span>
            </MessageBox>

            <MessageBox v-if="rooms_checked && rooms_list.length && config && course_config.display.addRoom && Object.keys(default_room).length === 0" type="info">
                <span v-text="$gettext('Wir empfehlen Ihnen, einen Raum als Standardraum zu definieren (in den Einstellung eines Raums).')"></span>
            </MessageBox>

            <MessageBox v-if="rooms_checked && !rooms_list_filtered.length && roomFilter" type="warning">
                <span v-text="$gettext('Leider konnte keinen Raum gefunden werden.')"></span>
            </MessageBox>

            <template v-if="course_config.introductions">
                <section v-for="(introduction, index) in course_config.introductions" :key="index" class="meeting-intro contentbox">
                    <header><h1 v-text="introduction.title ? introduction.title : $gettext('Einleitung')"></h1></header>
                    <section>
                        <article>
                            <span v-html="introduction.text"></span>
                        </article>
                    </section>
                </section>
            </template>

            <div class="conference-meeting" v-if="rooms_list_filtered.length">
                <MeetingComponent v-for="(room, index) in rooms_list_filtered"
                    :key="index"
                    :room="room"
                    :info="rooms_info !== undefined && rooms_info[room.id] ? rooms_info[room.id] : {}"
                    v-on:getRecording="showRecording"
                    v-on:renewRoomList="getRoomList"
                    v-on:getGuestInfo="showGuestDialog"
                    v-on:getModeratorGuestInfo="showModeratorGuestDialog"
                    v-on:getFeatures="showEditRoom"
                    v-on:setMessage="showMessage"
                    v-on:getFeedback="showFeedbackDialog"
                    v-on:displayQRCode="showQRCodeDialog">
                </MeetingComponent>
            </div>
        </template>

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

        <MeetingModeratorGuest v-if="showModeratorGuest"
            :room="showModeratorGuest"
            @done="showModeratorGuest = false"
            @cancel="showModeratorGuest = false"
        />

        <MeetingQRCodeDialog v-if="showQRCode"
            :room="showQRCode"
            @cancel="showQRCode = false"
        />

        <MeetingFolderManager v-if="showFolderManager"
            @done="showFolderManager = false"
            @cancel="showFolderManager = false"
        />

        <!-- Sidebar Contents -->
        <MountingPortal mountTo="#meeting-action-widget" name="sidebar-actions" v-if="generate_action_items.length">
            <StudipActionWidget
                :items="generate_action_items"
                @createNewRoom="createNewRoom"
                @displayFolderManager="displayFolderManager"
            />
        </MountingPortal>
        <MountingPortal mountTo="#meeting-folder-widget" name="sidebar-actions" v-if="generate_folder_widget_items.length">
            <StudipFolderWidget
                :items="generate_folder_widget_items"
                @displayFolderManager="displayFolderManager"
            />
        </MountingPortal>
        <MountingPortal mountTo="#meeting-search-widget" name="sidebar-search">
            <StudipSearchWidget
                @setRoomFilter="setRoomFilter"
                @clearRoomFilter="clearRoomFilter"
            />
        </MountingPortal>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import MeetingStatus from "@meeting/status/MeetingStatus";
import MeetingComponent from "@meeting/MeetingComponent";
import MeetingAdd from "@meeting/add/MeetingAdd";
import MeetingRecordings from "@meeting/recordings/MeetingRecordings";
import MeetingFeedback from "@meeting/feedback/MeetingFeedback";
import MeetingGuest from "@meeting/guests/MeetingGuest";
import MeetingModeratorGuest from "@meeting/guests/MeetingModeratorGuest";
import MeetingQRCodeDialog from "@meeting/qr_code/MeetingQRCodeDialog";
import MeetingFolderManager from "@meeting/folders/MeetingFolderManager";
import StudipActionWidget from '@studip/StudipActionWidget.vue';
import StudipSearchWidget from '@studip/StudipSearchWidget.vue';
import StudipFolderWidget from '@studip/StudipFolderWidget.vue';

import {
    CONFIG_COURSE_READ,
    ROOM_LIST,
    ROOM_INFO,
} from "@/store/actions.type";

import {
    ROOM_CLEAR
} from "@/store/mutations.type";

export default {
    name: "Course",

    components: {
        MeetingStatus,
        MeetingComponent,
        MeetingAdd,
        MeetingRecordings,
        MeetingFeedback,
        MeetingGuest,
        MeetingModeratorGuest,
        MeetingQRCodeDialog,
        MeetingFolderManager,
        StudipActionWidget,
        StudipSearchWidget,
        StudipFolderWidget,
    },

    computed: {
        ...mapGetters([
            'config', 'course_config', 'room',
            'rooms_list', 'rooms_info', 'rooms_checked',
            'default_room'
        ]),
        rooms_list_filtered: function() {
            let view = this;

            if (this.roomFilter != '') {
                return this.rooms_list.filter(function(entry) {
                    return (entry.name.toLowerCase().indexOf(view.roomFilter.toLowerCase()) !== -1);
                });
            } else {
                return this.rooms_list;
            }
        },
        generate_action_items() {
            let actionItems = [];
            let id = 1;
            if (this.config) {
                if (this.course_config?.display?.addRoom) {
                    actionItems.push({id: id, label: this.$gettext('Raum hinzufügen'), icon: 'add', emit: 'createNewRoom'});
                    id++;
                }
            }
            return actionItems;
        },
        generate_folder_widget_items() {
            let folderItems = [];
            let id = 1;
            if (this.config) {
                if (this.course_config?.display?.addFolder) {
                    folderItems.push({id: id, label: this.$gettext('Ordnerverwaltung'), icon: 'folder-empty', emit: 'displayFolderManager'});
                    id++;
                }
            }
            return folderItems;
        }
    },

    data() {
        return {
            message: null,
            roomFilter: '',
            createEditRoom: false,
            showRecordings: false,
            showFeedback: false,
            showGuest: false,
            showModeratorGuest: false,
            showQRCode: false,
            showFolderManager: false
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
                // Apply defaults for record features.
                if (Object.keys(this.config[room.driver]).includes('features') &&
                    Object.keys(this.config[room.driver]['features']).includes('record') &&
                    Object.keys(this.config[room.driver]['features']['record']).includes('record_setting')) {
                    let config_record_setting_features = this.config[room.driver]['features']['record']['record_setting'];
                    var default_feature_obj = {};
                    if (!Object.keys(room.features).includes('giveAccessToRecordings')) {
                        default_feature_obj = config_record_setting_features.find(m => m.name == 'giveAccessToRecordings');

                        this.$set(room.features, 'giveAccessToRecordings', ((default_feature_obj)
                            ? default_feature_obj.value
                            : true)
                        );
                    }
                    if (!Object.keys(room.features).includes('autoStartRecording')) {
                        default_feature_obj = config_record_setting_features.find(m => m.name == 'autoStartRecording');

                        this.$set(room.features, 'autoStartRecording', ((default_feature_obj)
                            ? default_feature_obj.value
                            : true)
                        );
                    }
                }

                // Apply default for group feature.
                if (Object.keys(room).includes('group_id') && room.group_id == null) {
                    room.group_id = '';
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

        showModeratorGuestDialog(room) {
            this.showModeratorGuest = room;
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
        },

        showQRCodeDialog(room) {
            this.showQRCode = room;
        },

        setRoomFilter(searchTerm) {
            this.roomFilter = searchTerm;
        },

        clearRoomFilter() {
            this.roomFilter = '';
        },

        displayFolderManager() {
            this.showFolderManager = true;
        },
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
    },

    updated () {
        $('.meeting-search-widget').toggleClass('hide', (this.rooms_list?.length < 2));
    },
};
</script>
