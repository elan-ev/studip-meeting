import axios from "axios";
import VueAxios from "vue-axios";

const ApiService = {
    init(app) {
        this.app = app;
        this.app.use(VueAxios, axios);
    },

    query(resource, params) {
        return this.app.axios.get(resource, params);
    },

    get(resource, slug = "") {
        return this.app.axios.get(`${resource}/${slug}`);
    },

    post(resource, params) {
        return this.app.axios.post(`${resource}`, params);
    },

    update(resource, slug, params) {
        return this.app.axios.put(`${resource}/${slug}`, params);
    },

    put(resource, params) {
        return this.app.axios.put(`${resource}`, params);
    },

    delete(resource) {
        return this.app.axios.delete(resource);
    },

    upload(resource, form) {
        return this.app.axios.post(`${resource}`, form, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },
};

export default ApiService;
