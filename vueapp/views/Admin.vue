<template>
    <div>
        <MeetingAdminConfig v-if="current_view_id === 'config'"/>
        <MeetingDefaultSildeManager v-else-if="current_view_id === 'slides'"/>

        <!-- Sidebar Contents -->
        <MountingPortal mountTo="#meeting-admin-view-widget" name="sidebar-views" v-if="generate_view_items.length">
            <StudipViewWidget
                :currentView="current_view_id"
                :views="generate_view_items"
                @changeView="setNewView"
            />
        </MountingPortal>
    </div>
</template>

<script>
import {
    MESSAGES_CLEAR
} from "@/store/actions.type";

import MeetingAdminConfig from "@meeting/admin/MeetingAdminConfig";
import MeetingDefaultSildeManager from "@meeting/admin/default_slides/MeetingDefaultSildeManager";
import StudipViewWidget from '@studip/StudipViewWidget.vue';

export default {
    name: "Admin",

    components: {
        MeetingAdminConfig,
        MeetingDefaultSildeManager,
        StudipViewWidget
    },

    data() {
        return {
            current_view_id: 'config'
        }
    },

    computed: {
        generate_view_items() {
            let viewItems = [];
            viewItems.push({id: 'config', name: this.$gettext('Konfiguration')});
            viewItems.push({id: 'slides', name: this.$gettext('Standard-Folie Verwaltung')});
            return viewItems;
        }
    },

    methods: {
        setNewView(selected) {
            let selectedView = this.generate_view_items.find(view => view.id === selected);
            if (selectedView) {
                this.current_view_id = selected;
                this.$store.dispatch(MESSAGES_CLEAR);
            }
        }
    },
};
</script>
