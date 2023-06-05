<template>
    <input v-if="name" type="image" :name="name" :src="url"
            :width="size" :height="size" v-bind="$attrs" v-on="$listeners">
    <img v-else :src="url" :width="size" :height="size" v-bind="$attrs" v-on="$listeners">
</template>

<script>
    export default {
        name: 'studip-icon',
        props: {
            icon: String,
            role: {
                type: String,
                required: false,
                default: 'clickable'
            },
            size: {
                required: false,
                default: 16
            },
            name: {
                type: String,
                required: false
            }
        },
        computed: {
            url: function () {
                if (this.icon.indexOf("http") === 0) {
                    return this.icon;
                }
                var path = this.icon.split('+').reverse().join('/');
                return `${STUDIP.ASSETS_URL}images/icons/${this.color}/${path}.svg`;
            },
            color: function () {
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
            }
        }
    }
</script>
