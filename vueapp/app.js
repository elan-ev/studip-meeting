import App from './App.vue';

import { createApp, h } from "vue";
import $ from 'jquery';
import router from "./router";
import store from "./store";
import "./public-path";

import { ERROR_COMMIT } from "./store/actions.type";
import ApiService from "./common/api.service";

import { createGettext } from 'vue3-gettext';
import PortalVue from 'portal-vue';
import translations from './i18n/translations.json';
// Common Studip Components.
import StudipDialog from '@studip/StudipDialog.vue';
import StudipIcon from '@studip/StudipIcon.vue';
import StudipButton from '@studip/StudipButton.vue';
import StudipTooltipIcon from "@studip/StudipTooltipIcon";

// Miscellaneous.
import MessageBox from '@/components/messages/MessageBox.vue';
import MessageList from '@/components/messages/MessageList.vue';


STUDIP.Vue.load().then(({ createApp: studipCreateApp }) => {
    const app = createApp({
        render: () => h(App)
    });
    app.use(store);
    app.use(router);

    ApiService.init(app);

    // Redirect to login page, if a 401 is catched
    // intercept the global error
    app.axios.interceptors.response.use(
        (response) => {
            return response;
        },
        function (error) {
            store.dispatch(ERROR_COMMIT, error.response);

            // Do something with response error
            return Promise.reject(error)
        }
    );
    app.axios.defaults.baseURL = API_URL;

    app.component('StudipDialog', StudipDialog)
    app.component('StudipIcon', StudipIcon)
    app.component('StudipButton', StudipButton)
    app.component('StudipTooltipIcon', StudipTooltipIcon)
    app.component('MessageBox', MessageBox)
    app.component('MessageList', MessageList)


    const gettext = createGettext({
        availableLanguages: {
            de_DE: 'Deutsch',
            en_GB: 'British English',
        },
        defaultLanguage: String.locale.replace('-', '_'),
        translations: translations,
        silent: true,
    });

    app.use(gettext);
    app.use(PortalVue);

    $(() => app.mount("#app"));
});
