<template>
    <div class="steps">
        <span v-for="index in steps">
            <span class="step" :class="{
                'active': index == step
            }">
                {{ index }}
            </span>

            <span class="spacer" v-if="index < steps"></span>
        </span>
    </div>
</template>

<script>
export default {
    name: "OpencastConfigStep",
    props: ['step', 'steps'],
    methods: {
        hide() {
            this.$emit('hide');
        }
    }
};
</script>
