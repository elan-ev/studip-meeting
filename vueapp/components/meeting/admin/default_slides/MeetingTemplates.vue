<template>
    <div>
        <fieldset v-for="(template, page) in templates" ref="template" :key="parseInt(page, 10)" class="collapsable"
            :class="{collapsed: !isLastTemplate(page)}">
            <legend
                tabindex="0"
                role="button"
                :aria-label="$gettext('Vorlage') + ` ${ page }`"
                :aria-expanded="isLastTemplate(page)"
                v-on="fieldsetHandlers"
                v-translate="{
                    page: page
                }">
                %{ page }. Vorlage
            </legend>
            <table class="default collapsable meetings-default-slides-settings">
                <thead>
                    <tr>
                        <th scope="col" v-translate>Typ</th>
                        <th scope="col" v-translate>Name</th>
                        <th scope="col" v-translate>Installiert</th>
                        <th scope="col" v-translate>Extension</th>
                        <th scope="col" v-translate>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="required" v-translate>PDF Folie</span></td>
                        <td>{{ displayTemplateName(template, 'pdf') }}</td>
                        <td>
                            <StudipIcon v-if="template.pdf && template.pdf.filename" icon="accept" role="status-green" />
                            <StudipIcon v-else icon="decline" role="status-red" />
                        </td>
                        <td><span v-translate><b>*.pdf</b> ist erlaubt!</span></td>
                        <td class="actions">
                            <a class="upload">
                                <input type="file" tabindex="0" :title="$gettext('Folie hochladen')" ref="pdf" :name="'pdf_' + page"
                                    accept=".pdf" v-on:change="handleFileUpload('pdf', parseInt(page, 10))">
                                <StudipIcon icon="upload" role="clickable" />
                            </a>
                            <a href="#" v-if="template.pdf && template.pdf.preview" :title="$gettext('Vorschau')"
                                @click.prevent="showPreview(template.pdf.preview)">
                                <StudipIcon icon="file-pdf" role="clickable"></StudipIcon>
                            </a>
                            <a href="#" @click.prevent="deleteTemplate('pdf', page)" :title="$gettext('Vorlage löschen')">
                                <StudipIcon icon="trash" role="clickable"></StudipIcon>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td><span v-translate>PHP (HTML) Template</span></td>
                        <td>{{ displayTemplateName(template, 'php') }}</td>
                        <td>
                            <StudipIcon v-if="template.php && template.php.filename" icon="accept" role="status-green" />
                            <StudipIcon v-else icon="decline" role="status-red" />
                        </td>
                        <td><span v-translate><b>*.php</b> ist erlaubt!</span></td>
                        <td class="actions">
                            <a class="upload">
                                <input type="file" tabindex="0" :title="$gettext('PHP Template hochladen')" ref="php" :name="'php_' + page"
                                    accept=".php" v-on:change="handleFileUpload('php', parseInt(page, 10))">
                                <StudipIcon icon="upload" role="clickable" />
                            </a>
                            <a href="#" @click.prevent="deleteTemplate('php', page)" :title="$gettext('PHP Template löschen')">
                                <StudipIcon icon="trash"  role="clickable"></StudipIcon>
                            </a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </fieldset>

        <!-- Dialogs -->
        <studip-dialog
            v-if="showDeleteConfirmDialog"
            :title="$gettext('Vorlage löschen')"
            :question="showDeleteConfirmDialog.question"
            :confirmText="$gettext('Ja')"
            :closeText="$gettext('Nein')"
            closeClass="cancel"
            height="180"
            @confirm="performDeleteTemplate"
            @close="resetTemplateDeleteConfirmDialog"
        >
        </studip-dialog>

        <DefaultSlidePreviewDialog v-if="showPreviewDialog"
            :preview="showPreviewDialog"
            @cancel="showPreviewDialog = false"
        />
    </div>
</template>

<script>
import {translate} from 'vue-gettext';
const {gettext: $gettext, gettextInterpolate} = translate;
import { a11y } from '@/common/a11y.mixins'

import {
    DEFAULT_SLIDE_TEMPLATE_READ,
    DEFAULT_SLIDE_TEMPLATE_DELETE,
    DEFAULT_SLIDE_TEMPLATE_UPLOAD,
    MESSAGE_ADD,
    MESSAGES_CLEAR
} from "@/store/actions.type"

import DefaultSlidePreviewDialog from "@meeting/admin/default_slides/DefaultSlidePreviewDialog";
export default {
    name: 'meeting-templates',
    mixins: [a11y],
    components: {
        DefaultSlidePreviewDialog,
    },
    props: ['templates'],
    data() {
        return {
            showPreviewDialog: false,
            showDeleteConfirmDialog: false,
            message: {}
        }
    },
    methods: {
        isLastTemplate(page) {
            return Object.keys(this.templates).length == parseInt(page, 10);
        },

        displayTemplateName(template, where) {
            var sub = (where == 'pdf') ? 'Folie' : 'Template';
            var name = gettextInterpolate($gettext('Keine %{ sub }'), {sub: sub});
            if (template && template[where] && template[where].filename) {
                name = template[where].filename;
            }
            return name;
        },

        handleFileUpload(what, page) {
            page = parseInt(page, 10);
            let ref_index = page - 1;
            // Prevent php upload without having the pdf!
            if (what == 'php' && this.templates?.[page]?.pdf && Object.keys(this.templates[page].pdf).length == 0) {
                this.message = {};
                this.$set(this.message, 'type', 'error');
                this.$set(this.message, 'text',
                    gettextInterpolate($gettext('Bitte laden Sie zuerst eine PDF-Datei für die %{ ref }. Vorlage hoch.'),
                        {ref: ref_index + 1})
                );
                return;
            }
            let file = null;
            if (Array.isArray(this.$refs[what])) {
                file = this.$refs[what][ref_index].files[0];
            } else {
                file = this.$refs[what].files[0];
            }

            let formData = new FormData();
            formData.append(what, file);
            let uploadTo = DEFAULT_SLIDE_TEMPLATE_UPLOAD;
            let readFrom = DEFAULT_SLIDE_TEMPLATE_READ;
            formData.append('page', (ref_index + 1));
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

        showPreview(preview_url) {
            this.showPreviewDialog = preview_url;
        },

        deleteTemplate(what, page) {
            page = parseInt(page, 10);
            if (this.templates && this.templates[page] && this.templates[page][what]
                && Object.keys(this.templates[page][what]).length == 0) {
                // Remove template if pdf delete is asked!
                if (what == 'pdf') {
                    this.$delete(this.templates, page);
                    this.$set(this.message, 'type', 'success');
                    this.$set(this.message, 'text', this.$gettext('Folie/Template wurde erfolgreich gelöscht'));
                }
                return;
            } else {
                var text = this.$gettext('Es wird die gesamte Vorlage löschen! Sind Sie sicher, dass Sie diese Vorlage löschen möchten?');
                if (what == 'php') {
                    text = this.$gettext('Sind Sie sicher, dass Sie diese Template löschen möchten?');
                }
                this.resetTemplateDeleteConfirmDialog();
                this.showDeleteConfirmDialog = {
                    question: text,
                    callback_data: {page, what}
                }
            }
        },

        performDeleteTemplate(){
            let data = this.showDeleteConfirmDialog.callback_data;
            this.$store.dispatch(DEFAULT_SLIDE_TEMPLATE_DELETE, data)
            .then(({data}) => {
                this.message = data.message;
                if (data.message.type == 'success') {
                    this.$store.dispatch(DEFAULT_SLIDE_TEMPLATE_READ);
                }
            });
            this.resetTemplateDeleteConfirmDialog();
        },

        resetTemplateDeleteConfirmDialog() {
            this.showDeleteConfirmDialog = false;
        },
    },
    watch: {
        message(value) {
            if (value?.text) {
                this.$store.dispatch(MESSAGES_CLEAR);
                this.$store.dispatch(MESSAGE_ADD, value);
            }
        }
    }
}
</script>
