<template>
    <div>
        <MeetingDialog :title="$gettext('Standard-Folienmanager')" @close="close">
            <template v-slot:content>
                <MessageBox v-if="modal_message.text" :type="modal_message.type" @hide="modal_message.text = ''">
                    {{ modal_message.text }}
                </MessageBox>
                <form class="default" style="position: relative">
                    <fieldset v-if="font" class="collapsable">
                        <legend v-translate>
                            Font
                        </legend>
                        <label v-for="(font_item, index) in font" :key="index" class="with-append">
                            <span :class="{required: font_item.type == 'regular'}" v-translate>
                                {{ displayFontType(font_item.type) }}
                            </span>
                            <div class="input-group files-search">
                                <input type="file" ref="font" accept=".ttf" v-on:change="handleFileUpload('font', index)">
                                <input type="text" readonly :value="(font_item.name ? font_item.name : $gettext('Keine Schriftart'))">
                                <span class="input-group-append">
                                    <a class="button" :title="$gettext('Schriftart hochladen')">
                                        <StudipIcon icon="upload" size="17" role="clickable"></StudipIcon>
                                    </a>
                                    <a class="button" @click.prevent="deleteFont(font_item.type)" :title="$gettext('Schriftart löschen')">
                                        <StudipIcon icon="trash" size="17" role="clickable"></StudipIcon>
                                    </a>
                                </span>
                            </div>
                             <p>
                                <small v-translate>
                                    <b>*.ttf</b> ist erlaubt!
                                </small>
                            </p>
                        </label>
                    </fieldset>
                    <fieldset style="display: none;" v-if="Object.keys(templates).length == 0"></fieldset>
                    <template v-for="(template, page) in templates">
                        <fieldset ref="template" v-if="template != undefined" :key="page" class="collapsable"
                            :class="{collapsed: Object.keys(templates).length != parseInt(page)}">
                            <legend v-translate="{
                                page: page
                            }">
                                %{ page }. Vorlage
                            </legend>
                            <label class="with-append">
                                <span class="required" v-translate>PDF Folie</span>
                                <div class="input-group files-search">
                                    <input type="file" ref="pdf" :name="'pdf_' + page" accept=".pdf" v-on:change="handleFileUpload('pdf', parseInt(page) - 1)">
                                    <input type="text" readonly :value="displayTemplateName(template, 'pdf')">
                                    <span class="input-group-append">
                                        <a class="button" :title="$gettext('Folie hochladen')">
                                            <StudipIcon icon="upload" size="17" role="clickable"></StudipIcon>
                                        </a>
                                        <a class="button" v-if="template.pdf && template.pdf.preview" :title="$gettext('Vorschau')" @click.prevent="showPreview(template.pdf.preview)">
                                            <StudipIcon icon="file-pdf" size="17" role="clickable"></StudipIcon>
                                        </a>
                                        <a class="button" @click.prevent="deleteTemplate('pdf', page)" :title="$gettext('Vorlage löschen')">
                                            <StudipIcon icon="trash" size="17" role="clickable"></StudipIcon>
                                        </a>
                                    </span>
                                </div>
                                <p>
                                    <small v-translate>
                                        <b>*.pdf</b> ist erlaubt!
                                    </small>
                                </p>
                            </label>
                            <label class="with-append">
                                <span v-translate>PHP (HTML) Template</span>
                                <div class="input-group files-search">
                                    <input type="file" ref="php" :name="'php_' + page" accept=".php" v-on:change="handleFileUpload('php', parseInt(page) - 1)">
                                    <input type="text" readonly :value="displayTemplateName(template, 'php')">
                                    <span class="input-group-append">
                                        <a class="button" :title="$gettext('PHP Template hochladen')">
                                            <StudipIcon icon="upload" size="17" role="clickable"></StudipIcon>
                                        </a>
                                        <a class="button" :title="$gettext('Mustervorlage herunterladen')" @click.prevent="downloadSample('php')">
                                            <StudipIcon icon="download" size="17" role="clickable"></StudipIcon>
                                        </a>
                                        <a class="button" @click.prevent="deleteTemplate('php', page)" :title="$gettext('PHP Template löschen')">
                                            <StudipIcon icon="decline" size="17" role="clickable"></StudipIcon>
                                        </a>
                                    </span>
                                </div>
                                <p>
                                    <small v-translate>
                                        <b>*.php</b> ist erlaubt!
                                    </small>
                                </p>
                            </label>
                        </fieldset>
                    </template>
                </form>
                <StudipButton
                    icon="add"
                    @click="addEmptyTemplateItem($event)">
                    <translate>Vorlage hinzufügen</translate>
                </StudipButton>
            </template>
            <template v-slot:buttons>
                <StudipButton icon="accept" type="button"  v-on:click="close" v-translate>
                    Übernehmen
                </StudipButton>
                <StudipButton icon="cancel" type="button"
                    v-on:click="close"
                    class="ui-button ui-corner-all ui-widget"
                    v-translate
                >
                    Abbrechen
                </StudipButton>
            </template>
        </MeetingDialog>

        <!-- dialogs -->
        <MeetingMessageDialog v-if="showConfirmDialog"
            :message="showConfirmDialog"
            @accept="performConfirm"
            @cancel="showConfirmDialog = false"
        />

        <DefaultSlidePreviewDialog v-if="showPreviewDialog"
            :preview="showPreviewDialog"
            @cancel="showPreviewDialog = false"
        />
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import {
    DEFAULT_SLIDE_FONT_READ,
    DEFAULT_SLIDE_FONT_UPLOAD,
    DEFAULT_SLIDE_FONT_DELETE,
    DEFAULT_SLIDE_TEMPLATE_READ,
    DEFAULT_SLIDE_TEMPLATE_DELETE,
    DEFAULT_SLIDE_TEMPLATE_UPLOAD,
    DEFAULT_SLIDE_SAMPLE_TEMPLATE_DOWNLOAD
} from "@/store/actions.type";


import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import StudipTooltipIcon from "@/components/StudipTooltipIcon";
import MessageBox from "@/components/MessageBox";
import MeetingMessageDialog from "@/components/MeetingMessageDialog";
import DefaultSlidePreviewDialog from "@/components/DefaultSlidePreviewDialog";

import { dialog } from '@/common/dialog.mixins'
import {translate} from 'vue-gettext';
const {gettext: $gettext, gettextInterpolate} = translate;

export default {
    name: "DefaultSlideManagerDialog",

    mixins: [dialog],

    components: {
        StudipButton,
        StudipIcon,
        StudipTooltipIcon,
        MessageBox,
        MeetingMessageDialog,
        DefaultSlidePreviewDialog
    },

    data() {
        return {
            modal_message: {},
            message: '',
            showConfirmDialog: false,
            showPreviewDialog: false
        }
    },

    computed: {
        ...mapGetters(['font', 'templates'])
    },

    mounted () {
        this.getInstalledFont();
        this.getInstalledTemplates();
    },

    methods: {
        close() {
            this.dialogClose();
            this.$emit('close');
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

        getInstalledFont() {
            this.$store.dispatch(DEFAULT_SLIDE_FONT_READ);
        },

        getInstalledTemplates() {
            this.$store.dispatch(DEFAULT_SLIDE_TEMPLATE_READ);
        },

        performConfirm(callback, data = null) {
            this.showConfirmDialog = false;
            if (callback && this[callback] != undefined) {
                this[callback](data);
            }
        },

        deleteFont(type) {
            var filtered_font = this.font.filter(f => f.type == type);
            var targeted_font = filtered_font.length ? filtered_font[0] : null;
            if (!this.font || targeted_font == null || !targeted_font.name) {
                return;
            }
            this.showConfirmDialog = {
                title: this.$gettext('Schriftart löschen'),
                text: this.$gettext('Sind Sie sicher, dass Sie diesen Schriftart löschen möchten?'),
                type: 'question', //info, warning, question
                isConfirm: true,
                callback: 'performDeleteFont',
                callback_data: {type}
            }
        },

        performDeleteFont({type}){
            this.$store.dispatch(DEFAULT_SLIDE_FONT_DELETE, type)
            .then(({data}) => {
                this.modal_message = data.message;
                if (data.message.type == 'success') {
                    this.getInstalledFont();
                }
            });
        },

        addEmptyTemplateItem(event) {
            if (event) {
                event.preventDefautl();
            }
            var next_index = (this.templates && Object.keys(this.templates).length) ? Object.keys(this.templates).length : 0;
            if (!this.templates) {
                return;
            }
            if (next_index == 0) { // Fresh
                this.$set(this.templates, (next_index + 1), {
                    pdf: {},
                    php: {}
                });
            } else if (this.templates[next_index] && this.templates[next_index].pdf && Object.keys(this.templates[next_index].pdf).length > 0) {
                this.$set(this.templates, (next_index + 1), {
                    pdf: {},
                    php: {}
                });
                setTimeout(() => {
                    var dialogComponent = this.$children.filter( (children) => {
                        return children.$options.name == 'Dialog'
                    });
                    if (dialogComponent.length && this.$refs && this.$refs.template && this.$refs.template[next_index]) {
                        $(`#${dialogComponent[0].$data.id}`).animate(
                            {scrollTop: $(this.$refs.template[next_index]).position().top},
                            'slow'
                        );
                    }
                }, 100);
            } else {
                this.$set(this.modal_message, 'type', 'error');
                this.$set(this.modal_message, 'text', gettextInterpolate($gettext('Bitte laden Sie zuerst eine PDF-Datei für die %{ next_index }. Vorlage hoch.'), {next_index, next_index}));
            }
        },

        deleteTemplate(what, page) {
            if (this.templates && this.templates[page] && this.templates[page][what]
                && Object.keys(this.templates[page][what]).length == 0) {
                // Remove template if pdf delete is asked!
                if (what == 'pdf') {
                    this.$delete(this.templates, page);
                    this.$set(this.modal_message, 'type', 'success');
                    this.$set(this.modal_message, 'text', this.$gettext('Folie/Template wurde erfolgreich gelöscht'));
                }
                return;
            } else {
                var text = this.$gettext('Es wird die gesamte Vorlage löschen! Sind Sie sicher, dass Sie diese Vorlage löschen möchten?');
                if (what == 'php') {
                    text = this.$gettext('Sind Sie sicher, dass Sie diese Template löschen möchten?');
                }
                this.showConfirmDialog = false;
                this.showConfirmDialog = {
                    title: this.$gettext('Vorlage löschen'),
                    text: text,
                    type: 'question', //info, warning, question
                    isConfirm: true,
                    callback: 'performDeleteTemplate',
                    callback_data: {page, what}
                }
            }
        },

        performDeleteTemplate({page, what}){
            this.$store.dispatch(DEFAULT_SLIDE_TEMPLATE_DELETE, {page, what})
            .then(({data}) => {
                this.modal_message = data.message;
                if (data.message.type == 'success') {
                    this.$store.dispatch(DEFAULT_SLIDE_TEMPLATE_READ);
                }
            });
        },

        displayTemplateName(template, where) {
            var sub = (where == 'pdf') ? 'Folie' : 'Template';
            var name = gettextInterpolate($gettext('Keine %{ sub }'), {sub: sub});
            if (template && template[where] && template[where].filename) {
                name = template[where].filename;
            }
            return name;
        },

        handleFileUpload(what, ref_index = 0) {
            // Prevent php upload without having the pdf!
            if (what == 'php' && this.templates && this.templates[ref_index + 1] && this.templates[ref_index + 1].pdf && Object.keys(this.templates[ref_index + 1].pdf).length == 0) {
                this.modal_message = {};
                this.$set(this.modal_message, 'type', 'error');
                this.$set(this.modal_message, 'text', gettextInterpolate($gettext('Bitte laden Sie zuerst eine PDF-Datei für die %{ ref }. Vorlage hoch.'), {ref: ref_index + 1}));
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
            let uploadTo = DEFAULT_SLIDE_FONT_UPLOAD;
            let readFrom = DEFAULT_SLIDE_FONT_READ;
            if (what != 'font') {
                uploadTo = DEFAULT_SLIDE_TEMPLATE_UPLOAD;
                readFrom = DEFAULT_SLIDE_TEMPLATE_READ;
                formData.append('page', (ref_index + 1));
            } else {
                formData.append('type', this.font[ref_index].type);
            }
            this.$store.dispatch(uploadTo, formData)
            .then(({ data }) => {
                this.modal_message = data.message;
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

        downloadSample(what) {
            this.$store.dispatch(DEFAULT_SLIDE_SAMPLE_TEMPLATE_DOWNLOAD, what)
            .then(({ data }) => {
                if (data.message) {
                    this.modal_message = data.message;
                }
                if (data.content) {
                    var type = what == 'php' ? 'text/plain;charset=utf-8' : 'application/pdf';
                    const blob = new Blob([data.content], { type: type });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'samples.' + what;
                    link.click();
                    URL.revokeObjectURL(link.href);
                }
            }).catch (({error}) => {
                console.log(error);
            });
        }
    },
}
</script>