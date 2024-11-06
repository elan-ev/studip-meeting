<template>
    <input
        v-if="name"
        type="image"
        :name="name"
        :src="url"
        :width="size"
        :height="size"
        :role="ariaRole"
        v-bind="$attrs"
        v-on="$listeners"
        :alt="$attrs.alt ?? ''"
    />
    <img v-else
         :src="url"
         :width="size"
         :height="size"
         :role="ariaRole"
         v-bind="$attrs"
         v-on="$listeners"
         :alt="$attrs.alt ?? ''"
    />
</template>

<script>
import Vue from 'vue';

export default Vue.extend({
    name: 'studip-icon',
    props: {
        ariaRole: {
            type: String,
            required: false,
        },
        name: {
            type: String,
            required: false,
        },
        role: {
            type: String,
            required: false,
            default: 'clickable',
        },
        shape: String,
        size: {
            type: Number,
            required: false,
            default: 16,
        },
    },
    computed: {
        url() {
            if (this.shape.indexOf('http') === 0) {
                return this.shape;
            }
            var path = this.shape.split('+').reverse().join('/');
            return `${window.STUDIP.ASSETS_URL}images/icons/${this.color}/${path}.svg`;
        },
        color() {
            switch (this.role) {
                case 'info':
                    return 'black';

                case 'inactive':
                    return 'grey';

                case 'accept':
                case 'status-green':
                    return 'green';

                case 'attention':
                case 'new':
                case 'status-red':
                    return 'red';

                case 'info_alt':
                    return 'white';

                case 'status-yellow':
                    return 'yellow';

                case 'sort':
                case 'clickable':
                case 'navigation':
                default:
                    return 'blue';
            }
        },
    },
});
</script>
