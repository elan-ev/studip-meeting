<template>

    <div v-if="config" id="conference-meeting-create">
        <transition name="modal-fade">
            <div class="modal-backdrop">
                <div class="modal" role="dialog">

                    <header class="modal-header">
                        <slot name="header">
                            <translate>Raumkonfiguration</translate>
                            <span class="modal-close-button" @click="$emit('cancel')"></span>
                        </slot>
                    </header>

                    <section class="modal-body">
                        <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                            {{ modal_message.text }}
                        </MessageBox>
                        <MessageBox v-else-if="room['driver'] && !Object.keys(config[room['driver']]['servers']).length"
                             type="error" v-translate>
                            Es gibt keine Server für dieses Konferenzsystem, bitte wählen Sie ein anderes Konferenzsystem
                        </MessageBox>

                        <form class="default" @keyup="roomFormSubmit($event)" style="position: relative">
                            <fieldset>
                                <legend v-translate>
                                    Raumname
                                </legend>
                                <label>
                                    <input type="text" v-model.trim="room['name']" id="name">
                                </label>
                            </fieldset>

                            <fieldset v-if="(Object.keys(config).length > 1) || (room['driver']
                                        && Object.keys(config[room['driver']]['servers']).length > 1)">

                                <legend v-translate>
                                    Konferenz Systemeinstellung
                                </legend>

                                <label v-if="Object.keys(config).length > 1">
                                    <span class="required" v-translate>
                                        Konferenzsystem
                                    </span>

                                    <select id="driver" v-model="room['driver']" @change.prevent="handleServerDefaults" :disabled="Object.keys(config).length == 1">
                                        <option value="" disabled v-translate>
                                            Bitte wählen Sie ein Konferenzsystem aus
                                        </option>
                                        <option v-for="(driver_config, driver) in config" :key="driver"
                                                :value="driver">
                                                {{ driver_config['display_name'] }}
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
                                                :value="'' + server_index">
                                                <translate>Server {{ (server_index + 1) }}</translate>
                                                <span v-if="config[room['driver']]['server_defaults'] && config[room['driver']]['server_defaults'][server_index]
                                                            &&  config[room['driver']]['server_defaults'][server_index]['maxAllowedParticipants']"
                                                    v-translate="{
                                                        count: config[room['driver']]['server_defaults'][server_index]['maxAllowedParticipants']
                                                    }"
                                                >
                                                    (max. %{ count } Teilnehmer)
                                                </span>
                                        </option>
                                    </select>
                                </label>
                            </fieldset>

                            <fieldset>
                                <legend v-translate>
                                    Zusätzliche Funktionen
                                </legend>
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

                                <div v-if="room['driver'] && Object.keys(config[room['driver']]).includes('features')
                                        && Object.keys(config[room['driver']]['features']).includes('create') &&
                                        Object.keys(config[room['driver']]['features']['create']).length">
                                    <div v-for="(feature, index) in config[room['driver']]['features']['create']" :key="index">
                                        <label v-if="(feature['value'] === true || feature['value'] === false)">
                                            <input  type="checkbox"
                                                true-value="true"
                                                false-value="false"
                                                v-model="room['features'][feature['name']]">

                                                {{ feature['display_name'] }}
                                                <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"></StudipTooltipIcon>
                                        </label>

                                        <label v-else-if="feature['value'] && typeof feature['value'] === 'object'">
                                            {{ feature['display_name'] }}
                                            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"></StudipTooltipIcon>

                                            <select :id="feature['name']" v-model.trim="room['features'][feature['name']]">
                                                <option v-for="(fvalue, findex) in feature['value']" :key="findex"
                                                        :value="findex" v-translate>
                                                        {{ fvalue }}
                                                </option>
                                            </select>
                                        </label>
                                        <label v-else>

                                            {{ feature['display_name'] }}
                                            <span v-if="feature['name'] == 'maxParticipants'
                                                    && Object.keys(config[room['driver']]).includes('server_defaults')
                                                    && room['server_index']
                                                    && config[room['driver']]['server_defaults'][room['server_index']] != undefined
                                                    && Object.keys(config[room['driver']]['server_defaults'][room['server_index']]).includes('maxAllowedParticipants')"
                                                v-translate="{
                                                    count: config[room['driver']]['server_defaults'][room['server_index']]
                                                }"
                                            >
                                                &nbsp; (Max. Limit: %{ count })
                                            </span>
                                            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')"
                                                :text="feature['info']">
                                            </StudipTooltipIcon>

                                            <input :type="(feature['name'] == 'duration' || feature['name'] == 'maxParticipants') ? 'number' : 'text'"
                                                :max="(
                                                    (feature['name'] == 'maxParticipants') ?
                                                    (Object.keys(config[room['driver']]).includes('server_defaults')
                                                        && room['server_index']
                                                        && config[room['driver']]['server_defaults'][room['server_index']] != undefined
                                                        && Object.keys(config[room['driver']]['server_defaults'][room['server_index']]).includes('maxAllowedParticipants')) ?
                                                            config[room['driver']]['server_defaults'][room['server_index']]['maxAllowedParticipants']
                                                        : ''
                                                    : ''
                                                )"
                                                :min="(feature['name'] == 'maxParticipants') ? 20 : ''"
                                                @change="(feature['name'] == 'maxParticipants') ? checkPresets() : ''"
                                                v-model.trim="room['features'][feature['name']]"
                                                :placeholder="feature['value'] ? feature['value'] : ''"
                                                :id="feature['name']">

                                        </label>
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset v-if="room['driver'] && Object.keys(config[room['driver']]).includes('features')
                                        && Object.keys(config[room['driver']]['features']).includes('record')
                                        && Object.keys(config[room['driver']]['features']['record']).length
                                        && Object.keys(config[room['driver']]).includes('record')">
                                <legend v-translate>
                                    Aufzeichnung
                                </legend>

                                <div v-for="(feature, index) in config[room['driver']]['features']['record']" :key="index">
                                    <label v-if="(feature['value'] === true || feature['value'] === false)">
                                        <input  type="checkbox"
                                            true-value="true"
                                            false-value="false"
                                            v-model="room['features'][feature['name']]">

                                            {{ feature['display_name'] }}
                                            <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"
                                                :badge="(Object.keys(config[room['driver']]).includes('opencast') && config[room['driver']]['opencast'] == '1'
                                                    && feature['info'].toLowerCase().includes('opencast')) ? true : false" v-translate>
                                                    beta
                                            </StudipTooltipIcon>
                                    </label>

                                    <label v-else-if="feature['value'] && typeof feature['value'] === 'object'">
                                        {{ feature['display_name'] }}
                                        <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"></StudipTooltipIcon>

                                        <select :id="feature['name']" v-model.trim="room['features'][feature['name']]">
                                            <option v-for="(fvalue, findex) in feature['value']" :key="findex"
                                                    :value="findex">
                                                    {{ fvalue }}
                                            </option>
                                        </select>
                                    </label>
                                    <label v-else>
                                        {{ feature['display_name'] }}
                                        <StudipTooltipIcon v-if="Object.keys(feature).includes('info')" :text="feature['info']"></StudipTooltipIcon>

                                        <input type="text" v-model.trim="room['features'][feature['name']]" :placeholder="feature['value'] ? feature['value'] : ''" :id="feature['name']">
                                    </label>
                                </div>
                            </fieldset>

                            <fieldset v-if="(Object.keys(course_groups).length > 1)">
                                <legend v-translate>
                                    Gruppenraum
                                </legend>

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

                            <fieldset v-if="room['driver'] && Object.keys(config[room['driver']]).includes('features')
                                        && Object.keys(config[room['driver']]['features']).includes('folders')
                                        && config[room['driver']]['features']['folders'] == true">
                                <legend v-translate>Raum Dateiordner</legend>
                                <label>
                                    <translate>Verknüpfen Sie einen Ordner mit diesem Raum</translate>
                                    <div>
                                        <translate>Aktuell ausgewählter Ordner</translate>:
                                        <span v-if="room.folder_id && room.folder_id == folder.id && folder.name != ''">
                                            {{folder.name}}
                                        </span>
                                        <span v-else v-translate>
                                            Kein Ordner
                                        </span>
                                    </div>
                                    <div class="course-folder-container">
                                        <table class="default documents">
                                             <caption>
                                                <div class="caption-container meetings-caption">
                                                    <a :title="$gettext('Zum Hauptordner')"
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
                                            <template v-if="(Object.keys(folder).includes('subfolders') && Object.keys(folder['subfolders']).length > 0) ||
                                                            (Object.keys(folder).includes('files') && Object.keys(folder['files']).length > 0 && showFilesInFolder)">
                                                <tbody class="subfolders" v-if="Object.keys(folder['subfolders']).length > 0">
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
                                                </tbody>
                                                <tbody class="files" v-if="Object.keys(folder['files']).length > 0 && showFilesInFolder">
                                                    <tr v-for="(finfo, fid) in folder.files" :key="fid">
                                                        <td>
                                                            <div>
                                                                <StudipIcon v-if="finfo.icon" :icon="finfo.icon"
                                                                    role="clickable" size="16"></StudipIcon>
                                                                <span>{{finfo.name}}</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </template>
                                            <template v-else>
                                                <tbody>
                                                    <tr class="empty">
                                                        <td>
                                                            <translate>Dieser Ordner ist leer</translate>
                                                            <span v-translate v-if="Object.keys(folder).includes('files') && Object.keys(folder['files']).length > 0">(Dateien verfügbar)</span>
                                                        </td>
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
                                                            <a @click.prevent="showFilesInFolder = !showFilesInFolder" class="right">
                                                                <StudipIcon :icon="(showFilesInFolder) ? 'checkbox-checked' : 'checkbox-unchecked'"
                                                                    role="clickable" size="14"></StudipIcon>
                                                                <span v-translate>Dateien anzeigen</span>
                                                            </a>
                                                        </div>
                                                    </td>
                                                 </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </label>
                               
                            </fieldset>

                            <footer class="modal-footer">
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
                            </footer>
                        </form>
                    </section>
                </div>
            </div>
        </transition>

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

import {
    ROOM_LIST, ROOM_UPDATE, ROOM_CREATE, FOLDER_READ
} from "@/store/actions.type";

import {
    ROOM_CLEAR
} from "@/store/mutations.type";

export default {
    name: "MeetingAdd",

    props: ['room'],

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
        MeetingAddNewFolder
    },

    data() {
        return {
            modal_message: {},
            message: '',
            showAddNewFolder: false,
            showFilesInFolder: false
        }
    },

    computed: {
        ...mapGetters([
            'config',
            'course_config', 'course_groups', 'folder'
        ])
    },

    mounted() {
        this.modal_message = {};
        this.setDriver();
        this.getFolders();
    },

    methods: {
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

            //set default features
            this.$set(this.room, "features" , {});

            if (Object.keys(this.config[this.room['driver']]).includes('features')) {
                //set default value of features
                if (Object.keys(this.config[this.room['driver']]['features']).includes('create') &&
                    Object.keys(this.config[this.room['driver']]['features']['create']).length) {
                    //applying first level of defaults for create features - important
                    this.config[this.room['driver']]['features']['create'].forEach(feature => { //apply all values for room feature!
                        this.$set(this.room['features'], feature.name , feature.value);
                    });
                    // set all selects to first entry
                    for (let index in this.config[this.room['driver']]['features']['create']) {
                        let feature = this.config[this.room['driver']]['features']['create'][index];

                        if (typeof feature.value === 'object' && !Array.isArray(feature.value)) {
                            this.room['features'][feature['name']] = Object.keys(feature['value'])[0];
                        }
                    }

                    //Applying Second level of defaults from server defaults - if there is any but highly important!
                    if (this.room['server_index'] && Object.keys(this.config[this.room['driver']]).includes('server_defaults') &&
                        Object.keys(this.config[this.room['driver']]['server_defaults']).length &&
                        Object.keys(this.config[this.room['driver']]['server_defaults']).includes(this.room['server_index'])) {
                        for (const [feature_name, feature_value] of Object.entries(this.config[this.room['driver']]['server_defaults'][this.room['server_index']])) {
                            if (feature_name != 'maxAllowedParticipants') {
                                this.$set(this.room['features'], ((feature_name == 'totalMembers') ? 'maxParticipants' : feature_name ), feature_value);
                            }
                        }
                    }
                }
                if (Object.keys(this.config[this.room['driver']]['features']).includes('record') &&
                    Object.keys(this.config[this.room['driver']]['features']['record']).length) {
                    this.config[this.room['driver']]['features']['record'].forEach(feature => { //apply all values for room feature!
                        this.$set(this.room['features'], feature.name , feature.value);
                    });
                    // set all selects to first entry
                    for (let index in this.config[this.room['driver']]['features']['record']) {
                        let feature = this.config[this.room['driver']]['features']['record'][index];

                        if (typeof feature.value === 'object' && !Array.isArray(feature.value)) {
                            this.room['features'][feature['name']] = Object.keys(feature['value'])[0];
                        }
                    }
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

        validateMaxParticipants() {
            var isValid = true;
            if (this.room['driver'] && this.room['server_index'] && this.room['features'] && this.room['features']['maxParticipants']
             && Object.keys(this.config[this.room['driver']]).includes('server_defaults')
             && Object.keys(this.config[this.room['driver']]['server_defaults'][this.room['server_index']]).includes('maxAllowedParticipants')
             && parseInt(this.room['features']['maxParticipants']) > parseInt(this.config[this.room['driver']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants'])) {

                this.$set(this.room['features'], 'maxParticipants', this.config[this.room['driver']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants']);
                var maxAllowedParticipants = this.config[this.room['driver']]['server_defaults'][this.room['server_index']]['maxAllowedParticipants'];
                this.modal_message.type = 'error';
                this.modal_message.text = `Teilnehmerzahl darf ${maxAllowedParticipants} nicht überschreiten`.toLocaleString();
                $('section.modal-body').animate({ scrollTop: 0}, 'slow');
                isValid = false;

            }
            return isValid;
        },

        addRoom(event) {
            if (event) {
                event.preventDefault();
            }

            if (!this.validateMaxParticipants()) {
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
                        $('section.modal-body').animate({ scrollTop: 0}, 'slow');
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
                $('section.modal-body').animate({ scrollTop: 0}, 'slow');
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

            if (!this.validateMaxParticipants()) {
                return;
            }

            this.$store.dispatch(ROOM_UPDATE, this.room)
            .then(({ data }) => {
                this.message = data.message;
                if (data.message.type == 'success') {
                    this.$emit('done', { message: this.message });
                } else {
                    $('section.modal-body').animate({ scrollTop: 0}, 'slow');
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
    }
}
</script>
