<template>
    <div>
        <studip-dialog
            :title="$gettext('Dokument hinzufügen')"
            :closeText="$gettext('Schließen')"
            :confirmText="$gettext('Hochladen')"
            closeClass="cancel"
            :confirmClass="confirm_button_class"
            class="meeting-dialog"
            height="380"
            width="500"
            @close="$emit('close')"
            @confirm="addNewDocument"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <div class="meeting-fileupload-info-panel">
                    <div v-if="has_upload_filesize">
                        <span>
                            {{ $gettext('Erlaubte Dateigröße:') }} <b>{{ course_config.upload_type.rel_file_size }}</b>
                        </span>
                        <template v-if="filesize !== null">
                            <StudipIcon v-if="is_filesize_valid" icon="accept" role="status-green" />
                            <StudipIcon v-else icon="decline" :title="$gettext('Dateigröße überschreitet!')" role="status-red" />
                        </template>
                    </div>
                    <div v-if="has_allowed_file_types || has_denied_file_types">
                        <span v-if="has_allowed_file_types">
                            {{ $gettext('Erlaubte Dateitypen:') }} <b>{{ course_config.upload_type.file_types.join(', ') }}</b>
                        </span>
                        <span v-else-if="has_denied_file_types">
                            {{ $gettext('Nicht erlaubte Dateitypen:') }} <b>{{ course_config.upload_type.file_types.join(', ') }}</b>
                        </span>
                        <template v-if="filetype !== null">
                            <StudipIcon v-if="is_filetype_valid" icon="accept" role="status-green" />
                            <StudipIcon v-else icon="decline" :title="$gettext('Ungültiger Dateityp!')" role="status-red" />
                        </template>
                    </div>
                </div>
                <div class="drag-and-drop-wrapper">
                    <label class="file drag-and-drop meeting-fileupload-dnd" @drop.prevent="getDroppedFile">
                        {{ $gettext('Neue Datei zum Hinzufügen per Drag & Drop in diesen Bereich ziehen.') }}
                        <input type="file" name="file" tabeindex="0" :title="tooltip_title_text" ref="meeting_upload_file" @change="getSelecedUploadFile">
                        <div v-if="filename">
                            <span>{{ filename }}</span>
                        </div>
                    </label>
                </div>
            </template>
        </studip-dialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import {translate} from 'vue-gettext';
const {gettext: $gettext, gettextInterpolate} = translate;

import {
    FOLDER_FILE_UPLOAD
} from "@/store/actions.type";

export default {
    name: "MeetingAddDocument",

    props: ['parent'],

    data() {
        return {
            modal_message: {},
            filename: null,
            filesize: null,
            filetype: null,
            disabledConfirmButton : true
        }
    },

    computed: {
        ...mapGetters(['course_config']),
        tooltip_title_text() {
            return this.filename ?
                gettextInterpolate($gettext('%{ filename } ist bereits ausgewählt.'),
                    {filename: this.filename})
                : this.$gettext('Neue Datei zum Hochladen auswählen.')
        },
        has_upload_filesize() {
            return this.course_config?.upload_type?.file_size > 0;
        },
        has_allowed_file_types() {
            return this.course_config?.upload_type?.type === 'deny' && this.course_config?.upload_type?.file_types?.length > 0;
        },
        has_denied_file_types() {
            return this.course_config?.upload_type?.type === 'allow' && this.course_config?.upload_type?.file_types?.length > 0;
        },
        is_filesize_valid() {
            if (this.filesize === null) {
                return true;
            }
            return this.has_upload_filesize && parseInt(this.filesize, 10) <= parseInt(this.course_config.upload_type.file_size, 10);
        },
        is_filetype_valid() {
            if (this.filetype === null) {
                return true;
            }
            if (this.has_allowed_file_types && !this.course_config.upload_type.file_types.includes(this.filetype)) {
                return false;
            }
            if (this.has_denied_file_types && this.course_config.upload_type.file_types.includes(this.filetype)) {
                return false;
            }
            return true;
        },
        confirm_button_class() {
            return this.disabledConfirmButton ? 'accept disabled' : 'accept';
        }
    },

    methods: {
        getDroppedFile(event) {
            this.modal_message = {};
            if (event?.dataTransfer?.files) {
                this.$refs.meeting_upload_file.files = event.dataTransfer.files;
                this.validateFileUpload();
            } else {
                this.modal_message = {
                    type: 'error',
                    text: $gettext('Beim Drag & Drop Fehler geschlagen!')
                }
            }
        },

        getSelecedUploadFile() {
            this.modal_message = {};
            if (this.$refs?.meeting_upload_file?.files) {
                this.validateFileUpload();
            } else {
                this.modal_message = {
                    type: 'error',
                    text: $gettext('Beim Auswahl der Datei Fehler geschlagen!')
                }
            }
        },

        validateFileUpload() {
            this.modal_message = {};
            let file = this.$refs.meeting_upload_file.files[0];
            if (!file) {
                this.modal_message = {
                    type: 'error',
                    text: $gettext('Datei konnte nicht gefunden werden.')
                }
                return;
            }
            this.filename = file.name;
            this.filesize = file.size;
            this.filetype = file.type;
            if (!this.is_filesize_valid || !this.is_filetype_valid) {
                this.disabledConfirmButton = true;
                return;
            }
            this.disabledConfirmButton = false;
        },

        addNewDocument() {
            if (!this.disabledConfirmButton) {
                let file = this.$refs.meeting_upload_file.files[0];
                if (!file) {
                    this.modal_message = {
                        type: 'error',
                        text: this.$gettext('Datei konnte nicht gefunden werden!')
                    }
                    return;
                }

                let formData = new FormData();
                formData.append('upload_file', file);
                formData.append('parent_id', this.parent.id);
                this.$store.dispatch(FOLDER_FILE_UPLOAD, formData)
                .then(({ data }) => {
                    let message = data.message;
                    if (message.type == 'error') {
                        this.modal_message = {
                            type: 'error',
                            text: message.text
                        }
                    } else {
                        this.$emit('done', message);
                    }
                }).catch (({error}) => {
                    this.modal_message = {
                        type: 'error',
                        text: error
                    }
                });
            }
        },
    }
}
</script>
