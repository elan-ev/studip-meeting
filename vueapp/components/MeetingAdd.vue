<template>
    <div v-if="config" id="conference-meeting-create">
        <MeetingDialog :title="$gettext('Raumkonfiguration')" @close="cancelAddRoom($event)">
            <template v-slot:content>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <MessageBox v-else-if="room['driver'] && !Object.keys(config[room['driver']]['servers']).length"
                        type="error" v-translate>
                    Es gibt keine Server für dieses Konferenzsystem, bitte wählen Sie ein anderes Konferenzsystem
                </MessageBox>

                <form class="default" @keyup="roomFormSubmit($event)" style="position: relative">
                    <fieldset id="room_settings_section">
                        <legend v-translate>
                            Raumeinstellung
                        </legend>
                        <label>
                            <span class="required" v-translate>
                                Raumname
                            </span>
                            <input type="text" v-model.trim="room['name']" id="name">
                        </label>
                        <label>
                            <input type="checkbox"
                            id="default"
                            :true-value="1"
                            :false-value="0"
                            v-model="room['is_default']">
                            <translate>
                                Als Standardraum markieren
                            </translate>
                            <StudipTooltipIcon
                                :text="$gettext('Ein Standardraum wird immer als erster Raum in der Raumliste angezeigt.' +
                                ' Auf dem Startseitenwidget werden die gebuchten Termine dieser Veranstaltung gelistet und ein Direktlink zum Standardraum angeboten.' +
                                ' Wenn Sie diesen Raum als Standard markieren, wird ein bereits vorhandener Standardraum automatisch abgewählt.')">
                            </StudipTooltipIcon>
                        </label>
                    </fieldset>

                    <fieldset id="server_settings_section" class="collapsable" :class="{collapsed: !isAddRoom}" v-if="show_server_settings_section">
                        <legend v-translate>
                            Konferenzsystem
                        </legend>

                        <label v-if="Object.keys(config).length > 1">
                            <span class="required" v-translate>
                                Konferenzsystem
                            </span>

                            <select id="driver" v-model="room['driver']" @change.prevent="handleServerDefaults" :disabled="Object.keys(config).length == 1">
                                <option value="" disabled v-translate>
                                    Bitte wählen Sie ein Konferenzsystem aus
                                </option>
                                <option v-for="(driver_config, driver) in availableServers" :key="driver"
                                        :value="driver"
                                        :disabled="Object.keys(config[driver]['servers']).length == 1
                                                    && ((config[driver]['server_course_type']
                                                    && config[driver]['server_course_type'][0] &&
                                                    !config[driver]['server_course_type'][0]['valid']) || !config[driver]['servers'][0])">
                                        {{ driver_config['display_name'] }}
                                        <template v-if="Object.keys(config[driver]['servers']).length == 1">
                                            <span v-if="config[driver]['server_details'] && config[driver]['server_details'][0]
                                                && config[driver]['server_details'][0]['label'] && config[driver]['server_details'][0]['label'] != ''"
                                                v-translate="{
                                                    label: config[driver]['server_details'][0]['label']
                                                }"
                                            >
                                                (%{ label })
                                            </span>
                                            <span v-if="config[driver]['server_course_type'] && config[driver]['server_course_type'][0] &&
                                                    config[driver]['server_course_type'][0]['name']"
                                                v-translate="{
                                                    name: config[driver]['server_course_type'][0]['name']
                                                }"
                                            >
                                                (für %{ name })
                                            </span>
                                            <span v-translate
                                                v-if="!config[driver]['servers'][0] || (config[driver]['server_course_type'] && config[driver]['server_course_type'][0] &&
                                                        !config[driver]['server_course_type'][0]['valid'])"
                                            >
                                            - nicht verfügbar
                                            </span>
                                        </template>
                                </option>
                            </select>
                        </label>

                        <label v-if="room['driver']
                                && Object.keys(config[room['driver']]['servers']).length > 1"
                        >
                            <span class="required" v-translate>
                                Verfügbare Server
                            </span>

                            <select id="server_index" v-model="room['server_index']" @change.prevent="handleServerDefaults"
                                :disabled="Object.keys(config[room['driver']]['servers']).length == 1">
                                <option value="" disabled v-translate>
                                    Bitte wählen Sie einen Server aus
                                </option>
                                <option v-for="(server_config, server_index) in config[room['driver']]['servers']" :key="server_index"
                                        :value="'' + server_index"
                                        :disabled="!server_config || (config[room['driver']]['server_course_type'] && config[room['driver']]['server_course_type'][server_index] &&
                                                    !config[room['driver']]['server_course_type'][server_index]['valid'])"
                                        >
                                        <span v-if="config[room['driver']]['server_details'] && config[room['driver']]['server_details'][server_index]
                                            && config[room['driver']]['server_details'][server_index]['label']
                                            && config[room['driver']]['server_details'][server_index]['label'] != ''"
                                            v-translate="{
                                                label: config[room['driver']]['server_details'][server_index]['label']
                                            }"
                                        >
                                            %{ label }
                                        </span>
                                        <translate v-else>Server {{ (server_index + 1) }}</translate>
                                        <span v-if="config[room['driver']]['server_defaults'] && config[room['driver']]['server_defaults'][server_index]
                                                    &&  config[room['driver']]['server_defaults'][server_index]['maxAllowedParticipants']"
                                            v-translate="{
                                                count: config[room['driver']]['server_defaults'][server_index]['maxAllowedParticipants']
                                            }"
                                        >
                                            (max. %{ count } Teilnehmende)
                                        </span>
                                        <span v-if="config[room['driver']]['server_course_type'] && config[room['driver']]['server_course_type'][server_index] &&
                                                    config[room['driver']]['server_course_type'][server_index]['name']"
                                            v-translate="{
                                                name: config[room['driver']]['server_course_type'][server_index]['name']
                                            }"
                                        >
                                            (für %{ name })
                                        </span>
                                        <span v-translate
                                            v-if="!server_config || (config[room['driver']]['server_course_type'] && config[room['driver']]['server_course_type'][server_index] &&
                                                    !config[room['driver']]['server_course_type'][server_index]['valid'])"
                                        >
                                           - nicht verfügbar
                                        </span>
                                </option>
                            </select>
                        </label>
                        <label v-if="room['driver'] && room['server_index'] && config[room['driver']]['server_details']
                                && config[room['driver']]['server_details'][room['server_index']]
                                && config[room['driver']]['server_details'][room['server_index']]['description']
                                && config[room['driver']]['server_details'][room['server_index']]['description'] != ''"
                        >
                            <strong v-translate>
                                Serverbeschreibung
                            </strong>
                            <div v-translate style="word-break: break-word !important;"
                                v-text="config[room['driver']]['server_details'][room['server_index']]['description']"
                            ></div>
                        </label>
                    </fieldset>

                    <fieldset id="roomsize_settings_section" class="collapsable"
                            v-if="room['driver'] && Object.keys(config[room['driver']]).includes('features')
                                && Object.keys(config[room['driver']]['features']).includes('create')
                                && Object.keys(config[room['driver']]['features']['create']).includes('roomsize')
                                && Object.keys(config[room['driver']]['features']['create']['roomsize']).length">
                        <legend v-text="$gettext('Raumgröße und Leistung')"></legend>
                        <template v-for="(feature, index) in config[room['driver']]['features']['create']['roomsize']">
                            <MeetingAddLabelItem :ref="feature['name']" :room="room" :feature="feature"
                                :maxAllowedParticipants="maxAllowedParticipants"
                                :minParticipants="minParticipants"
                                @checkPresets="checkPresets"
                                :key="index"/>
                        </template>
                    </fieldset>

                    <fieldset id="recording_settings_section" class="collapsable collapsed" v-if="show_recording_settings_section">
                        <legend v-text="$gettext('Aufzeichnung')"></legend>
                        <template v-for="(feature, index) in config[room['driver']]['features']['record']['record_setting']">
                            <MeetingAddLabelItem :ref="feature['name']" :room="room" :feature="feature" :maxDuration="maxDuration"
                                @labelClicked="labelClickHandler"
                                :badge="(feature['name'] == 'record' && Object.keys(config[room['driver']]).includes('opencast') && config[room['driver']]['opencast'] == '1'
                                            && feature['info'].toLowerCase().includes('opencast')) ? {show: true, text: $gettext('beta')} : {}"
                                :key="index"/>
                        </template>
                    </fieldset>

                    <fieldset v-if="room['driver']" id="privacy_settings_section" class="collapsable collapsed">
                        <legend v-text="$gettext('Berechtigungen')"></legend>
                        <label>
                            <input type="checkbox"
                                id="join_as_moderator"
                                true-value="1"
                                false-value="0"
                                v-model="room['join_as_moderator']">
                                <translate>
                                    Alle Teilnehmenden haben Moderationsrechte
                                </translate>
                        </label>
                        <span v-if="Object.keys(config[room['driver']]).includes('features')
                            && Object.keys(config[room['driver']]['features']).includes('create')
                            && Object.keys(config[room['driver']]['features']['create']).includes('privacy')
                            && Object.keys(config[room['driver']]['features']['create']['privacy']).length"
                        >
                            <template v-for="(feature, index) in config[room['driver']]['features']['create']['privacy']">
                                <MeetingAddLabelItem :ref="feature['name']" :room="room" :feature="feature" :key="index"
                                    :inlineFeatureWarningIcon="(feature['name'] == 'room_anyone_can_start' && printRoomStartWarning()) ? {messagebox_id: 'room_start_warning'} : {}"
                                    @toggleInlineFeatureWarning="toggleInlineFeatureWarning"
                                />
                                <MessageBox
                                    v-if="feature['name'] == 'room_anyone_can_start' && printRoomStartWarning()"
                                    :key="'msgbx' + index" id="room_start_warning"
                                    class="inline-feature-warning"
                                    type="warning"
                                    @hide="toggleInlineFeatureWarning('room_start_warning')"
                                >
                                    <span v-text="$gettext('Es ist bei Aufzeichnungen dringend empfohlen die Veranstaltung und somit die Aufzeichnungen erst zu beginnen, ' +
                                        'wenn Lehrende die Videokonferenz betreten.')"></span>
                                </MessageBox>
                            </template>
                        </span>
                    </fieldset>

                    <fieldset id="group_settings_section" class="collapsable collapsed" v-if="room['driver'] && Object.keys(course_groups).length">
                        <legend v-text="$gettext('Gruppenraum')"></legend>

                        <label>
                            <translate>Wählen sie eine zugehörige Gruppe aus</translate>
                            <select id="gruppen" v-model.trim="room.group_id">
                                <option value="" v-translate>
                                    Keine Gruppe
                                </option>

                                <option v-for="(gname, gid) in course_groups" :key="gid"
                                        :value="gid">
                                        {{ gname }}
                                </option>
                            </select>
                        </label>
                    </fieldset>

                    <fieldset id="extended_settings_section" class="collapsable collapsed"
                            v-if="room['driver'] && Object.keys(config[room['driver']]).includes('features')
                                && Object.keys(config[room['driver']]['features']).includes('create')
                                && Object.keys(config[room['driver']]['features']['create']).includes('extended_setting')
                                && Object.keys(config[room['driver']]['features']['create']['extended_setting']).length">
                        <legend v-text="$gettext('Erweiterte Einstellungen')"></legend>
                        <template v-for="(feature, index) in config[room['driver']]['features']['create']['extended_setting']">
                            <MeetingAddLabelItem :ref="feature['name']" :room="room" :feature="feature" :key="index" />
                        </template>
                    </fieldset>

                    <fieldset id="presentation_sildes_section" class="collapsable collapsed" v-if="hasPresentationSetting != false">
                        <legend v-text="$gettext('Präsentationsfolien')"></legend>
                        <template v-if="hasPresentationSetting == 'all' || hasPresentationSetting == 'setting'">
                            <template v-for="(feature, index) in config[room['driver']]['features']['create']['presentation_sildes']">
                                <MeetingAddLabelItem :ref="feature['name']" :room="room" :feature="feature" :key="index" />
                            </template>
                        </template>


                        <label v-if="hasPresentationSetting == 'all' || hasPresentationSetting == 'preupload'">
                            <h3 v-translate>
                                <translate>
                                    Automatisches hochladen von Materialien
                                </translate>
                                <StudipTooltipIcon :text="$gettext('Verknüpfen Sie einen Ordner mit diesem Raum. '
                                    + 'Es werden alle Dateien in diesem Ordner automatisch zu Beginn des Meetings hochgeladen. '
                                    + 'Sie können im Meeting zwischen den Dateien wechseln.')">
                                </StudipTooltipIcon>
                            </h3>

                            <div>
                                <translate>Aktuell ausgewählter Ordner: </translate>

                                <span v-if="room.folder_id && room.folder_id == folder.id && folder.name != ''">
                                    {{ folder.name }}
                                </span>
                                <span v-else v-translate>
                                    Kein Ordner
                                </span>
                            </div>
                            <div class="course-folder-container">
                                <table class="default documents">
                                        <caption>
                                        <div class="caption-container meetings-caption">
                                            <a :title="$gettext('Zum Hauptordner - Ordnerauswahl aufheben')"
                                                @click.prevent="FolderHandler('topFolder')">
                                                <StudipIcon class="folder-icon" icon="folder-home-full"
                                                    role="clickable" size="20"></StudipIcon>
                                            </a>
                                            <template v-if="Object.keys(folder).includes('breadcrumbs')">
                                                <template v-for="(bcname, bcid) in folder.breadcrumbs">
                                                    &nbsp;/&nbsp;
                                                    <a  :key="bcid"
                                                        @click.prevent="(room.folder_id && room.folder_id == bcid) ? null : FolderHandler(bcid)">
                                                        {{bcname}}
                                                    </a>
                                                </template>
                                            </template>
                                        </div>
                                        </caption>
                                    <thead>
                                        <tr>
                                            <th v-translate>Name</th>
                                        </tr>
                                    </thead>
                                    <template v-if="folder_has_content">
                                        <tbody>
                                            <template v-if="Object.keys(folder['subfolders']).length > 0">
                                                <tr v-for="(sfinfo, sfid) in folder.subfolders" :key="sfid" :id="'row_folder_' + sfid">
                                                    <td>
                                                        <a :title="$gettext('Als aktueller Ordner auswählen')"
                                                            @click.prevent="FolderHandler(sfid)">
                                                            <StudipIcon v-if="sfinfo.icon" :icon="sfinfo.icon"
                                                                role="clickable" size="16"></StudipIcon>
                                                            <span>{{sfinfo.name}}</span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            </template>
                                            <template v-if="Object.keys(folder['files']).length <= numFileInFolderLimit || showFilesInFolder">
                                                <tr v-for="(finfo, fid) in folder.files" :key="fid">
                                                    <td>
                                                        <div>
                                                            <StudipIcon v-if="finfo.icon" :icon="finfo.icon"
                                                                role="clickable" size="16"></StudipIcon>
                                                            <span>{{finfo.name}}</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                            <template v-else>
                                                <tr class="empty">
                                                    <td>
                                                        <span v-if="Object.keys(folder).includes('files')
                                                            && Object.keys(folder['files']).length > numFileInFolderLimit"
                                                            v-translate="{
                                                                count: Object.keys(folder['files']).length
                                                            }"
                                                        >
                                                            In diesem Ordner befinden sich %{ count } Dateien <br>
                                                            die aus Gründen der Übersichtlichkeit ausgeblendet wurden. <br>
                                                            Wählen sie "Alle Dateien anzeigen" um diese Dateien aufzulisten
                                                        </span>
                                                        <span v-else v-translate>
                                                            Dieser Ordner ist leer
                                                        </span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </template>
                                    <template v-else>
                                        <tbody>
                                            <tr class="empty">
                                                <translate>
                                                    Dieser Ordner ist leer
                                                </translate>
                                            </tr>
                                        </tbody>
                                    </template>

                                    <tfoot>
                                            <tr>
                                            <td>
                                                <div class="footer-container">
                                                    <a class="button" @click.prevent="showAddNewFolder = true" v-translate>
                                                        Neuer Ordner
                                                    </a>
                                                    <a v-if="Object.keys(folder).includes('files') && Object.keys(folder['files']).length > numFileInFolderLimit" @click.prevent="showFilesInFolder = !showFilesInFolder" class="right">
                                                        <StudipIcon :icon="(showFilesInFolder) ? 'checkbox-checked' : 'checkbox-unchecked'"
                                                            role="clickable" size="14"></StudipIcon>
                                                        <span v-translate>Alle Dateien anzeigen</span>
                                                    </a>
                                                </div>
                                            </td>
                                            </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </label>
                    </fieldset>
                </form>
            </template>
            <template v-slot:buttons>
                <StudipButton v-if="room['id']" icon="accept" type="button"
                    v-on:click="editRoom($event)"
                    class="ui-button ui-corner-all ui-widget"
                    v-translate
                >
                    Änderungen speichern
                </StudipButton>

                <StudipButton v-else icon="accept" type="button"
                    v-on:click="addRoom($event)"
                    class="ui-button ui-corner-all ui-widget"
                    v-translate
                >
                    Raum erstellen
                </StudipButton>

                <StudipButton icon="cancel" type="button"
                    v-on:click="cancelAddRoom($event)"
                    class="ui-button ui-corner-all ui-widget"
                    v-translate
                >
                    Abbrechen
                </StudipButton>
            </template>
        </MeetingDialog>

        <!-- dialog -->
        <MeetingAddNewFolder v-if="showAddNewFolder"
            :folder="folder"
            @done="showAddNewFolder = false"
            @cancel="showAddNewFolder = false"
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
import MeetingAddNewFolder from "@/components/MeetingAddNewFolder";
import MeetingAddLabelItem from "@/components/MeetingAddLabelItem";

import { dialog } from '@/common/dialog.mixins'

import {
    ROOM_LIST, ROOM_UPDATE, ROOM_CREATE, FOLDER_READ
} from "@/store/actions.type";

import {
    ROOM_CLEAR
} from "@/store/mutations.type";

export default {
    name: "MeetingAdd",

    props: ['room'],

    mixins: [dialog],

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
        MeetingAddNewFolder,
        MeetingAddLabelItem
    },

    data() {
        return {
            modal_message: {},
            message: '',
            showAddNewFolder: false,
            showFilesInFolder: false,
            numFileInFolderLimit: 5,
            minParticipants: 20,
            maxDuration: 1440,
            isAddRoom: true,
        }
    },

    computed: {
        ...mapGetters([
            'config',
            'course_config', 'course_groups', 'folder'
        ]),

        show_server_settings_section() {
            return ((Object.keys(this.config).length > 1) || (this.room?.driver && this.config[this.room.driver]?.servers?.length > 1));
        },

        show_recording_settings_section() {
            return this.room?.driver && (parseInt(this.config[this.room.driver]?.record) || parseInt(this.config[this.room.driver]?.opencast)) && this.config[this.room.driver]?.features?.record?.record_setting?.length;
        },

        availableServers() {
            let availableServers = {};

            for (let server in this.config) {
                if (this.config[server].enable) {
                    availableServers[server] = this.config[server];
                }
            }

            return availableServers;
        },

        maxAllowedParticipants() {
            var max = 0;
            if (Object.keys(this.config[this.room['driver']]).includes('server_defaults')
                    && this.room['server_index']
                    && this.config[this.room['driver']]['server_defaults'][this.room['server_index']] != undefined
                    && Object.keys(this.config[this.room['driver']]['server_defaults'][this.room['server_index']]).includes('maxAllowedParticipants')) {
                max = parseInt(this.config[this.room['driver']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants']);
            }
            return max;
        },

        hasPresentationSetting() {
            if (!this.room || !this.room.driver) {
                return false;
            }

            let has_setting = this.config && this.room?.driver
                                    && this.config[this.room['driver']]?.features?.create?.presentation_sildes?.length > 0

            let has_preupload = this.config && this.room?.driver
                                    && this.config[this.room['driver']]?.preupload == true;

            if (has_setting && has_preupload) {
                return 'all';
            } else if (has_setting && !has_preupload) {
                return 'setting';
            } else if (!has_setting && has_preupload) {
                return 'preupload';
            } else {
                return false;
            }
        },

        folder_has_content() {
            let has_files = this.folder?.files?.length;
            let has_subfolders = this.folder?.subfolders && Object.keys(this.folder.subfolders).length > 0;
            return this.folder && (has_files || has_subfolders);
        }
    },

    mounted() {
        this.modal_message = {};
        this.setDriver();
        this.getFolders();
        this.getCalledArea();
    },

    methods: {
        getCalledArea() {
            this.isAddRoom = (this.room.driver == '') ? true : false;
        },

        setDriver() {
            if (Object.keys(this.config).length == 1) {
                this.$set(this.room, "driver" , Object.keys(this.config)[0]);
                this.handleServerDefaults();
            }

            // check, if the selected server is still available for this room
            if (this.room['driver'] !== undefined
                && this.config[this.room['driver']] !== undefined
                && this.config[this.room['driver']]['server_defaults'][this.room['server_index']] === undefined
            ) {
                this.$set(this.room, "server_index" , "0");
            }
        },

        handleServerDefaults() {
            //mandatory server selection when there is only one server
            if (this.room['driver'] && Object.keys(this.config[this.room['driver']]['servers']).length == 1) {
                this.$set(this.room, "server_index" , "0");
            }

            // auto-selecting server if there is only one avaialble for this course!
            if (this.room['driver'] && Object.keys(this.config[this.room['driver']]).includes('server_course_type')
                && Object.keys(this.config[this.room['driver']]['server_course_type']).length > 1) {
                const server_course_types_validataion = this.config[this.room['driver']]['server_course_type'].map((sct) => sct.valid == true);
                if (server_course_types_validataion.filter(Boolean).length == 1) {
                    var server_index = server_course_types_validataion.findIndex((sct) => sct == true);
                    if (server_index != -1) {
                        this.$set(this.room, "server_index" , server_index.toString());
                    }
                }
            }

            //set default features
            this.$set(this.room, "features" , {});

            if (Object.keys(this.config[this.room['driver']]).includes('features')) {
                //set default value of features
                if (Object.keys(this.config[this.room['driver']]['features']).includes('create') &&
                    Object.keys(this.config[this.room['driver']]['features']['create']).length) {

                    //applying first level of defaults for create features - important
                    Object.keys(this.config[this.room['driver']]['features']['create']).forEach(section_name => { //apply all values for room feature!
                        let section = this.config[this.room['driver']]['features']['create'][section_name];
                        section.forEach(feature => {
                            // set all selects to first entry
                            if (typeof feature.value === 'object' && !Array.isArray(feature.value)) {
                                this.room['features'][feature['name']] = Object.keys(feature['value'])[0];
                            } else {
                                this.$set(this.room['features'], feature.name , feature.value);
                            }
                        });
                    });

                    //Applying Second level of defaults from server defaults - if there is any but highly important!
                    if (this.room['server_index'] && Object.keys(this.config[this.room['driver']]).includes('server_defaults') &&
                        Object.keys(this.config[this.room['driver']]['server_defaults']).length &&
                        Object.keys(this.config[this.room['driver']]['server_defaults']).includes(this.room['server_index'])) {
                        for (const [feature_name, feature_value] of Object.entries(this.config[this.room['driver']]['server_defaults'][this.room['server_index']])) {
                            if (feature_name != 'maxAllowedParticipants' && feature_name != 'totalMembers') {
                                this.$set(this.room['features'], feature_name , feature_value);
                            }
                        }
                    }
                }
                if (Object.keys(this.config[this.room['driver']]['features']).includes('record') &&
                    Object.keys(this.config[this.room['driver']]['features']['record']).length) {

                    Object.keys(this.config[this.room['driver']]['features']['record']).forEach(section_name => { //apply all values for room feature!
                        let section = this.config[this.room['driver']]['features']['record'][section_name];
                        section.forEach(feature => {
                            // set all selects to first entry
                            if (typeof feature.value === 'object' && !Array.isArray(feature.value)) {
                                this.room['features'][feature['name']] = Object.keys(feature['value'])[0];
                            } else {
                                this.$set(this.room['features'], feature.name , feature.value);
                            }
                        });
                    });
                }
            }
        },

        checkPresets() {
            if (this.room['driver'] && this.room['server_index']
                && Object.keys(this.config[this.room['driver']]).includes('server_presets')
                && Object.keys(this.config[this.room['driver']]['server_presets']).includes(this.room['server_index'])) {
                for (const [size, featues] of  Object.entries(this.config[this.room['driver']]['server_presets'][this.room['server_index']])) {
                    if (this.room['features'] && this.room['features']['maxParticipants'] && parseInt(this.room['features']['maxParticipants']) >= parseInt(featues['minParticipants'])) {
                        for (const [feature_name, featues_value] of Object.entries(featues)) {
                            if (feature_name != 'minParticipants') {
                                this.$set(this.room['features'], feature_name, featues_value);
                            }
                        }
                    }
                }
            }
        },

        validateMinMaxParticipants() {
            var isValid = true;
            this.$set(this.modal_message, "text" , "");
            var err_message = '';
            if (this.room['driver'] && this.room['server_index'] && this.room['features'] && this.room['features']['maxParticipants']) {
                if ( Object.keys(this.config[this.room['driver']]).includes('server_defaults')
                && Object.keys(this.config[this.room['driver']]['server_defaults'][this.room['server_index']]).includes('maxAllowedParticipants')
                && parseInt(this.room['features']['maxParticipants']) > parseInt(this.config[this.room['driver']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants'])) {
                    this.$set(this.room['features'], 'maxParticipants', this.config[this.room['driver']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants']);
                    var maxAllowedParticipants = this.config[this.room['driver']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants'];
                    err_message = `Teilnehmerzahl darf ${maxAllowedParticipants} nicht überschreiten`.toLocaleString();
                    isValid = false;
                }

                if (parseInt(this.room['features']['maxParticipants']) < parseInt(this.minParticipants)) {
                    this.$set(this.room['features'], 'maxParticipants', parseInt(this.minParticipants));
                    err_message = `Teilnehmerzahl soll ${this.minParticipants} nicht unterschreiten`.toLocaleString();
                    isValid = false;
                }
            }

            if (!isValid) {
                this.modal_message.type = 'error';
                setTimeout(() => {
                    this.modal_message.text = err_message;
                }, 150);
            }

            return isValid;
        },

        validateFeatureInputs() {
            var isValid = true;
            var invalidInputs = [];
            this.$set(this.modal_message, "text" , "");
            if (this.config && this.room?.driver && this.config[this.room.driver]?.features && this.room?.features) {
                //loop through the config features...
                for (const [config_feature_cat, config_feature_contents] of Object.entries(this.config[this.room['driver']]['features'])) {
                    for (const [section, section_contents] of Object.entries(config_feature_contents)) {
                        if (Array.isArray(section_contents)) {
                            //loop through room features
                            section_contents.forEach(config_feature => {
                                if (Object.keys(this.room['features']).includes(config_feature.name)) {
                                    //Apply validation based on type of input
                                    switch (typeof config_feature.value) {
                                        case 'boolean':
                                            if ((typeof this.room['features'][config_feature.name] == 'string' &&
                                                this.room['features'][config_feature.name] != 'true' && this.room['features'][config_feature.name] != 'false')
                                                || (typeof this.room['features'][config_feature.name] != 'string'
                                                    && typeof this.room['features'][config_feature.name] != 'boolean')) {
                                                invalidInputs.push(config_feature.display_name)
                                                isValid = false;
                                                this.$set(this.room['features'], config_feature.name, config_feature.value);
                                            }
                                        break;
                                        case 'number':
                                            var value = parseInt(this.room['features'][config_feature.name]);
                                            var range_value = (config_feature.name == 'maxParticipants') ? -1 : 0;
                                            if (Number.isInteger(value) && value > range_value) {
                                                this.$set(this.room['features'], config_feature.name, value);
                                            } else {
                                                invalidInputs.push(config_feature.display_name)
                                                isValid = false;
                                                this.$set(this.room['features'], config_feature.name, config_feature.value);
                                            }
                                        break;
                                        case 'object':
                                            if (!Object.keys(config_feature.value).includes(this.room['features'][config_feature.name])) {
                                                invalidInputs.push(config_feature.display_name)
                                                isValid = false;
                                            }
                                        break;
                                        default: // Should be String
                                            //sanitize - html tags
                                            var value = this.room['features'][config_feature.name];
                                            var text = '';
                                            if (config_feature.name == 'welcome') {
                                                text = value.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
                                            } else {
                                                text = value.replace(/(<([^>]+)>)/gi, "");
                                            }
                                            this.$set(this.room['features'], config_feature.name, text);
                                    }
                                }
                            });
                        }
                    }
                }
            }

            if (invalidInputs.length > 0) {
                var invalid_inputs_str = invalidInputs.join('), (');
                this.$set(this.modal_message, "text" , "");
                this.$set(this.modal_message, "type" , "error");
                setTimeout(() => {
                    this.$set(this.modal_message, "text" , `Bitte beachten Sie die folgenden Felder (Eingaben auf Standard zurückgesetzt): (${invalid_inputs_str})`.toLocaleString());
                }, 150);
            }
            return isValid;
        },

        validateMinMaxDuration() {
            var isValid = true;
            this.$set(this.modal_message, "text" , "");
            var err_message = '';

            if (this.maxDuration && this.room['driver'] && this.room['server_index'] && this.room['features'] && this.room['features']['duration']) {
                if (this.room['features']['duration'] > this.maxDuration) {
                    err_message = `Konferenzdauer darf ${this.maxDuration} Minuten nicht überschreiten`.toLocaleString();
                    isValid = false;
                    this.$set(this.room['features'], 'duration', this.maxDuration);
                }
            }

            if (!isValid) {
                this.modal_message.type = 'error';
                setTimeout(() => {
                    this.modal_message.text = err_message;
                }, 150);
            }

            return isValid;
        },

        addRoom(event) {
            if (event) {
                event.preventDefault();
            }

            if (!this.validateFeatureInputs()) {
                return;
            }

            if (!this.validateMinMaxParticipants()) {
                return;
            }

            if (!this.validateMinMaxDuration()) {
                return;
            }

            var empty_fields_arr = [];
            for (var key in this.room) {
                if (key != 'join_as_moderator' && key != 'features' && this.room[key] === '' ) {
                    $(`#${key}`).prev().hasClass('required') ? empty_fields_arr.push($(`#${key}`).prev().text()) : '';
                }
            }
            if ( !empty_fields_arr.length ) {
                this.modal_message = {};
                this.$store.dispatch(ROOM_CREATE, this.room)
                .then(({ data }) => {
                    this.message = data.message;
                    if (this.message.type == 'error') {
                        this.$set(this.modal_message, "type" , "error");
                        this.$set(this.modal_message, "text" , this.message.text);
                    } else {
                        store.dispatch(ROOM_LIST);
                        this.$emit('done', { message: this.message });
                    }
                }).catch (({error}) => {
                    this.$emit('cancel');
                });
            } else {
                var empty_fields_str = empty_fields_arr.join('), (');
                this.$set(this.modal_message, "type" , "error");
                this.$set(this.modal_message, "text" , `Bitte füllen Sie folgende Felder aus: (${empty_fields_str})`.toLocaleString());
            }
        },

        cancelAddRoom(event) {
            if (event) {
                event.preventDefault();
            }

            this.$store.commit(ROOM_CLEAR);
            this.$emit('cancel');
        },

        roomFormSubmit(event) {
            if (event.key == 'Enter' && $(event.target).is('input')) {
                if (Object.keys(this.room).includes('id')) {
                    this.editRoom(event);
                } else {
                    this.addRoom(event);
                }
            }
        },

        editRoom(event) {
            if (event) {
                event.preventDefault();
            }

            if (!this.validateFeatureInputs()) {
                return;
            }

            if (!this.validateMinMaxParticipants()) {
                return;
            }

            if (!this.validateMinMaxDuration()) {
                return;
            }

            this.$store.dispatch(ROOM_UPDATE, this.room)
            .then(({ data }) => {
                this.message = data.message;
                if (data.message.type == 'success') {
                    this.$emit('done', { message: this.message });
                } else {
                    this.modal_message = data.message;
                }
            }).catch (({error}) => {
                this.$emit('cancel');
            });
        },

        getFolders(folder_id = '') {
            if (folder_id == '') {
                folder_id = (this.room.folder_id ? this.room.folder_id : 'topFolder');
            }
            this.$store.dispatch(FOLDER_READ, folder_id);
        },

        FolderHandler(to) {
            this.$set(this.room, "folder_id" , (to == 'topFolder' ? null : to));
            this.getFolders(to);
        },

        printRoomStartWarning() {
            if (Object.keys(this.room).includes('driver') &&
                ((Object.keys(this.config[this.room['driver']]).includes('record') && JSON.parse(this.config[this.room['driver']]['record']) == true) ||
                (Object.keys(this.config[this.room['driver']]).includes('opencast') && JSON.parse(this.config[this.room['driver']]['opencast']) == true)) &&
                Object.keys(this.room).includes('features') &&
                Object.keys(this.room['features']).includes('room_anyone_can_start') &&
                Object.keys(this.room['features']).includes('record') &&
                JSON.parse(this.room['features']['room_anyone_can_start']) == true &&
                JSON.parse(this.room['features']['record']) == true)
            {
                if ($('#privacy_settings_section').hasClass('collapsed')) {
                    $('#privacy_settings_section').removeClass('collapsed');
                }
                return true;
            }
            return false;
        },

        labelClickHandler(feature_name) {
            if (feature_name == 'record') {
                this.scrollToRoomStartWarning();
            }
        },

        scrollToRoomStartWarning() {
            setTimeout(() => {
                if (this.printRoomStartWarning()) {
                    // Make sure the Privacy fieldset is expanded.
                    if ($('#privacy_settings_section').hasClass('collapsed')) {
                        $('#privacy_settings_section').removeClass('collapsed');
                    }

                    var dialogComponent = this.$children.filter( (children) => {
                        return children.$options.name == 'Dialog'
                    });
                    if (dialogComponent.length) {
                        $(`#${dialogComponent[0].$data.id}`).animate(
                            {scrollTop: $(this.$refs["record"][0].$el).position().top},
                            'slow',
                            () => {
                                if (!$('#room_start_warning').is(':visible')) {
                                    $('#room_start_warning').show();
                                }
                            }
                        );
                    }
                }
            }, 100);
        },

        toggleInlineFeatureWarning(id) {
            if ($(`#${id}`) != undefined) {
                $(`#${id}`).toggle();
            }
        },
    }
}
</script>
