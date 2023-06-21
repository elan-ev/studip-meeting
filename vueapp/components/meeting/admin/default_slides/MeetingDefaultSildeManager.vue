<template>
    <div>
        <h1>{{ $gettext('Meetings Standard-Folie') }}</h1>

        <MessageBox v-if="general_config['read_default_slides_from'] == 'server'" type="info">
            {{ $gettext('Um die folgenden Standard-Folieneinstellungen verwenden zu können, Stud.IP für Standardfolien verwenden sollte.') }}
        </MessageBox>

        <MessageList />
        <form class="default" @submit.prevent>
            <MeetingFonts />
            <MeetingTemplates :templates="templates"/>
            <footer>
                <StudipButton
                    icon="add"
                    @click="addEmptyTemplateItem">
                    {{ $gettext('Vorlage hinzufügen') }}
                </StudipButton>
                <StudipButton
                    icon="download"
                    @click="downloadSample('php')">
                    {{ $gettext('PHP Mustervorlage herunterladen') }}
                </StudipButton>
            </footer>
        </form>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import {translate} from 'vue-gettext';
const {gettext: $gettext, gettextInterpolate} = translate;

import {
    DEFAULT_SLIDE_TEMPLATE_READ,
    DEFAULT_SLIDE_SAMPLE_TEMPLATE_DOWNLOAD,
    MESSAGE_ADD,
    MESSAGES_CLEAR
} from "@/store/actions.type"

import MeetingFonts from "@meeting/admin/default_slides/MeetingFonts";
import MeetingTemplates from "@meeting/admin/default_slides/MeetingTemplates";

export default {
    name: 'meeting-default-slides-manager',
    components: {
        MeetingFonts,
        MeetingTemplates
    },
    data() {
        return {
            message: {}
        }
    },
    computed: {
        ...mapGetters(['general_config', 'templates'])
    },
    methods: {
        getInstalledTemplates() {
            this.$store.dispatch(DEFAULT_SLIDE_TEMPLATE_READ);
        },
        downloadSample(what) {
            this.$store.dispatch(DEFAULT_SLIDE_SAMPLE_TEMPLATE_DOWNLOAD, what)
            .then(({ data }) => {
                if (data.message) {
                    this.message = data.message;
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
        },
        addEmptyTemplateItem() {
            if (!this.templates) {
                return;
            }
            let currentTemplatesNum = Object.keys(this.templates).length;
            let nextIndex = currentTemplatesNum  > 0 ? (currentTemplatesNum + 1) : 1;
            let addTemplate = true;
            // Check if the previous template has pdf file already.
            let prevIndex = nextIndex - 1;
            if (nextIndex > 1 && this.templates?.[prevIndex]?.pdf && Object.keys(this.templates[prevIndex].pdf).length == 0) {
                addTemplate = false;
            }
            if (addTemplate) {
                this.$set(this.templates, nextIndex, {pdf: {}, php: {}});
            } else {
                this.$set(this.message, 'type', 'error');
                this.$set(this.message, 'text',
                    gettextInterpolate($gettext('Bitte laden Sie zuerst eine PDF-Datei für die %{ next_index }. Vorlage hoch.'),
                        {next_index: (nextIndex - 1)})
                );
            }
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
        this.getInstalledTemplates();
    },
}
</script>
