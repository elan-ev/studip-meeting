<template>
    <div>
        <MeetingDialog :parentId="parentDialogId" :title="$gettext('Neuer Ordner')" @close="closeDialog($event)">
            <template v-slot:content>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>

                <form class="default" @submit.prevent="addNewFolder">
                    <fieldset>
                        <legend v-translate>
                            Ordnereigenschaften
                        </legend>
                        <label>
                            <span class="required" v-translate>Name</span>
                            <input id="edit_folder_name" type="text" name="name" :placeholder="$gettext('Name')" v-model.trim="new_folder.name">
                        </label>
                        <label>
                            <translate>Beschreibung</translate>
                            <textarea name="description" :placeholder="$gettext('Optionale Beschreibung')" v-model.trim="new_folder.desc"></textarea>
                        </label>
                    </fieldset>
                    <fieldset v-if="Object.keys(folder).includes('folder_types') && Object.keys(folder['folder_types']).length > 0"
                        class="select_terms_of_use">
                        <legend v-translate>
                            Ordnertyp auswählen
                        </legend>
                        <template v-for="(folder_type, ik) in folder.folder_types">
                            <input :key="ik" type="radio" name="folder_type"
                                :value="folder_type.class" v-model="new_folder.type"
                                :id="'folder-type-' + folder_type.class">
                            <label :key="folder_type.class" :for="'folder-type-' + folder_type.class">
                                <div class="icon" v-if="folder_type.icon">
                                    <StudipIcon :icon="folder_type.icon"
                                        role="clickable" size="32"></StudipIcon>
                                </div>
                                <div class="text">
                                    {{ folder_type.name }}
                                </div>
                                <StudipIcon icon="check-circle" class="check"
                                        role="clickable" size="32"></StudipIcon>
                            </label>
                        </template>
                    </fieldset>
                </form>
            </template>
            <template v-slot:buttons>
                <StudipButton icon="accept" type="button"  v-on:click="addNewFolder($event)" v-translate>
                    Erstellen
                </StudipButton>
                <StudipButton icon="cancel" type="button"  v-on:click="closeDialog($event)" v-translate>
                    Schließen
                </StudipButton>
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
    FOLDER_CREATE, FOLDER_READ
} from "@/store/actions.type";

export default {
    name: "MeetingAddNewFolder",

    props: ['folder'],

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
            new_folder: {
                name: '',
                desc: '',
                type: 'StandardFolder'
            }
        }
    },

    computed: {
        ...mapGetters([

        ])
    },

    mounted() {

    },

    methods: {
        closeDialog(event) {
            if (event) {
                event.preventDefault();
            }
            this.new_folder = {
                name: '',
                desc: '',
                type: 'StandardFolder'
            };
            this.$emit('cancel');
        },

        addNewFolder(event) {
            if (event) {
                event.preventDefault();
            }
            if (this.new_folder.name == '' ||
                    (this.folder.folder_types && this.folder.folder_types.length > 0 && this.new_folder.type == '')) {
                var text = 'Der Name darf nicht leer sein.'.toLocaleString();
                if (this.new_folder.name != '') {
                    text = 'Bitte wählen Sie einen Ordner Typ aus'.toLocaleString();
                }
                this.$set(this.modal_message, "type" , "error");
                this.$set(this.modal_message, "text" , text);
                return;
            }

            this.$set(this.new_folder, "parent_id" , this.folder.id);

            this.$store.dispatch(FOLDER_CREATE, this.new_folder)
            .then(({ data }) => {
                this.message = data.message;
                if (this.message.type == 'error') {
                    this.$set(this.modal_message, "type" , "error");
                    this.$set(this.modal_message, "text" , this.message.text);
                } else {
                    store.dispatch(FOLDER_READ, this.folder.id);
                    this.$emit('done', { message: this.message });
                }
            }).catch (({error}) => {
                this.$emit('cancel');
            });
        }
    }
}
</script>
