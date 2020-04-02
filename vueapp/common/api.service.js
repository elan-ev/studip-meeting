import Vue from "vue";
import axios from "axios";
import VueAxios from "vue-axios";

const ApiService = {
    init() {
        Vue.use(VueAxios, axios);
    },

    query(resource, params) {
        return Vue.axios.get(resource, params);
    },

    get(resource, slug = "") {
        return Vue.axios.get(`${resource}/${slug}`);
    },

    post(resource, params) {
        return Vue.axios.post(`${resource}`, params);
    },

    update(resource, slug, params) {
        return Vue.axios.put(`${resource}/${slug}`, params);
    },

    put(resource, params) {
        return Vue.axios.put(`${resource}`, params);
    },

    delete(resource) {
        return Vue.axios.delete(resource);
    }
};

export default ApiService;
