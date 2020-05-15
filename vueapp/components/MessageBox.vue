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
    props: {
        type: {
            type: String,
        },
        timer: {
            type: Number,
            required: false,
            default: 0
        }
    },
    methods: {
        hide() {
            this.$emit('hide');
        }
    },
    created() {
        if (this.timer) {
            var self = this;
            setTimeout(function() {
                self.hide();
            }, this.timer); 
        } 
    }
};
</script>
