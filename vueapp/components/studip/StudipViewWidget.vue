<template>
    <ul class="widget-list widget-links sidebar-views meeting-view-widget">
        <li v-for="(view, index) in views" :key="index" :class="{ active: currentView === view.id }">
            <a href="#" @click.prevent="setView(view.id)">
                {{ view.name }}
            </a>
        </li>
    </ul>
</template>

<script>
export default {
    name: 'studip-view-widget',
    props: {
        currentView: {
            type: String,
            required: true
        },
        views: {
            type: Array,
            required: true
        },
    },
    methods: {
        setView(viewId) {
            this.$emit('changeView', viewId);
        }
    }
}
</script>
