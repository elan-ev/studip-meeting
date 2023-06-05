<template>
    <button v-if="apply_break_change" class="action-menu-icon meeting-action-menu-icon" :title="$gettext('Aktionen')" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <a v-else class="action-menu-icon meeting-action-menu-icon" :title="$gettext('Aktionen')" aria-expanded="false" :aria-label="$gettext('Aktionsmenü')" href="#">
        <div></div>
        <div></div>
        <div></div>
    </a>
</template>

<script>
export default {
    name: 'studip-action-menu-icon',
    computed: {
        apply_break_change() {
            return (STUDIP_VERSION != undefined && STUDIP_VERSION >= 5.2) ? true : false;
        }
    },
}
</script>