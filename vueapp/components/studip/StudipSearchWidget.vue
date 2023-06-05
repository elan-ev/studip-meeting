<template>
    <form class="sidebar-search meeting-sidebar-search" @submit.prevent="">
        <ul class="needles">
            <StudipSearchWidgetNeedle @send="sendSearchTerm" @clear="clearSearchTerm"/>
        </ul>
    </form>
</template>

<script>
import StudipSearchWidgetNeedle from "@studip/breakchanges/StudipSearchWidgetNeedle";

export default {
    name: 'studip-search-widget',
    components: {
        StudipSearchWidgetNeedle,
    },
    methods: {
        sendSearchTerm(value) {
            this.$emit('setRoomFilter', value);
        },
        clearSearchTerm() {
            this.$emit('clearRoomFilter');
        }
    }
}
</script>
