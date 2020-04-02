<template>
    <img :src="path" :width="size" :height="size">
</template>

<script>
export default {
    name: "StudipIcon",
    props: {
        icon: {
            type: String,
        },
        role: {
            type: String,
            validator(value) {
                return ['info', 'clickable', 'accept', 'status-green', 'inactive',
                    'navigation', 'new', 'attention', 'status-red', 'info_alt',
                    'sort', 'status-yellow'].includes(value)
            },
            default: ''
        },
        size: {
            type: String,
            required: false,
            default: "16"
        }
    },

    data() {
        return {
            role_to_color : {
                'info'          : 'black',
                'clickable'     : 'blue',
                'accept'        : 'green',
                'status-green'  : 'green',
                'inactive'      : 'grey',
                'navigation'    : 'blue',
                'new'           : 'red',
                'attention'     : 'red',
                'status-red'    : 'red',
                'info_alt'      : 'white',
                'sort'          : 'yellow',
                'status-yellow' : 'yellow'
            }
        }
    },

    computed: {
        path() {
            var path = this.icon.split('+').reverse().join('/');
            return ICON_URL + this.role_to_color[this.role] + '/' + path + '.svg';
        }
    }
}
</script>
