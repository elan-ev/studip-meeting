<template>
    <button class="button" :class="[icon]" :type="type" :name="name" v-bind="$attrs" v-on="$listeners">
        <slot>ButtonLabel</slot>
    </button>
</template>

<script>
export default {
    name: "studip-button",
    props: {
        name: {
            type: String,
        },
        icon: {
            type: String,
            validator(value) {
                return ['', 'accept', 'cancel', 'edit', 'move-up', 'move-down', 'add', 'download', 'search', 'trash'].includes(value)
            },
            default: ''
        },
        type: {
            type: String,
            required: false,
            default: "submit"
        }
    }
}
</script>
