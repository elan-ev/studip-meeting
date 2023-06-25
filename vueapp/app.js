import Vue from 'vue';
import App from './App.vue';

import $ from 'jquery';
import router from "./router";
import store from "./store";
import "./public-path";

import { ERROR_COMMIT } from "./store/actions.type";
import ApiService from "./common/api.service";
import DateFilter from "./common/date.filter";
import ErrorFilter from "./common/error.filter";
import {gettextinterpolate} from "./common/gettextinterpolate.filter";

import GetTextPlugin from 'vue-gettext';
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

Vue.filter("date", DateFilter);
Vue.filter("error", ErrorFilter);
Vue.filter("gettextinterpolate", gettextinterpolate);
/*
# Example of using this filter:
{{ $gettext('%{ page }. Vorlage') | gettextinterpolate({page}) }}
*/

ApiService.init();

// Redirect to login page, if a 401 is catched
Vue.axios.interceptors.response.use((response) => { // intercept the global error
        return response;
    }, function (error) {
        store.dispatch(ERROR_COMMIT, error.response);

        // Do something with response error
        return Promise.reject(error)
    }
);

Vue.component('StudipDialog', StudipDialog)
Vue.component('StudipIcon', StudipIcon)
Vue.component('StudipButton', StudipButton)
Vue.component('StudipTooltipIcon', StudipTooltipIcon)
Vue.component('MessageBox', MessageBox)
Vue.component('MessageList', MessageList)

Vue.use(GetTextPlugin, {
    availableLanguages: {
        de_DE: 'Deutsch',
        en_GB: 'British English',
    },
    defaultLanguage: String.locale.replace('-', '_'),
    translations: translations,
    silent: true,
});

Vue.use(PortalVue);

$(function() {
    const VueInstance = new Vue({
        router,
        store,
        render: h => h(App)
    }).$mount('#app');

    VueInstance.axios.defaults.baseURL = API_URL;
});
