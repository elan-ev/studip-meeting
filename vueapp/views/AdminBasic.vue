<template>
    <div>
        <OpencastConfigStep :step="1" :steps="5" />
        <form class="default" v-if="config">
            <fieldset>
                <legend>
                    {{ "Opencast Server Einstellungen" | i18n }}
                    <StudipIcon icon="accept" role="status-green" v-if="config.checked"/>
                </legend>

                <label>
                    {{ "Basis URL zur Opencast Installation" | i18n }}
                    <input type="text"
                        v-model="config.url"
                        placeholder="http://opencast.url">
                </label>

                <label>
                    {{ "Nutzerkennung" | i18n }}
                    <input type="text"
                        v-model="config.user"
                        placeholder="ENDPOINT_USER">
                </label>

                <label>
                    {{ "Passwort" | i18n }}
                    <input type="password"
                        v-model="config.password"
                        placeholder="ENDPOINT_USER_PASSWORD">
                </label>

                <label>
                    {{ "LTI Consumerkey" | i18n }}
                    <input type="text"
                        v-model="config.ltikey"
                        placeholder="CONSUMERKEY"
                        :class="{ 'invalid': lti_error }">
                </label>

                <label>
                    {{ "LTI Consumersecret" | i18n }}
                    <input type="text"
                        v-model="config.ltisecret"
                        placeholder="CONSUMERSECRET"
                        :class="{ 'invalid': lti_error }">
                </label>

                <MessageBox v-if="lti_error" type="error" @hide="lti_error = false">
                    Überprüfung der LTI Verbindung fehlgeschlagen! <br />
                    Kontrollieren Sie die eingetragenen Daten und stellen Sie
                    sicher, dass Cross-Origin Aufrufe von dieser Domain zur URL
                    {{ lti.launch_url }} möglich sind! <br />
                    Denken sie auch daran, in Opencast die korrekten
                    access-control-allow-* Header zu setzen.
                </MessageBox>
            </fieldset>

            <footer>
                <StudipButton icon="accept" @click="storeConfig">
                    Einstellungen speichern und überprüfen
                </StudipButton>
                <StudipButton @click="nextStep" v-if="config.checked">
                    Weiter zu Schritt 2 >>
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
    name: "AdminBasic",
    components: {
        StudipButton, StudipIcon,
        MessageBox,
        OpencastConfigStep
    },
    data() {
        return {
            message: null,
            lti_error: false,
            lti: {}
        }
    },
    computed: {
        ...mapGetters(['config'])
    },
    methods: {
        storeConfig() {
            this.message = { type: 'info', text: 'Überprüfe Konfiguration...'};
            this.config.checked = false;

            this.$store.dispatch(CONFIG_CREATE, this.config)
                .then(({ data }) => {
                    this.message = data.message;
                    this.checkLti(data.lti);
                    this.$store.commit(CONFIG_SET, data.config);
                });
        },
        checkLti(lti) {
            let view = this;
            this.lti = lti;

             Vue.axios.post(lti.launch_url, lti.launch_data, {
                 crossDomain: true,
                 withCredentials: true
             })
            .then(() => {
                view.lti_error = false;
            }).catch(function (error) {
                view.lti_error = true;
            });
        },
        nextStep() {
            this.$router.push({ name: 'admin_step2' });
        }
    },
    mounted() {
        store.dispatch(CONFIG_READ, 1);
    }
};
</script>
