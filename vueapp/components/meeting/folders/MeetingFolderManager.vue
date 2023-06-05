<template>
    <div>
        <studip-dialog
            :title="$gettext('Ordnerverwaltung')"
            :closeText="$gettext('Schließen')"
            closeClass="cancel"
            class="meeting-dialog"
            :height="dialog_height"
            :width="dialog_width"
            @close="$emit('cancel')"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>

                <form class="default" @submit.prevent="">
                    <div>
                        {{ $gettext('Aktuell ausgewählter Ordner: ') }}

                        <span>
                            {{ folder.name }}
                            <span v-if="folder.is_top_folder">
                                {{ '(' + $gettext('Hauptordner') + ')' }}
                            </span>
                        </span>
                    </div>
                    <MeetingFolderTable
                        :folder="folder"
                        :renderLarge="true"
                        @switchFolder="folderHandler"
                        :numFileInFolderLimit="numFileInFolderLimit">
                        <template v-slot:footerButtons>
                            <StudipButton
                                @click="showAddNewDialog = true">
                                {{ $gettext('Neuer Ordner') }}
                            </StudipButton>
                            <StudipButton
                                @click="showUploadDocumentDialog = true">
                                {{ $gettext('Dokument hinzufügen') }}
                            </StudipButton>
                        </template>
                    </MeetingFolderTable>
                </form>
            </template>
        </studip-dialog>

        <!-- Dialogs -->
        <MeetingAddNewFolder v-if="showAddNewDialog"
            :parent="folder"
            @done="performAfterAddNewFolder"
            @close="showAddNewDialog = false"
        />

        <MeetingAddDocument v-if="showUploadDocumentDialog"
            :parent="folder"
            @done="performAfterAddNewDockument"
            @close="showUploadDocumentDialog = false"/>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import {translate} from 'vue-gettext';
const {gettext: $gettext, gettextInterpolate} = translate;

import MeetingFolderTable from "@meeting/folders/MeetingFolderTable";
import MeetingAddNewFolder from "@meeting/folders/MeetingAddNewFolder";
import MeetingAddDocument from "@meeting/folders/MeetingAddDocument";

import {
    FOLDER_READ
} from "@/store/actions.type";

export default {
    name: "MeetingFolderManager",

    components: {
        MeetingFolderTable,
        MeetingAddNewFolder,
        MeetingAddDocument
    },

    data() {
        return {
            modal_message: {},
            showUploadDocumentDialog: false,
            showAddNewDialog: false,
            numFileInFolderLimit: 10
        }
    },

    computed: {
        ...mapGetters(['folder']),
        dialog_height() {
            let optimalHeight = window.innerHeight * 0.5;
            return optimalHeight.toString();
        },
        dialog_width() {
            let optimalWidth = window.innerWidth * 0.6;
            return optimalWidth.toString();
        },
        new_folder_title() {
            return gettextInterpolate($gettext('Neuer Ordner unter (%{ current }) erstellen.'),{current: this.folder.name});
        },
        add_document_title() {
            return gettextInterpolate($gettext('Dokumente in (%{ current }) hinzufügen.'),{current: this.folder.name});
        }
    },

    mounted() {
        this.getFolders('topFolder');
    },

    methods: {
        getFolders(folder_id = 'topFolder') {
            this.$store.dispatch(FOLDER_READ, folder_id);
        },

        folderHandler(to) {
            this.getFolders(to);
        },

        performAfterAddNewFolder(message) {
            this.showAddNewDialog = false;
            this.modal_message = message;
            this.getFolders(this.folder.id);
        },

        performAfterAddNewDockument(message) {
            this.showUploadDocumentDialog = false;
            this.modal_message = message;
            this.getFolders(this.folder.id);
        },
    }
}
</script>
