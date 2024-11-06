<template>
    <div>
        <studip-dialog
            :title="$gettext('Neuer Ordner')"
            :confirmText="$gettext('Erstellen')"
            :closeText="$gettext('Schließen')"
            confirmClass="accept"
            closeClass="cancel"
            class="meeting-dialog"
            height="550"
            width="450"
            @confirm="addNewFolder"
            @close="$emit('close')"
        >
            <template v-slot:dialogContent>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <form class="default" @submit.prevent="addNewFolder">
                    <fieldset>
                        <legend>
                            {{ $gettext('Ordnereigenschaften') }}
                        </legend>
                        <label>
                            <span class="required">{{ $gettext('Name') }}</span>
                            <input id="edit_folder_name" type="text" name="name" :placeholder="$gettext('Name')" v-model.trim="new_folder.name">
                        </label>
                        <label>
                            {{ $gettext('Beschreibung') }}
                            <textarea name="description" :placeholder="$gettext('Optionale Beschreibung')" v-model.trim="new_folder.desc"></textarea>
                        </label>
                    </fieldset>
                    <fieldset v-if="Object.keys(parent).includes('folder_types') && Object.keys(parent['folder_types']).length > 0"
                        class="select_terms_of_use">
                        <legend>
                            {{ $gettext('Ordnertyp auswählen') }}
                        </legend>
                        <template v-for="(folder_type, ik) in parent.folder_types">
                            <input :key="ik" type="radio" name="folder_type"
                                :value="folder_type.class" v-model="new_folder.type"
                                :id="'folder-type-' + folder_type.class">
                            <label :key="folder_type.class" :for="'folder-type-' + folder_type.class">
                                <div class="icon" v-if="folder_type.icon">
                                    <StudipIcon :shape="folder_type.icon"
                                        role="clickable" size="32"></StudipIcon>
                                </div>
                                <div class="text">
                                    {{ folder_type.name }}
                                </div>
                                <StudipIcon shape="check-circle" class="check"
                                        role="clickable" size="32"></StudipIcon>
                            </label>
                        </template>
                    </fieldset>
                </form>
            </template>
        </studip-dialog>
    </div>
</template>

<script>

import {
    FOLDER_CREATE
} from "@/store/actions.type";

export default {
    name: "MeetingAddNewFolder",

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

    props: ['parent'],

    methods: {
        addNewFolder() {
            if (this.new_folder.name == '' ||
                    (this.parent.folder_types && this.parent.folder_types.length > 0 && this.new_folder.type == '')) {
                var text = this.$gettext('Der Name darf nicht leer sein.');
                if (this.new_folder.name != '') {
                    text = this.$gettext('Bitte wählen Sie einen Ordner Typ aus');
                }
                this.$set(this.modal_message, "type" , "error");
                this.$set(this.modal_message, "text" , text);
                return;
            }

            this.$set(this.new_folder, "parent_id" , this.parent.id);

            this.$store.dispatch(FOLDER_CREATE, this.new_folder)
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
        },
    },
}
</script>
