<template>
    <span data-tooltip class="tooltip" :class="cssClass" :title="title" tabindex="0">
        <span v-if="html && !badge" class="tooltip-content">{{ text }}</span>
        <slot v-else-if="badge"></slot>
    </span>
</template>

<script>
export default {
    name: "studip-tooltip-icon",
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
    computed: {
        cssClass () {
            let classes = [];
            if (this.important) {
                classes.push('tooltip-important');
            }
            if (this.badge) {
                classes.push('meeting-badge');
            } else {
                classes.push('tooltip-icon');
            }
            return classes.join(' ');
        },
        title () {
            return !this.html ? this.text : '';
        }
    }
}
</script>
