<template>
    <div class="messagebox"
        :class="`messagebox_` + type">
        <div class="messagebox_buttons">
            <a class="close" href="#" title="Nachrichtenbox schliessen" @click.stop="hide">
                <span>Nachrichtenbox schliessen</span>
            </a>
        </div>

        <div>
            <slot>Message</slot>
        </div>
    </div>
</template>

<script>
export default {
    name: "MessageBox",
    props: ['type'],
    methods: {
        hide() {
            this.$emit('hide');
        }
    }
};
</script>
