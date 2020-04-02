<template>
    <button class="button" :class="[icon]" :type="type" :name="name" @click="onClick">
        <slot>ButtonLabel</slot>
    </button>
</template>

<script>
export default {
    name: "StudipButton",
    props: {
        name: {
            type: String,
        },
        icon: {
            type: String,
            validator(value) {
                return ['', 'accept', 'cancel', 'edit', 'move-up', 'move-down', 'add', 'download', 'search'].includes(value)
            },
            default: ''
        },
        type: {
            type: String,
            required: false,
            default: "submit"
        }
    },
    methods: {
        onClick() {
            this.$emit('click');
        }
    }
}
</script>
