<template>
    <img :src="path">
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
            return ICON_URL + this.role_to_color[this.role] + '/' + this.icon + '.svg';
        }
    }
}
</script>
