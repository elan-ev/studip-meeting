<template>
    <div v-if="error" class="messagebox messagebox_error">
        <div class="messagebox_buttons">
            <a class="close" href="#" title="Nachrichtenbox schliessen">
                <span>Nachrichtenbox schliessen</span>
            </a>
        </div>
        <div v-if="error.data.errors" class="messagebox_error_text">
            <span v-for="(err, i) in error.data.errors" :key="i">
                {{ err.code }}: {{ err.title }}
            </span>
        </div>
        <div v-else-if="error.data.error" class="messagebox_error_text">
            <span v-for="(err, i) in error.data.error" :key="i">
                {{ err.message }}<br>
                Line {{ err.line }} in file {{ err.file }}
            </span>
        </div>
    </div>
</template>

<script>
import store from "@/store";
import { mapGetters } from "vuex";

import { ERROR_CLEAR } from "@/store/actions.type";

export default {
    name: "Error",

    computed: {
        ...mapGetters(["error"])
    },
    methods: {
        clearErrors() {
            this.$store.dispatch(ERROR_CLEAR);
        }
    }
};
</script>
