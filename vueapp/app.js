import Vue from 'vue';
import App from './App.vue';

import $ from 'jquery';
import router from "./router";
import store from "./store";
import "./public-path";

import { CHECK_AUTH, LOGOUT, ERROR_COMMIT } from "./store/actions.type";
import ApiService from "./common/api.service";
import DateFilter from "./common/date.filter";
import ErrorFilter from "./common/error.filter";
import I18N from "./common/i18n.filter";

Vue.filter("date", DateFilter);
Vue.filter("error", ErrorFilter);
Vue.filter("i18n", I18N);

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

$(function() {
    window.Vue = new Vue({
        router,
        store,
        render: h => h(App)
    }).$mount('#app');

    window.Vue.axios.defaults.baseURL = API_URL;
});
