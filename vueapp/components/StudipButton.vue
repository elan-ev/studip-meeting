<template>
    <button class="button" :class="[icon]" type="submit" :name="name" @click="onClick">
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
        }
    },
    methods: {
        onClick() {
            this.$emit('click');
        }
    }
}
</script>
