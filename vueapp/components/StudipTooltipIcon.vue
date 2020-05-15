<template>
    <span :class="'tooltip tooltip-icon' + (important ? ' tooltip-important' : '')" data-tooltip :title="!html ? text : ''">
        <span v-if="html" class="tooltip-content">{{ text }}</span>
    </span>
</template>

<script>
export default {
    name: "StudipTooltipIcon",
    props: {
        text: {
            type: String,
            required: true
        },
        important: {
            type: Boolean,
            required: false,
            default: false
        },
        html: {
            type: Boolean,
            required: false,
            default: false
        }
    },

    data() {
        return {
            
        }
    },

    computed: {
        
    }
}
</script>
