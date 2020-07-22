<template>
    <span :class="'tooltip ' + (important ? ' tooltip-important' : '') + (badge ? ' meeting-badge' : ' tooltip-icon')" data-tooltip :title="!html ? text : ''">
        <span v-if="html && !badge" class="tooltip-content">{{ text }}</span>
        <slot v-else-if="badge"></slot>
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
        },
        badge: {
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
