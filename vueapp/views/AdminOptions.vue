<template>
    <div>
        <OpencastConfigStep :step="2" :steps="5" />
        <form class="default" v-if="config">
            <fieldset>
                <legend>
                    {{ "Opencast Server Einstellungen" | i18n }}
                    <StudipIcon icon="accept" role="status-green" v-if="config.checked"/>
                </legend>
            </fieldset>

            <footer>
                <StudipButton @click="prevStep">
                    {{ "<< Zurück zu Schritt 1" | i18n }}
                </StudipButton>
                <StudipButton icon="accept" @click="nextStep">
                    {{ "Weiter zu Schritt 3 >>" | i18n }}
                </StudipButton>
            </footer>
        </form>

        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            {{ message.text }}
        </MessageBox>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";

import OpencastConfigStep from "@/components/OpencastConfigStep";

import {
    CONFIG_READ, CONFIG_UPDATE,
    CONFIG_CREATE, CONFIG_DELETE
} from "@/store/actions.type";

import {
    CONFIG_SET
} from "@/store/mutations.type";

export default {
    name: "AdminOptions",
    components: {
        StudipButton, StudipIcon,
        MessageBox,
        OpencastConfigStep
    },
    data() {
        return {
            message: null
        }
    },
    computed: {
        ...mapGetters(['config'])
    },
    methods: {
        prevStep() {
            this.$router.push({ name: 'admin' });
        },
        nextStep() {
            this.$router.push({ name: 'admin_step3' });
        }
    }
};
</script>
