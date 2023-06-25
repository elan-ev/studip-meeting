<template>
    <fieldset class="collapsable">
        <legend
            tabindex="0"
            role="button"
            :aria-label="$gettext('Schriftarten')"
            aria-expanded="true"
            v-on="fieldsetHandlers"
        >
            {{ $gettext('Schriftarten') }}
        </legend>
        <table v-if="font" class="default collapsable meetings-default-slides-settings">
            <thead>
                <tr>
                    <th scope="col">{{ $gettext('Typ') }}</th>
                    <th scope="col">{{ $gettext('Name') }}</th>
                    <th scope="col">{{ $gettext('Installiert') }}</th>
                    <th scope="col">{{ $gettext('Aktionen') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(font_item, index) in font" :key="index">
                    <td>
                        <span :class="{required: font_item.type == 'regular'}">
                            {{ displayFontType(font_item.type) }}
                        </span>
                    </td>
                    <td>{{ font_item.name ? font_item.name : $gettext('Keine Schriftart') }}</td>
                    <td>
                        <StudipIcon v-if="font_item.name" icon="accept" role="status-green" />
                        <StudipIcon v-else icon="decline" role="status-red" />
                    </td>
                    <td class="actions">
                        <a class="upload">
                            <input type="file" tabindex="0" :title="$gettext('Schriftart hochladen')" ref="font" accept=".ttf" v-on:change="handleFileUpload('font', index)"/>
                            <StudipIcon icon="upload" role="clickable" />
                        </a>
                        <a href="#" @click.prevent="deleteFont(font_item.type)" :title="$gettext('Schriftart löschen')">
                            <StudipIcon icon="trash" role="clickable" />
                        </a>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="legend">
                        <p>
                            <small>
                                <b>*.ttf</b> {{ $gettext('ist erlaubt!') }}
                            </small>
                        </p>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Dialogs -->
        <studip-dialog
            v-if="showDeleteConfirmDialog"
            :title="$gettext('Schriftart löschen')"
            :question="$gettext('Sind Sie sicher, dass Sie diesen Schriftart löschen möchten?')"
            :confirmText="$gettext('Ja')"
            :closeText="$gettext('Nein')"
            closeClass="cancel"
            height="180"
            @confirm="performDeleteFont"
            @close="resetDeleteConfirmDialog"
        />
    </fieldset>
</template>

<script>
import { mapGetters } from "vuex";
import { a11y } from '@/common/a11y.mixins'

import {
    DEFAULT_SLIDE_FONT_READ,
    DEFAULT_SLIDE_FONT_UPLOAD,
    DEFAULT_SLIDE_FONT_DELETE,
    MESSAGE_ADD,
    MESSAGES_CLEAR
} from "@/store/actions.type";

export default {
    name: 'meeting-fonts',
    mixins: [a11y],
    data() {
        return {
            showDeleteConfirmDialog: false,
            message: {}
        }
    },
    computed: {
        ...mapGetters(['font'])
    },
    methods: {
        getInstalledFont() {
            this.$store.dispatch(DEFAULT_SLIDE_FONT_READ);
        },

        displayFontType(font_type) {
            switch (font_type) {
                case 'regular':
                    return this.$gettext('Regulär');
                case 'bold':
                    return this.$gettext('Fett gedruckt');
                case 'italic':
                    return this.$gettext('Kursiv');
                case 'bold_italic':
                    return this.$gettext('Fett Kursiv');
            }
        },

        deleteFont(type) {
            var filtered_font = this.font.filter(f => f.type == type);
            var targeted_font = filtered_font.length ? filtered_font[0] : null;
            if (!this.font || targeted_font == null || !targeted_font.name) {
                return;
            }
            this.resetDeleteConfirmDialog();
            this.showDeleteConfirmDialog = {
                callback_data: type
            }
        },

        performDeleteFont(){
            let type = this.showDeleteConfirmDialog.callback_data;
            this.$store.dispatch(DEFAULT_SLIDE_FONT_DELETE, type)
            .then(({data}) => {
                this.message = data.message;
                if (data.message.type == 'success') {
                    this.getInstalledFont();
                }
            });
            this.resetDeleteConfirmDialog();
        },

        resetDeleteConfirmDialog() {
            this.showDeleteConfirmDialog = false;
        },

        handleFileUpload(what, ref_index = 0) {
            let file = null;
            if (Array.isArray(this.$refs[what])) {
                file = this.$refs[what][ref_index].files[0];
            } else {
                file = this.$refs[what].files[0];
            }

            let formData = new FormData();
            formData.append(what, file);
            let uploadTo = DEFAULT_SLIDE_FONT_UPLOAD;
            let readFrom = DEFAULT_SLIDE_FONT_READ;
            formData.append('type', this.font[ref_index].type);
            this.$store.dispatch(uploadTo, formData)
            .then(({ data }) => {
                this.message = data.message;
                if (data.message.type == 'success') {
                    this.$store.dispatch(readFrom);
                }
            }).catch (({error}) => {
                console.log(error);
            });
        },
    },
    watch: {
        message(value) {
            if (value?.text) {
                this.$store.dispatch(MESSAGES_CLEAR);
                this.$store.dispatch(MESSAGE_ADD, value);
            }
        }
    },
    mounted () {
        this.getInstalledFont();
    },
}
</script>
