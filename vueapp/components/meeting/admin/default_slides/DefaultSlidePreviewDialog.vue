<template>
    <div>
        <studip-dialog
            :title="$gettext('Vorlagenvorschau')"
            :closeText="$gettext('Abbrechen')"
            closeClass="cancel"
            class="meeting-dialog"
            height="500"
            width="800"
            @close="close"
        >
            <template v-slot:dialogContent>
                <pdf v-if="preview" :src="preview" :page="1" @error="errorCaptured" @progress="loadedRatio = $event">
                    <template slot="loading">
                    loading content...
                    </template>
                </pdf>
            </template>
        </studip-dialog>
    </div>
</template>

<script>
import pdf from 'pdfvuer'
import 'pdfjs-dist/build/pdf.worker.entry'



export default {
    name: "DefaultSlidePreviewDialog",

    components: {
        pdf
    },

    props: {
        preview: {
            type: String,
            required: true
        },
    },

    methods: {
        close() {
            this.$emit('cancel');
        },
        errorCaptured(err) {
            console.log(err);
            return false
        },
    },
}
</script>
