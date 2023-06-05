<template>
    <li class="meeting-sidebar-search-item">
        <template v-if="apply_break_change">
            <div class="input-group files-search">
                <input
                    type="text"
                    v-model="searchTerm"
                    :placeholder="$gettext('Räume filtern nach Name')"
                    @keypress.enter="sendSearchTerm"
                />
                <a v-if="searchTerm && showClear" @click.prevent="clearSearchTerm"
                    class="reset-search meeting-reset-search">
                    <StudipIcon icon="decline" size="20"/>
                </a>
                <button
                    type="submit"
                    :value="$gettext('Suchen')"
                    aria-controls="search"
                    class="submit-search"
                    @click="sendSearchTerm"
                >
                    <StudipIcon icon="search" size="20" />
                </button>
            </div>
        </template>
        <template v-else>
            <div class="clear-search" v-if="searchTerm && showClear" style="text-align: right;">
                <a @click.prevent="clearSearchTerm">
                    <StudipIcon icon="search+decline" role="clickable" size="16"></StudipIcon>
                    {{ $gettext('Zurücksetzen') }}
                </a>
            </div>
            <input type="text" v-model="searchTerm"
                :placeholder="$gettext('Räume filtern nach Name')"
                @keypress.enter="sendSearchTerm"
            >
            <input type="submit" :value="$gettext('Suchen')" @click="sendSearchTerm">
        </template>
    </li>
</template>

<script>
export default {
    name: 'studip-search-widget-needle',
    data() {
        return {
            searchTerm: '',
            showClear: false
        }
    },
    computed: {
        apply_break_change() {
            return (STUDIP_VERSION != undefined && STUDIP_VERSION >= 5.3) ? true : false;
        }
    },
    methods: {
        sendSearchTerm() {
            if (this.searchTerm) {
                this.$emit('send', this.searchTerm);
                this.showClear = true;
            }
        },
        clearSearchTerm() {
            this.searchTerm = '';
            this.$emit('clear');
            this.showClear = false;
        }
    },
    watch: {
        searchTerm(value) {
            if (value == '') {
                this.clearSearchTerm();
            }
        }
    },
}
</script>