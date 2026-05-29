<template>
    <div>
        <studip-dialog
            :title="$gettext('Vorlagenvorschau')"
            :closeText="$gettext('Abbrechen')"
            closeClass="cancel"
            class="meeting-dialog"
            width="800"
            height="800"
            @close="close"
        >
            <template v-slot:dialogContent>
                <embed
                    v-if="preview"
                    :src="preview"
                    type="application/pdf"
                    class="meeting-pdf-preview-embed"
                />
                <div v-else class="meeting-empty-preview">
                    {{ $gettext('Keine Vorschau verfügbar.') }}
                </div>
            </template>
        </studip-dialog>
    </div>
</template>

<script>
export default {
    name: "DefaultSlidePreviewDialog",

    props: {
        preview: {
            type: String,
            required: true,
        },
    },

    methods: {
        close() {
            this.$emit('cancel')
        },
    },
}
</script>

